<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Base;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback();
Route::get('/account', [Base\IndexController::class, 'index'])
  ->withoutMiddleware(RequireTwoFactorAuthentication::class)
  ->name('account');

Route::get('/locales/locale.json', Base\LocaleController::class)
  ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
  ->where('namespace', '.*');

/*
|--------------------------------------------------------------------------
| Panasaurus Health Endpoint
|--------------------------------------------------------------------------
|
| Endpoint: /api/health — public, unauthenticated. Suitable for uptime
| monitors and container health checks. Never leaks internals.
|
*/

Route::get('/api/health', [\Pterodactyl\Http\Controllers\Api\Client\HealthController::class])
  ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
  ->name('health');

Route::get('/{react}', [Base\IndexController::class, 'index'])
  ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
