<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->with('roles.privileges')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = addcslashes(trim((string) $request->input('search')), '%_\\');

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create(Arr::only($data, ['name', 'email', 'password']));
        $user->roles()->sync($data['roles'] ?? []);

        return UserResource::make($user->load('roles.privileges'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return UserResource::make($user->load('roles.privileges'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update(Arr::only($data, ['name', 'email', 'password']));

        if (array_key_exists('roles', $data)) {
            $user->roles()->sync($data['roles']);
        }

        return UserResource::make($user->load('roles.privileges'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete the account you are signed in with.',
            ]);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
