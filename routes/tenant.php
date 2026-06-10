<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\InitializeTenancyForApi;

/*
|--------------------------------------------------------------------------
| Tenant Web Routes
|--------------------------------------------------------------------------
| Serves the React SPA shell for every URL on a tenant subdomain.
| React Router (BrowserRouter) handles /login, /app/*, etc. client-side.
|
| InitializeTenancyForApi handles the subdomain → tenant lookup.
| Central domains pass through untouched; unknown subdomains get 404.
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyForApi::class,
])->group(function () {

    Route::get('/{any?}', function () {
        return view('welcome');
    })->where('any', '.*')->name('tenant.spa');

});
