<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDueNotificationState extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'task_id',
        'read_at',
        'cleared_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
