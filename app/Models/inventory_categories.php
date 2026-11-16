<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class inventory_categories extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];
}
