<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Privilege\StorePrivilegeRequest;
use App\Http\Requests\Privilege\UpdatePrivilegeRequest;
use App\Http\Resources\PrivilegeResource;
use App\Models\Privilege;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrivilegeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $privileges = Privilege::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        return PrivilegeResource::collection($privileges);
    }

    public function store(StorePrivilegeRequest $request): JsonResponse
    {
        $privilege = Privilege::create($request->validated());

        return PrivilegeResource::make($privilege)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePrivilegeRequest $request, Privilege $privilege): PrivilegeResource
    {
        $privilege->update($request->validated());

        return PrivilegeResource::make($privilege);
    }

    public function destroy(Privilege $privilege): JsonResponse
    {
        $privilege->delete();

        return response()->json(['message' => 'Privilege deleted.']);
    }
}
