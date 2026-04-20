<?php

namespace App\Http\Resources\Note;

use App\Http\Resources\NoteType\NoteTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user_id' => $this->user_id,
            'note_types' => NoteTypeResource::collection($this->whenLoaded('noteTypes')),
        ];
    }
}
