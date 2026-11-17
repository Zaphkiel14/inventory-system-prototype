<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Units extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Sections::class, 'section_id');
    }
}
