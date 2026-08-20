<?php

namespace App\Livewire\Master;

use App\Exports\BankExport;
use App\Exports\BankImportTemplate;
use App\Imports\BankImport;
use App\Livewire\Traits\WithPerPagePagination;
use App\Models\Bank as BankModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.admin')]
class Bank extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination, WithPerPagePagination;

    public $bankId = null;

    public $name = '';

    public $initial = '';

    public $description = '';

    public $editMode = false;

    public $search = '';

    public $deleteId = null;

    public $deleteItemName = '';

    public $importFile = null;

    public $selectMode = false;

    public $selectedIds = [];

    public function export()
    {
        return Excel::download(new BankExport, 'banks.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new BankImportTemplate, 'bank_import_template.xlsx');
    }

    public function import()
    {
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls,csv']);
        Excel::import(new BankImport, $this->importFile->getRealPath());
        $this->dispatch('notify', type: 'success', message: 'Banks imported successfully.');
        $this->importFile = null;
        $this->dispatch('hide-import-modal');
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:banks,name,'.$this->bankId,
            'initial' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function render()
    {
        $banks = BankModel::where(function ($q) {
            $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('initial', 'like', '%'.$this->search.'%');
        })
            ->paginate($this->perPage)
            ->withPath(request()->url());

        return view('livewire.master.bank', compact('banks'));
    }

    public function create()
    {
        $this->resetInput();
        $this->editMode = false;
        $this->dispatch('show-modal');
    }

    public function store()
    {
        $this->authorize('bank.create');
        $this->validate();

        BankModel::create([
            'name' => $this->name,
            'initial' => $this->initial,
            'description' => $this->description,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Bank created successfully.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function edit($id)
    {
        $this->resetInput();
        $bank = BankModel::findOrFail($id);
        $this->bankId = $bank->id;
        $this->name = $bank->name;
        $this->initial = $bank->initial;
        $this->description = $bank->description;
        $this->editMode = true;
        $this->dispatch('show-modal');
    }

    public function update()
    {
        $this->authorize('bank.update');
        $this->validate();

        $bank = BankModel::findOrFail($this->bankId);
        $bank->update([
            'name' => $this->name,
            'initial' => $this->initial,
            'description' => $this->description,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Bank updated successfully.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function toggleSelectMode()
    {
        $this->selectMode = ! $this->selectMode;
        if (! $this->selectMode) {
            $this->selectedIds = [];
        }
    }

    public function toggleSelectAll()
    {
        $ids = BankModel::where(function ($q) {
            $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('initial', 'like', '%'.$this->search.'%');
        })
            ->pluck('id')->toArray();
        $this->selectedIds = count($this->selectedIds) === count($ids) ? [] : $ids;
    }

    public function confirmBulkDelete()
    {
        $this->authorize('bank.delete');
        if (empty($this->selectedIds)) {
            return;
        }
        $this->deleteId = null;
        $this->deleteItemName = count($this->selectedIds).' selected banks';
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        $this->authorize('bank.delete');
        if (! empty($this->selectedIds)) {
            $count = count($this->selectedIds);
            BankModel::whereIn('id', $this->selectedIds)->delete();
            $this->dispatch('notify', type: 'success', message: "{$count} banks deleted successfully.");
            $this->selectedIds = [];
            $this->selectMode = false;
            $this->dispatch('hide-delete-modal');

            return;
        }

        if (! $this->deleteId) {
            return;
        }

        BankModel::findOrFail($this->deleteId)->delete();
        $this->dispatch('notify', type: 'success', message: "Bank '{$this->deleteItemName}' deleted successfully.");
        $this->dispatch('hide-delete-modal');
        $this->deleteId = null;
        $this->deleteItemName = '';
    }

    public function confirmDelete($id, $name)
    {
        $this->authorize('bank.delete');
        $this->deleteId = $id;
        $this->deleteItemName = $name;
        $this->selectedIds = [];
        $this->selectMode = false;
        $this->dispatch('show-delete-modal');
    }

    private function resetInput()
    {
        $this->bankId = null;
        $this->name = '';
        $this->initial = '';
        $this->description = '';
        $this->editMode = false;
    }
}
