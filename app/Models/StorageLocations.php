<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageLocations extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];
}
