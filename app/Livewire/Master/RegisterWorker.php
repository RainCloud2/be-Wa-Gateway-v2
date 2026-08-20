<?php

namespace App\Livewire\Master;

use App\Models\Bank;
use App\Models\Worker as WorkerModel;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.guest')]
class RegisterWorker extends Component
{
    use WithFileUploads;

    public $opsId = '';

    public $fullName = '';

    public $nik = '';

    public $phoneNumber = '';

    public $ktpPhoto = null;

    public $bankId = null;

    public $accountNumber = '';

    public $domicileAddress = '';

    public $emergencyContactNumber = '';

    public $emergencyContactName = '';

    public $emergencyContactRelation = '';

    public $selfPhoto = null;

    public $success = false;

    protected function rules()
    {
        return [
            'opsId' => 'required|string|max:255|unique:workers,ops_id',
            'fullName' => 'required|string|max:255',
            'nik' => 'required|string|max:20|unique:workers,nik',
            'phoneNumber' => 'required|string|max:20',
            'ktpPhoto' => 'required|image|max:2048',
            'bankId' => 'required|exists:banks,id',
            'accountNumber' => 'required|string|max:50',
            'domicileAddress' => 'required|string|max:1000',
            'emergencyContactNumber' => 'required|string|max:30',
            'emergencyContactName' => 'required|string|max:255',
            'emergencyContactRelation' => 'required|string|max:255',
            'selfPhoto' => 'required|image|max:2048',
        ];
    }

    public function render()
    {
        $banks = Bank::orderBy('name')->get();

        return view('livewire.master.register-worker', compact('banks'));
    }

    public function save()
    {
        $this->validate();

        WorkerModel::create([
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

        $this->success = true;
        $this->resetInput();
    }

    private function uploadPhoto($photo, $folder)
    {
        return $photo ? $photo->store($folder, 'public') : null;
    }

    private function resetInput()
    {
        $this->opsId = '';
        $this->fullName = '';
        $this->nik = '';
        $this->phoneNumber = '';
        $this->ktpPhoto = null;
        $this->bankId = null;
        $this->accountNumber = '';
        $this->domicileAddress = '';
        $this->emergencyContactNumber = '';
        $this->emergencyContactName = '';
        $this->emergencyContactRelation = '';
        $this->selfPhoto = null;
    }
}
