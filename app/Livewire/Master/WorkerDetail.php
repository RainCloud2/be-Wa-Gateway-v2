<?php

namespace App\Livewire\Master;

use App\Models\Worker as WorkerModel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class WorkerDetail extends Component
{
    public $workerId;

    public $search = '';

    public function mount($worker)
    {
        $this->workerId = $worker;
    }

    public function goTo($id)
    {
        $this->search = '';

        return redirect()->route('master.workers.show', $id);
    }

    public function render()
    {
        $worker = WorkerModel::with('bank')->findOrFail($this->workerId);

        $results = new Collection;
        if (trim($this->search) !== '') {
            $results = WorkerModel::with('bank')
                ->where(function ($q) {
                    $q->where('ops_id', 'like', '%'.$this->search.'%')
                        ->orWhere('full_name', 'like', '%'.$this->search.'%')
                        ->orWhere('nik', 'like', '%'.$this->search.'%');
                })
                ->where('id', '!=', $this->workerId)
                ->limit(10)
                ->get();
        }

        return view('livewire.master.worker-detail', compact('worker', 'results'));
    }
}
