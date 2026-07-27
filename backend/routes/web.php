<?php

use Illuminate\Support\Facades\Route;

/*
 * This application only serves the API and its documentation. There is no Blade
 * interface, so the root sends visitors to the Swagger UI rather than a page
 * that says nothing.
 */
Route::redirect('/', '/api/documentation');
