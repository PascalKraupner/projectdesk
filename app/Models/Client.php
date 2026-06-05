<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'hourly_rate',
        'currency',
        'share_token',
        'share_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'currency' => Currency::class,
            'share_expires_at' => 'datetime',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function timeLogs(): HasManyThrough
    {
        return $this->hasManyThrough(TimeLog::class, Project::class);
    }
}
