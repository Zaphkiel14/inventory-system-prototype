<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachments extends Model
{
    protected $fillable = ['attachable_type', 'attachable_id', 'path'];


    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
