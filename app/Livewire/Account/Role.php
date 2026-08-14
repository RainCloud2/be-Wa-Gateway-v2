<?php

namespace App\Livewire\Account;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithFileUploads;
use App\Livewire\Traits\WithPerPagePagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RoleExport;
use App\Exports\RoleImportTemplate;
use App\Imports\RoleImport;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\Models\Permission;

#[Layout('layouts.admin')]
class Role extends Component
{
    use WithPagination, WithPerPagePagination, WithFileUploads, AuthorizesRequests;

    public $roleId = null;
    public $name = '';
    public $selectedPermissions = [];
    public $editMode = false;
    public $search = '';
    public $deleteId = null;
    public $deleteItemName = '';
    public $importFile = null;
    public $selectMode = false;
    public $selectedIds = [];

    public function export()
    {
        return Excel::download(new RoleExport, 'roles.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new RoleImportTemplate, 'role_import_template.xlsx');
    }

    public function import()
    {
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls,csv']);
        Excel::import(new RoleImport, $this->importFile->getRealPath());
        $this->dispatch('notify', type: 'success', message: 'Roles imported successfully.');
        $this->importFile = null;
        $this->dispatch('hide-import-modal');
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'array',
        ];
    }

    public function render()
    {
        $roles = RoleModel::with('permissions')
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage)
            ->withPath(request()->url());

        $groupedPermissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        return view('livewire.account.role', compact('roles', 'groupedPermissions'));
    }

    public function create()
    {
        $this->resetInput();
        $this->editMode = false;
        $this->dispatch('show-modal');
    }

    public function store()
    {
        $this->authorize('role.create');
        $this->validate();

        $role = RoleModel::create(['name' => $this->name]);

        if ($this->selectedPermissions) {
            $role->syncPermissions($this->selectedPermissions);
        }

        $this->dispatch('notify', type: 'success', message: 'Role created successfully.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function edit($id)
    {
        $this->resetInput();
        $role = RoleModel::with('permissions')->findOrFail($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->editMode = true;
        $this->dispatch('show-modal');
    }

    public function update()
    {
        $this->authorize('role.update');
        $this->validate();

        $role = RoleModel::findOrFail($this->roleId);
        $role->update(['name' => $this->name]);
        $role->syncPermissions($this->selectedPermissions);

        $this->dispatch('notify', type: 'success', message: 'Role updated successfully.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function toggleGroup($group, $checked)
    {
        $permissions = Permission::where('group', $group)->pluck('name')->toArray();

        if ($checked) {
            $this->selectedPermissions = array_unique(array_merge(
                $this->selectedPermissions,
                $permissions
            ));
        } else {
            $this->selectedPermissions = array_values(array_diff(
                $this->selectedPermissions,
                $permissions
            ));
        }
    }

    public function toggleSelectMode()
    {
        $this->selectMode = !$this->selectMode;
        if (!$this->selectMode) $this->selectedIds = [];
    }

    public function toggleSelectAll()
    {
        $ids = \Spatie\Permission\Models\Role::where('name', 'like', '%' . $this->search . '%')
            ->pluck('id')->toArray();
        $this->selectedIds = count($this->selectedIds) === count($ids) ? [] : $ids;
    }

    public function confirmBulkDelete()
    {
        $this->authorize('role.delete');
        if (empty($this->selectedIds)) return;
        $this->deleteId = null;
        $this->deleteItemName = count($this->selectedIds) . ' selected roles';
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        $this->authorize('role.delete');
        if (!empty($this->selectedIds)) {
            $count = count($this->selectedIds);
            RoleModel::whereIn('id', $this->selectedIds)->delete();
            $this->dispatch('notify', type: 'success', message: "{$count} roles deleted successfully.");
            $this->selectedIds = [];
            $this->selectMode = false;
            $this->dispatch('hide-delete-modal');
            return;
        }

        if (!$this->deleteId) return;

        RoleModel::findOrFail($this->deleteId)->delete();
        $this->dispatch('notify', type: 'success', message: "Role '{$this->deleteItemName}' deleted successfully.");
        $this->dispatch('hide-delete-modal');
        $this->deleteId = null;
        $this->deleteItemName = '';
    }

    public function confirmDelete($id, $name)
    {
        $this->authorize('role.delete');
        $this->deleteId = $id;
        $this->deleteItemName = $name;
        $this->selectedIds = [];
        $this->selectMode = false;
        $this->dispatch('show-delete-modal');
    }

    private function resetInput()
    {
        $this->roleId = null;
        $this->name = '';
        $this->selectedPermissions = [];
        $this->editMode = false;
    }
}
