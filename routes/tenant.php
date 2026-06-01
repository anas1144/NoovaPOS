<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Web Routes
|--------------------------------------------------------------------------
| All requests arriving at a TENANT subdomain (e.g. acme.noovapos.local)
| pass through these routes after tenancy is initialised.
|
| The React SPA handles all in-app routing (login, /app/*, etc.) — we just
| need to serve the welcome.blade.php shell for every path so BrowserRouter
| can take over.
|
| API calls from tenant subdomains hit routes/api.php automatically via the
| RouteServiceProvider; tenancy is initialised before those handlers fire
| because InitializeTenancyByDomainOrSubdomain is set as the highest-priority
| middleware in TenancyServiceProvider.
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Serve the React SPA shell for every URL on a tenant subdomain.
    // React Router (BrowserRouter) takes over from here.
    Route::get('/{any?}', function () {
        return view('welcome');
    })->where('any', '.*')->name('tenant.spa');

});
