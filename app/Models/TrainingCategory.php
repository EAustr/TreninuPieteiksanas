<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
    ];

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'category_id');
    }
}
