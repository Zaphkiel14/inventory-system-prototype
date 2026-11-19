<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\StockReceiptsForm;
use Livewire\Component;

class ReceivingCreateForm extends Component
{
    public StockReceiptsForm $form;

    public function render()
    {
        return view('livewire.inventory.receiving-create-form');
    }
}
