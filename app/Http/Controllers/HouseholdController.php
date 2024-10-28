<?php

namespace App\Http\Controllers;

use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use Illuminate\Http\Request;

class HouseholdController
{
    /**
     * Retrieve the user's household
     */
    public function show(Request $request): HouseholdResource
    {
        return new HouseholdResource($request->user()->household);
    }

    /**
     * Update a user's household
     */
    public function update(Request $request, Household $household)
    {
        $data = $request->validate([
            'name' => ['required'],
        ]);

        $household->update($data);

        return $household;
    }
}
