<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\Household;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupController
{
    use AuthorizesRequests;

    /**
     * Get the groups of the household
     *
     * @return AnonymousResourceCollection<GroupResource>
     */
    public function index(Household $household): AnonymousResourceCollection
    {
        return GroupResource::collection($household->groups);
    }

    /**
     * Create a new group for the household
     */
    public function store(GroupRequest $request, Household $household): GroupResource
    {
        $this->authorize('create', Group::class);

        $group = Group::make($request->validated());

        $group->household()->associate($household);

        $group->save();

        return new GroupResource($group);
    }

    /**
     * Get the requested group
     */
    public function show(Household $household, Group $group): GroupResource
    {
        return new GroupResource($group);
    }

    /**
     * Edit the requested group
     */
    public function update(GroupRequest $request, Household $household, Group $group): GroupResource
    {
        $this->authorize('update', Group::class);

        $group->update($request->validated());

        return new GroupResource($group);
    }

    /**
     * Delete the requested group
     */
    public function destroy(Household $household, Group $group)
    {
        $this->authorize('delete', Group::class);

        $group->delete();

        return response()->json();
    }
}
