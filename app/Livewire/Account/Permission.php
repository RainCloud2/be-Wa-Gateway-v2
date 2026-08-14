<?php

namespace App\Livewire\Account;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithFileUploads;
use App\Livewire\Traits\WithPerPagePagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PermissionExport;
use App\Exports\PermissionImportTemplate;
use App\Imports\PermissionImport;
use Spatie\Permission\Models\Permission as PermissionModel;

#[Layout('layouts.admin')]
class Permission extends Component
{
    use WithPagination, WithPerPagePagination, WithFileUploads, AuthorizesRequests;

    public $permissionId = null;
    public $name = '';
    public $group = '';
    public $selectedActions = [];
    public $editMode = false;
    public $search = '';
    public $customActions = '';
    public $deleteId = null;
    public $deleteItemName = '';
    public $importFile = null;
    public $selectMode = false;
    public $selectedIds = [];

    public function export()
    {
        return Excel::download(new PermissionExport, 'permissions.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new PermissionImportTemplate, 'permission_import_template.xlsx');
    }

    public function import()
    {
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls,csv']);
        Excel::import(new PermissionImport, $this->importFile->getRealPath());
        $this->dispatch('notify', type: 'success', message: 'Permissions imported successfully.');
        $this->importFile = null;
        $this->dispatch('hide-import-modal');
    }

    public $allActions = ['create', 'read', 'update', 'delete', 'import', 'export'];

    protected function rules()
    {
        if ($this->editMode) {
            return [
                'name' => 'required|string|max:255|unique:permissions,name,' . $this->permissionId,
            ];
        }

        return [
            'group' => 'required|string|max:255|alpha_dash',
            'selectedActions' => 'required|array|min:1',
            'selectedActions.*' => 'string|in:' . implode(',', $this->allActions),
            'customActions' => 'nullable|string|max:255',
        ];
    }

    protected $messages = [
        'group.required' => 'Module/group name is required.',
        'selectedActions.required' => 'Select at least one action.',
        'selectedActions.min' => 'Select at least one action.',
    ];

    public function render()
    {
        $permissions = PermissionModel::select('permissions.*')
            ->leftJoin(
                \DB::raw('(SELECT `group`, MAX(created_at) as group_last FROM permissions GROUP BY `group`) as pg'),
                'permissions.group',
                '=',
                'pg.group'
            )
            ->where('permissions.name', 'like', '%' . $this->search . '%')
            ->orderBy('pg.group_last', 'desc')
            ->orderBy('permissions.created_at', 'desc')
            ->paginate($this->perPage)
            ->withPath(request()->url());

        return view('livewire.account.permission', compact('permissions'));
    }

    public function create()
    {
        $this->resetInput();
        $this->editMode = false;
        $this->dispatch('show-modal');
    }

    public function store()
    {
        $this->authorize('permission.create');
        $this->validate();

        $allActionNames = $this->selectedActions;

        if ($this->customActions) {
            $extra = array_map('trim', explode(',', $this->customActions));
            $extra = array_filter($extra);
            $allActionNames = array_merge($allActionNames, $extra);
        }

        $created = 0;
        foreach ($allActionNames as $action) {
            $name = $this->group . '.' . $action;
            PermissionModel::firstOrCreate(
                ['name' => $name],
                [
                    'group' => $this->group,
                    'label' => ucfirst($action) . ' ' . ucfirst($this->group),
                ]
            );
            $created++;
        }

        $this->dispatch('notify', type: 'success', message: "{$created} permission(s) created successfully.");
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function edit($id)
    {
        $this->resetInput();
        $perm = PermissionModel::findOrFail($id);
        $this->permissionId = $perm->id;
        $this->name = $perm->name;
        $this->editMode = true;
        $this->dispatch('show-modal');
    }

    public function update()
    {
        $this->authorize('permission.update');
        $this->validate();

        $perm = PermissionModel::findOrFail($this->permissionId);
        $perm->update(['name' => $this->name]);

        $this->dispatch('notify', type: 'success', message: 'Permission updated successfully.');
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
        $ids = \Spatie\Permission\Models\Permission::where('name', 'like', '%' . $this->search . '%')
            ->pluck('id')->toArray();
        $this->selectedIds = count($this->selectedIds) === count($ids) ? [] : $ids;
    }

    public function confirmBulkDelete()
    {
        $this->authorize('permission.delete');
        if (empty($this->selectedIds)) return;
        $this->deleteId = null;
        $this->deleteItemName = count($this->selectedIds) . ' selected permissions';
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        $this->authorize('permission.delete');
        if (!empty($this->selectedIds)) {
            $count = count($this->selectedIds);
            PermissionModel::whereIn('id', $this->selectedIds)->delete();
            $this->dispatch('notify', type: 'success', message: "{$count} permissions deleted successfully.");
            $this->selectedIds = [];
            $this->selectMode = false;
            $this->dispatch('hide-delete-modal');
            return;
        }

        if (!$this->deleteId) return;

        PermissionModel::findOrFail($this->deleteId)->delete();
        $this->dispatch('notify', type: 'success', message: "Permission '{$this->deleteItemName}' deleted successfully.");
        $this->dispatch('hide-delete-modal');
        $this->deleteId = null;
        $this->deleteItemName = '';
    }

    public function confirmDelete($id, $name)
    {
        $this->authorize('permission.delete');
        $this->deleteId = $id;
        $this->deleteItemName = $name;
        $this->selectedIds = [];
        $this->selectMode = false;
        $this->dispatch('show-delete-modal');
    }

    public function toggleAllActions()
    {
        if (count($this->selectedActions) === count($this->allActions)) {
            $this->selectedActions = [];
        } else {
            $this->selectedActions = $this->allActions;
        }
    }

    private function resetInput()
    {
        $this->permissionId = null;
        $this->name = '';
        $this->group = '';
        $this->selectedActions = [];
        $this->editMode = false;
        $this->customActions = '';
    }
}
