<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class attachments extends Model
{
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'path',
    ];
}
