<?php

namespace App\Livewire\Cms;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.cms.dashboard');
    }
}
