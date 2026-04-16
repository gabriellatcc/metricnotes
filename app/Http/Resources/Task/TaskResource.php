<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,

            'status'=>$this->status,
            'priority'=>$this->priority,

            'is_being_viewed'=> $this->is_being_viewed,
            'last_viewed_at'=> $this->last_viewed_at,
            'completed_at'=> $this->completed_at,

            'original_due_date'=> $this->original_due_date,
            'current_due_date'=> $this->current_due_date,
            'postponed_count' => $this->postponed_count,
            'postponed_date_1'=> $this->postponed_date_1,
            'postponed_date_2'=> $this-> postponed_date_2,
            'postponed_date_3'=> $this->postponed_date_3,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user_id' => $this->user_id,
            'task_type_id' => $this->task_type_id,
        ];
    }
}