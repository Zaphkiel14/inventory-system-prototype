<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departments extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Sections::class, 'department_id');
    }
}
