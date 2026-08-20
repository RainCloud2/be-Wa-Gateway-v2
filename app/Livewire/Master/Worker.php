<?php

namespace App\Livewire\Master;

use App\Exports\WorkerExport;
use App\Exports\WorkerImportTemplate;
use App\Imports\WorkerImport;
use App\Livewire\Traits\WithPerPagePagination;
use App\Models\Bank;
use App\Models\Worker as WorkerModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.admin')]
class Worker extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination, WithPerPagePagination;

    public $workerId = null;

    public $opsId = '';

    public $fullName = '';

    public $nik = '';

    public $phoneNumber = '';

    public $ktpPhoto = null;

    public $currentKtpPhoto = '';

    public $bankId = null;

    public $accountNumber = '';

    public $domicileAddress = '';

    public $emergencyContactNumber = '';

    public $emergencyContactName = '';

    public $emergencyContactRelation = '';

    public $selfPhoto = null;

    public $currentSelfPhoto = '';

    public $editMode = false;

    public $search = '';

    public $deleteId = null;

    public $deleteItemName = '';

    public $importFile = null;

    public $selectMode = false;

    public $selectedIds = [];

    public function export()
    {
        return Excel::download(new WorkerExport, 'workers.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new WorkerImportTemplate, 'worker_import_template.xlsx');
    }

    public function import()
    {
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls,csv']);
        Excel::import(new WorkerImport, $this->importFile->getRealPath());
        $this->dispatch('notify', type: 'success', message: 'Workers imported successfully.');
        $this->importFile = null;
        $this->dispatch('hide-import-modal');
    }

    protected function rules()
    {
        return [
            'opsId' => 'required|string|max:255|unique:workers,ops_id,'.$this->workerId,
            'fullName' => 'required|string|max:255',
            'nik' => 'required|string|max:20|unique:workers,nik,'.$this->workerId,
            'phoneNumber' => 'nullable|string|max:20',
            'ktpPhoto' => 'nullable|image|max:2048',
            'bankId' => 'nullable|exists:banks,id',
            'accountNumber' => 'nullable|string|max:50',
            'domicileAddress' => 'nullable|string|max:1000',
            'emergencyContactNumber' => 'nullable|string|max:30',
            'emergencyContactName' => 'nullable|string|max:255',
            'emergencyContactRelation' => 'nullable|string|max:255',
            'selfPhoto' => 'nullable|image|max:2048',
        ];
    }

    public function render()
    {
        $workers = WorkerModel::with('bank')
            ->where(function ($q) {
                $q->where('full_name', 'like', '%'.$this->search.'%')
                    ->orWhere('ops_id', 'like', '%'.$this->search.'%')
                    ->orWhere('nik', 'like', '%'.$this->search.'%');
            })
            ->paginate($this->perPage)
            ->withPath(request()->url());

        $banks = Bank::orderBy('name')->get();

        return view('livewire.master.worker', compact('workers', 'banks'));
    }

    public function create()
    {
        $this->resetInput();
        $this->editMode = false;
        $this->dispatch('show-modal');
    }

    public function store()
    {
        $this->authorize('worker.create');
        $this->validate();

        $worker = WorkerModel::create([
            'ops_id' => $this->opsId,
            'full_name' => $this->fullName,
            'nik' => $this->nik,
            'phone_number' => $this->phoneNumber,
            'ktp_photo' => $this->uploadPhoto($this->ktpPhoto, 'workers/ktp'),
            'bank_id' => $this->bankId,
            'account_number' => $this->accountNumber,
            'domicile_address' => $this->domicileAddress,
            'emergency_contact_number' => $this->emergencyContactNumber,
            'emergency_contact_name' => $this->emergencyContactName,
            'emergency_contact_relation' => $this->emergencyContactRelation,
            'self_photo' => $this->uploadPhoto($this->selfPhoto, 'workers/self'),
        ]);

        $this->dispatch('notify', type: 'success', message: "Worker '{$worker->full_name}' created successfully.");
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function edit($id)
    {
        $this->resetInput();
        $worker = WorkerModel::with('bank')->findOrFail($id);
        $this->workerId = $worker->id;
        $this->opsId = $worker->ops_id;
        $this->fullName = $worker->full_name;
        $this->nik = $worker->nik;
        $this->phoneNumber = $worker->phone_number ?? '';
        $this->currentKtpPhoto = $worker->ktp_photo ?? '';
        $this->bankId = $worker->bank_id;
        $this->accountNumber = $worker->account_number ?? '';
        $this->domicileAddress = $worker->domicile_address ?? '';
        $this->emergencyContactNumber = $worker->emergency_contact_number ?? '';
        $this->emergencyContactName = $worker->emergency_contact_name ?? '';
        $this->emergencyContactRelation = $worker->emergency_contact_relation ?? '';
        $this->currentSelfPhoto = $worker->self_photo ?? '';
        $this->editMode = true;
        $this->dispatch('show-modal');
    }

    public function update()
    {
        $this->authorize('worker.update');
        $this->validate();

        $worker = WorkerModel::findOrFail($this->workerId);

        $data = [
            'ops_id' => $this->opsId,
            'full_name' => $this->fullName,
            'nik' => $this->nik,
            'phone_number' => $this->phoneNumber,
            'bank_id' => $this->bankId,
            'account_number' => $this->accountNumber,
            'domicile_address' => $this->domicileAddress,
            'emergency_contact_number' => $this->emergencyContactNumber,
            'emergency_contact_name' => $this->emergencyContactName,
            'emergency_contact_relation' => $this->emergencyContactRelation,
        ];

        if ($this->ktpPhoto) {
            if ($worker->ktp_photo) {
                Storage::disk('public')->delete($worker->ktp_photo);
            }
            $data['ktp_photo'] = $this->uploadPhoto($this->ktpPhoto, 'workers/ktp');
        }

        if ($this->selfPhoto) {
            if ($worker->self_photo) {
                Storage::disk('public')->delete($worker->self_photo);
            }
            $data['self_photo'] = $this->uploadPhoto($this->selfPhoto, 'workers/self');
        }

        $worker->update($data);

        $this->dispatch('notify', type: 'success', message: "Worker '{$worker->full_name}' updated successfully.");
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    private function uploadPhoto($photo, $folder)
    {
        return $photo ? $photo->store($folder, 'public') : null;
    }

    public function showDetail($id)
    {
        return redirect()->route('master.workers.show', $id);
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
        $ids = WorkerModel::where(function ($q) {
            $q->where('full_name', 'like', '%'.$this->search.'%')
                ->orWhere('ops_id', 'like', '%'.$this->search.'%')
                ->orWhere('nik', 'like', '%'.$this->search.'%');
        })
            ->pluck('id')->toArray();
        $this->selectedIds = count($this->selectedIds) === count($ids) ? [] : $ids;
    }

    public function confirmBulkDelete()
    {
        $this->authorize('worker.delete');
        if (empty($this->selectedIds)) {
            return;
        }
        $this->deleteId = null;
        $this->deleteItemName = count($this->selectedIds).' selected workers';
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        $this->authorize('worker.delete');
        if (! empty($this->selectedIds)) {
            $workers = WorkerModel::whereIn('id', $this->selectedIds)->get();
            $count = count($workers);
            foreach ($workers as $worker) {
                $this->deletePhotos($worker);
            }
            WorkerModel::whereIn('id', $this->selectedIds)->delete();
            $this->dispatch('notify', type: 'success', message: "{$count} workers deleted successfully.");
            $this->selectedIds = [];
            $this->selectMode = false;
            $this->dispatch('hide-delete-modal');

            return;
        }

        if (! $this->deleteId) {
            return;
        }

        $worker = WorkerModel::findOrFail($this->deleteId);
        $this->deletePhotos($worker);
        $worker->delete();
        $this->dispatch('notify', type: 'success', message: "Worker '{$this->deleteItemName}' deleted successfully.");
        $this->dispatch('hide-delete-modal');
        $this->deleteId = null;
        $this->deleteItemName = '';
    }

    public function confirmDelete($id, $name)
    {
        $this->authorize('worker.delete');
        $this->deleteId = $id;
        $this->deleteItemName = $name;
        $this->selectedIds = [];
        $this->selectMode = false;
        $this->dispatch('show-delete-modal');
    }

    private function deletePhotos($worker)
    {
        if ($worker->ktp_photo) {
            Storage::disk('public')->delete($worker->ktp_photo);
        }
        if ($worker->self_photo) {
            Storage::disk('public')->delete($worker->self_photo);
        }
    }

    private function resetInput()
    {
        $this->workerId = null;
        $this->opsId = '';
        $this->fullName = '';
        $this->nik = '';
        $this->phoneNumber = '';
        $this->ktpPhoto = null;
        $this->currentKtpPhoto = '';
        $this->bankId = null;
        $this->accountNumber = '';
        $this->domicileAddress = '';
        $this->emergencyContactNumber = '';
        $this->emergencyContactName = '';
        $this->emergencyContactRelation = '';
        $this->selfPhoto = null;
        $this->currentSelfPhoto = '';
        $this->editMode = false;
    }
}
