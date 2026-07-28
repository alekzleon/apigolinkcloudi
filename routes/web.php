<?php

declare(strict_types=1);

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{shortCode}', RedirectController::class)
    ->where('shortCode', '^(?!api$|up$|storage$|sanctum$|admin$|login$|register$|logout$|dashboard$|links$|analytics$|health$|status$|password$|forgot-password$|reset-password$)[A-Za-z0-9_-]{3,50}$')
    ->middleware('throttle:redirects');
