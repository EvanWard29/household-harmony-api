<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */ class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'deadline' => $this->deadline,
            'group' => new GroupResource($this->group),

            'owner_id' => $this->owner_id,
            'household_id' => $this->household_id,
            'assigned' => UserResource::collection($this->assigned),
        ];
    }
}
