<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisitions extends Model
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

    public function approvals(): HasMany
    {
        return $this->hasMany(Approvals::class, 'requisition_id');
    }
}
