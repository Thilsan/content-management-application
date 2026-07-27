<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PrivilegeController;
use App\Http\Controllers\Api\PublicContentController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

/*
 * Read only endpoints the public React site consumes. Drafts and pages whose
 * publish date has not arrived yet are filtered out by the visible() scope.
 */
Route::prefix('public')->group(function (): void {
    Route::get('menu', [PublicContentController::class, 'menu']);
    Route::get('pages', [PublicContentController::class, 'pages']);
    Route::get('pages/{slug}', [PublicContentController::class, 'page']);
});

/*
 * Everything below needs a Sanctum token. The can: middleware resolves against
 * the privileges table through the gate registered in AppServiceProvider.
 */
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::prefix('pages')->group(function (): void {
        Route::get('/', [PageController::class, 'index'])->middleware('can:pages.view');
        Route::post('/', [PageController::class, 'store'])->middleware('can:pages.create');
        Route::get('trashed', [PageController::class, 'trashed'])->middleware('can:pages.restore');

        Route::prefix('{page}')->whereNumber('page')->group(function (): void {
            Route::get('/', [PageController::class, 'show'])->middleware('can:pages.view');
            // POST is accepted alongside PUT so multipart uploads can reach this
            // action; PHP only populates uploaded files on POST requests.
            Route::match(['put', 'post'], '/', [PageController::class, 'update'])->middleware('can:pages.update');
            Route::delete('/', [PageController::class, 'destroy'])->middleware('can:pages.delete');
            // withTrashed lets route binding resolve a page that is in the trash.
            Route::post('restore', [PageController::class, 'restore'])->middleware('can:pages.restore')->withTrashed();
            Route::delete('force', [PageController::class, 'forceDestroy'])->middleware('can:pages.restore')->withTrashed();
        });
    });

    Route::prefix('menus')->group(function (): void {
        Route::get('/', [MenuController::class, 'index'])->middleware('can:menus.view');
        Route::post('/', [MenuController::class, 'store'])->middleware('can:menus.create');
        Route::post('reorder', [MenuController::class, 'reorder'])->middleware('can:menus.reorder');
        Route::put('{menu}', [MenuController::class, 'update'])->middleware('can:menus.update');
        Route::delete('{menu}', [MenuController::class, 'destroy'])->middleware('can:menus.delete');
    });

    Route::prefix('users')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->middleware('can:users.view');
        Route::post('/', [UserController::class, 'store'])->middleware('can:users.create');
        Route::get('{user}', [UserController::class, 'show'])->middleware('can:users.view');
        Route::put('{user}', [UserController::class, 'update'])->middleware('can:users.update');
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('can:users.delete');
    });

    Route::prefix('roles')->group(function (): void {
        Route::get('/', [RoleController::class, 'index'])->middleware('can:roles.view');
        Route::post('/', [RoleController::class, 'store'])->middleware('can:roles.create');
        Route::get('{role}', [RoleController::class, 'show'])->middleware('can:roles.view');
        Route::put('{role}', [RoleController::class, 'update'])->middleware('can:roles.update');
        Route::delete('{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete');
    });

    Route::prefix('privileges')->group(function (): void {
        Route::get('/', [PrivilegeController::class, 'index'])->middleware('can:privileges.view');
        Route::post('/', [PrivilegeController::class, 'store'])->middleware('can:privileges.create');
        Route::put('{privilege}', [PrivilegeController::class, 'update'])->middleware('can:privileges.update');
        Route::delete('{privilege}', [PrivilegeController::class, 'destroy'])->middleware('can:privileges.delete');
    });
});
