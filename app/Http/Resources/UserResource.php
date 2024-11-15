<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'username' => $this->username,
            'email_verified_at' => $this->email_verified_at,
            'is_active' => $this->is_active,
            'is_admin' => $this->isAdmin(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'household_id' => $this->household_id,
            'permissions' => $this->permissions->pluck('name'),
        ];
    }
}
