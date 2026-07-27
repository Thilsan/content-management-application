<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="CMS API",
 *     version="1.0.0",
 *     description="REST API for a small content management application. Pages are grouped
 *         under a sortable, nestable menu and published to a React front end. Every write
 *         endpoint is guarded by a privilege stored in the database and granted through
 *         roles, so permissions are data rather than hard coded role names."
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Application server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Personal access token returned by POST /api/auth/login. Send it as
 *         an Authorization: Bearer {token} header."
 * )
 *
 * @OA\Tag(name="Auth", description="Sign in, sign out and inspect the current user")
 * @OA\Tag(name="Public", description="Unauthenticated endpoints the React site reads")
 * @OA\Tag(name="Pages", description="Page management, trash and restore")
 * @OA\Tag(name="Menus", description="Nestable menu management and reordering")
 * @OA\Tag(name="Users", description="User administration")
 * @OA\Tag(name="Roles", description="Role administration and privilege assignment")
 * @OA\Tag(name="Privileges", description="Privilege catalogue")
 */
abstract class Controller
{
    //
}
