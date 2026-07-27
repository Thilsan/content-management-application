<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::query()
            ->with('privileges')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] ??= unique_slug($data['name'], 'roles');

        $role = Role::create(Arr::only($data, ['name', 'slug', 'description']));
        $role->privileges()->sync($data['privileges'] ?? []);

        return RoleResource::make($role->load('privileges')->loadCount('users'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Role $role): RoleResource
    {
        return RoleResource::make($role->load('privileges')->loadCount('users'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $data = $request->validated();

        $role->update(Arr::only($data, ['name', 'slug', 'description']));

        if (array_key_exists('privileges', $data)) {
            $role->privileges()->sync($data['privileges']);
        }

        return RoleResource::make($role->load('privileges')->loadCount('users'));
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Reassign the users holding this role before deleting it.',
            ]);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }
}
