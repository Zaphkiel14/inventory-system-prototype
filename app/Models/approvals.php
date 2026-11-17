<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approvals extends Model
{
    protected $fillable = [
        'requisition_id',
        'user_id',
        'decision',
        'remarks',
        'decided_at',
    ];
}
