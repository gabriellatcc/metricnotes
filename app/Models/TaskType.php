<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaskType extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)->withTimestamps();
    }
}
