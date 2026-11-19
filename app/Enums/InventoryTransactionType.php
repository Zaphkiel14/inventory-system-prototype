<?php

namespace App\Enums;

enum InventoryTransactionType: int
{
    case Receipt = 1;
    case Issuance = 2;
    case Adjustment = 3;
}
