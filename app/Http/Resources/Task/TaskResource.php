<?php

namespace App\Http\Resources\Task;

use App\Http\Resources\Tip\TipResource;
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

            'status' => $this->status,
            'priority' => $this->priority,

            'is_being_viewed' => $this->is_being_viewed,
            'last_viewed_at' => $this->last_viewed_at,
            'total_view_time_seconds' => $this->total_view_time_seconds,
            'completed_at' => $this->completed_at,

            'original_due_date' => $this->original_due_date,
            'current_due_date' => $this->current_due_date,
            'postponed_count' => $this->postponed_count,
            'postponed_date_1' => $this->postponed_date_1,
            'postponed_date_2' => $this->postponed_date_2,
            'postponed_date_3' => $this->postponed_date_3,
            /** Indica se regras 3.1–3.4 se aplicam (false para prazo inicial “longo”, exceção 3.5). */
            'postpone_strict_rules_apply' => $this->postponeStrictRulesApply(),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'user_id' => $this->user_id,
            'tips' => TipResource::collection($this->whenLoaded('tips')),
        ];
    }
}
