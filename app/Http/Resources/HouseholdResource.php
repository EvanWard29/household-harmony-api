<?php

namespace App\Http\Resources;

use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Household */
class HouseholdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->load('users.permissions');

        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'users' => UserResource::collection($this->users),
        ];
    }
}
