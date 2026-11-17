<?php

namespace App\Livewire\Configurations;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Units extends Component
{
    public function render(): View
    {
        return view('livewire.configurations.units');
    }
}
