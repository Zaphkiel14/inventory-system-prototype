<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sections extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Units::class, 'section_id');
    }
}
