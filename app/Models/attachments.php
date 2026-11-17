<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachments extends Model
{
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'path',
    ];
}
