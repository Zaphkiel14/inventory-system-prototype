<?php

namespace App\Livewire\Forms;

use App\Models\StockReceipts;
use Livewire\Form;

class StockReceiptsForm extends Form
{
    public ?StockReceipts $stockReceipt = null;
}
