<?php

namespace App\Livewire\Traits;

trait WithPerPagePagination
{
    public $perPage = 10;

    public function updatedPerPage()
    {
        $this->resetPage();
    }
}
