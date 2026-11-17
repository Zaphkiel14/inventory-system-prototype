<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class requisitions extends Model
{
    protected $fillable = [
        'request_number',
        'user_id',
        'department_id',
        'section_id',
        'unit_id',
        'status',
        'purpose',
        'submitted_at',
    ];
}
