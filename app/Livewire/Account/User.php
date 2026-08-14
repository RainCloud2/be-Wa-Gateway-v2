<?php

namespace App\Livewire\Account;

use App\Models\User as UserModel;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Livewire\Traits\WithPerPagePagination;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserExport;
use App\Exports\UserImportTemplate;
use App\Imports\UserImport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('layouts.admin')]
class User extends Component
{
    use WithPagination, WithPerPagePagination, WithFileUploads, AuthorizesRequests;

    public $userId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $roles = [];
    public $editMode = false;
    public $search = '';
    public $deleteId = null;
    public $deleteItemName = '';
    public $importFile = null;
    public $selectMode = false;
    public $selectedIds = [];

    public function export()
    {
        return Excel::download(new UserExport, 'users.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new UserImportTemplate, 'user_import_template.xlsx');
    }

    public function import()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new UserImport, $this->importFile->getRealPath());
        $this->dispatch('notify', type: 'success', message: 'Users imported successfully.');
        $this->importFile = null;
        $this->dispatch('hide-import-modal');
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->userId,
            'roles' => 'array',
        ];

        if (!$this->editMode || $this->password) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        return $rules;
    }

    public function render()
    {
        $users = UserModel::with('roles')
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when(!auth()->user()->hasRole('super-admin'), function ($q) {
                $q->whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'));
            })
            ->paginate($this->perPage)
            ->withPath(request()->url());

        $allRoles = \Spatie\Permission\Models\Role::all();

        return view('livewire.account.user', compact('users', 'allRoles'));
    }

    public function create()
    {
        $this->resetInput();
        $this->editMode = false;
        $this->dispatch('show-modal');
    }

    public function store()
    {
        $this->authorize('user.create');
        $this->validate();

        $user = UserModel::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        if ($this->roles) {
            $user->syncRoles($this->roles);
        }

        $this->dispatch('notify', type: 'success', message: 'User created successfully.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function edit($id)
    {
        $this->resetInput();
        $user = UserModel::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roles = $user->roles->pluck('name')->toArray();
        $this->editMode = true;
        $this->dispatch('show-modal');
    }

    public function update()
    {
        $this->authorize('user.update');
        $this->validate();

        $user = UserModel::findOrFail($this->userId);
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);
        $user->syncRoles($this->roles);

        $this->dispatch('notify', type: 'success', message: 'User updated successfully.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function toggleSelectMode()
    {
        $this->selectMode = !$this->selectMode;
        if (!$this->selectMode) $this->selectedIds = [];
    }

    public function toggleSelectAll()
    {
        $ids = \App\Models\User::where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when(!auth()->user()->hasRole('super-admin'), function ($q) {
                $q->whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'));
            })
            ->pluck('id')->toArray();
        $this->selectedIds = count($this->selectedIds) === count($ids) ? [] : $ids;
    }

    public function confirmBulkDelete()
    {
        $this->authorize('user.delete');
        if (empty($this->selectedIds)) return;

        $this->selectedIds = array_filter($this->selectedIds, fn($id) => $id != auth()->id());

        if (empty($this->selectedIds)) {
            $this->dispatch('notify', type: 'danger', message: 'You cannot delete yourself.');
            return;
        }

        $this->deleteId = null;
        $this->deleteItemName = count($this->selectedIds) . ' selected users';
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        $this->authorize('user.delete');
        if (!auth()->user()->hasRole('super-admin')) {
            $this->selectedIds = array_filter($this->selectedIds ?? [], function ($id) {
                $user = UserModel::find($id);
                return $user && !$user->hasRole('super-admin');
            });
        }
        if (!empty($this->selectedIds)) {
            $count = count($this->selectedIds);
            UserModel::whereIn('id', $this->selectedIds)->delete();
            $this->dispatch('notify', type: 'success', message: "{$count} users deleted successfully.");
            $this->selectedIds = [];
            $this->selectMode = false;
            $this->dispatch('hide-delete-modal');
            return;
        }

        if (!$this->deleteId) return;

        UserModel::findOrFail($this->deleteId)->delete();
        $this->dispatch('notify', type: 'success', message: "User '{$this->deleteItemName}' deleted successfully.");
        $this->dispatch('hide-delete-modal');
        $this->deleteId = null;
        $this->deleteItemName = '';
    }

    public function confirmDelete($id, $name)
    {
        $this->authorize('user.delete');
        if ($id == auth()->id()) {
            $this->dispatch('notify', type: 'danger', message: 'You cannot delete yourself.');
            return;
        }

        $this->deleteId = $id;
        $this->deleteItemName = $name;
        $this->selectedIds = [];
        $this->selectMode = false;
        $this->dispatch('show-delete-modal');
    }

    private function resetInput()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->roles = [];
        $this->editMode = false;
    }
}
