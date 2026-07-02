<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Root → marketing landing page on the central/main domain.
// On a tenant subdomain there is no marketing page, so go straight to the app login.
Route::get('/', function (\Illuminate\Http\Request $request) {
    $centralDomains = (array) config('tenancy.central_domains', []);

    if (in_array($request->getHost(), $centralDomains, true)) {
        $cmsMenu = \App\Models\CmsPage::query()
            ->where('status', true)->where('show_in_menu', true)
            ->orderBy('menu_order')->get(['slug', 'title', 'type', 'shop_type']);

        return view('landing', ['cmsMenu' => $cmsMenu]);
    }

    return redirect('/login');
})->name('landing');

// /register-tenant is blocked on the web layer — tenants are invited by the platform admin
Route::get('/register-tenant', function () {
    return redirect('/login');
})->name('register-tenant.redirect');

// Marketing CMS (server-rendered) — must be registered BEFORE the SPA catch-all.
Route::get('/blog', [\App\Http\Controllers\WebCmsController::class, 'blogList'])->name('cms.blog');
Route::get('/blog/{slug}', [\App\Http\Controllers\WebCmsController::class, 'blogPost'])->name('cms.blog.post');
Route::get('/p/{slug}', [\App\Http\Controllers\WebCmsController::class, 'page'])
    ->where('slug', '.*')
    ->name('cms.page');

// Customer self-service kiosk (public, no login) — token identifies the display.
Route::get('/kiosk/{token}', function (string $token) {
    return view('kiosk', ['token' => $token]);
})->name('kiosk');

// Hosted subscription pay page (token-based) — shows the pending checkout so the
// tenant can complete/share payment. Read-only; the super admin confirms it.
Route::get('/billing/pay/{token}', function (string $token) {
    $payment = \App\Models\SubscriptionPayment::query()
        ->where('checkout_token', $token)
        ->first();
    abort_if(! $payment, 404);

    $country = \App\Models\User::query()->where('tenant_id', $payment->tenant_id)->value('country');
    $channel = collect(\App\Http\Controllers\API\PlatformSettingController::channelsFor($country))
        ->firstWhere('key', $payment->checkout_channel);

    return view('billing-pay', ['payment' => $payment, 'channel' => $channel]);
})->name('billing.pay');

// React SPA catch-all — handles /login, /app/*, /forgot-password, etc.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$')->name('spa');

