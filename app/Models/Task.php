<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Task extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'user_id',
        'task_type_id',
        'name',
        'description',
        'status',
        'priority',
        'original_due_date',
        'current_due_date',
        'postponed_count',
        'postponed_date_1',
        'postponed_date_2',
        'postponed_date_3',
        'is_being_viewed',
        'last_viewed_at',
        'completed_at'
    ];

    protected $casts = [
        'original_due_date' => 'datetime',
        'current_due_date' => 'datetime',
        'postponed_date_1' => 'datetime',
        'postponed_date_2' => 'datetime',
        'postponed_date_3' => 'datetime',
        'last_viewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_being_viewed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }
}