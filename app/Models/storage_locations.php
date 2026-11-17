<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class storage_locations extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];
}
