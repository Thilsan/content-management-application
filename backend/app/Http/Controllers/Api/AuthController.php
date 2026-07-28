<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Exchange credentials for a Sanctum token',
        description: 'Returns the signed in user together with the flattened list of privileges '.
            'their roles grant, which is what the React app uses to decide which screens to offer. '.
            'The route allows six attempts per minute per client.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@cms.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: '12345'),
                    new OA\Property(
                        property: 'device_name',
                        description: 'Names the issued token. Defaults to "web".',
                        type: 'string',
                        example: 'web',
                    ),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Signed in',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '4|Xy7pQ2sR...'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Credentials did not match',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
            new OA\Response(response: 429, description: 'Too many attempts'),
        ],
    )]
    public function login(LoginRequest $request): UserResource
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'web')->plainTextToken;

        return UserResource::make($user->load('roles.privileges'))
            ->additional(['token' => $token]);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'The user behind the current token',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current user',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
        ],
    )]
    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user()->load('roles.privileges'));
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Revoke the token used for this request',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Signed out',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
