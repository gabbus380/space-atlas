<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AccessController;
use App\Http\Controllers\Admin\ApiRequestController;
use App\Http\Controllers\Admin\DirectoryController;
use App\Http\Controllers\Admin\PeopleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiOrderController;
use App\Http\Controllers\AtlasController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\EmailPreferenceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

// public pages
Route::view('/pricing', 'pricing')->name('pricing');
Route::get('/api-order', [ApiOrderController::class, 'create'])->name('api-order');
Route::post('/api-order', [ApiOrderController::class, 'store'])->middleware('throttle:6,1');
Route::get('/api-order/received', [ApiOrderController::class, 'done'])->name('api-order.done');
Route::get('/legal', fn () => response()->file(config('atlas.html_path').'/legal.html', ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']))->name('legal');
Route::redirect('/terms', '/legal#terms');
Route::redirect('/privacy', '/legal#privacy');
Route::redirect('/refunds', '/legal#refunds');
Route::get('/withdraw', [WithdrawalController::class, 'create'])->name('withdraw');
Route::post('/withdraw', [WithdrawalController::class, 'store'])->middleware('throttle:6,1');
Route::get('/invite/{token}', [TeamController::class, 'accept'])->name('invite.accept');
Route::match(['get', 'post'], '/email/preferences/{user}/{list}', [EmailPreferenceController::class, 'update'])
    ->middleware('signed')
    ->name('email.unsubscribe');

// sign up and sign in
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/login/{provider}', [SocialController::class, 'redirect'])->whereIn('provider', array_keys(SocialController::PROVIDERS));
    Route::match(['get', 'post'], '/login/{provider}/callback', [SocialController::class, 'callback'])->whereIn('provider', array_keys(SocialController::PROVIDERS))->middleware('throttle:12,1');
    Route::get('/forgot-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    // inside the atlas page
    Route::post('/app/unlock/{iso3}', [AtlasController::class, 'unlock'])->middleware('throttle:30,1');
    Route::put('/app/watchlist', [AtlasController::class, 'saveWatchlist']);
    Route::post('/app/watchlist/scope', [AtlasController::class, 'scope']);
    Route::put('/app/notes/{iso3}', [AtlasController::class, 'saveNote']);
    Route::delete('/app/notes/{iso3}', [AtlasController::class, 'deleteNote']);
    Route::get('/app/me', [AtlasController::class, 'me']);

    // account
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::post('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::post('/account/coupon', [AccountController::class, 'coupon'])->name('account.coupon');
    Route::get('/account/api', [AccountController::class, 'apiDocs'])->name('account.api');
    Route::post('/account/api-keys/{key}/refresh', [AccountController::class, 'refreshApiKey'])->name('account.api.refresh');
    Route::post('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::get('/account/export', [AccountController::class, 'export'])->name('account.export');
    Route::post('/account/delete', [AccountController::class, 'destroy'])->name('account.delete');

    // plans and payments (Paddle is the reseller and merchant of record)
    Route::middleware('verified')->group(function () {
        Route::get('/subscribe/personal', [BillingController::class, 'personal'])->name('subscribe.personal');
        Route::get('/subscribe/teams', [BillingController::class, 'teamsForm'])->name('subscribe.teams');
        Route::post('/subscribe/teams', [BillingController::class, 'teamsCheckout']);
    });
    Route::get('/billing/done', [BillingController::class, 'done'])->name('billing.done');
    Route::post('/billing/yearly', [BillingController::class, 'yearly'])->name('billing.yearly');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/payment-method', [BillingController::class, 'paymentMethod'])->name('billing.payment');
    Route::get('/billing/invoice/{transaction}', [BillingController::class, 'invoice'])->name('billing.invoice');

    // team
    Route::get('/team', [TeamController::class, 'show'])->name('team');
    Route::post('/team/invitations', [TeamController::class, 'invite'])->middleware('throttle:30,1')->name('team.invite');
    Route::delete('/team/invitations/{invitation}', [TeamController::class, 'revoke'])->name('team.revoke');
    Route::delete('/team/members/{member}', [TeamController::class, 'remove'])->name('team.remove');
    Route::post('/team/seats', [TeamController::class, 'seats'])->name('team.seats');
    Route::post('/team/name', [TeamController::class, 'rename'])->name('team.rename');
    Route::post('/team/leave', [TeamController::class, 'leave'])->name('team.leave');

    // EMMA staff. Each ability returns 404 to the roles that do not have it.
    Route::middleware(EnsureStaff::class.':dashboard')->prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'show'])->name('admin');

        Route::middleware(EnsureStaff::class.':customers')->group(function () {
            Route::get('/users', [PeopleController::class, 'users'])->name('admin.users');
            Route::get('/users/{user}', [PeopleController::class, 'user'])->name('admin.users.show');
            Route::post('/users/{user}/grant', [PeopleController::class, 'grant'])->name('admin.users.grant');
            Route::post('/users/{user}/revoke', [PeopleController::class, 'revoke'])->name('admin.users.revoke');
            Route::post('/users/{user}/verify', [PeopleController::class, 'verify'])->name('admin.users.verify');
            Route::post('/users/{user}/convert-team', [PeopleController::class, 'convertToTeam'])->name('admin.users.convert-team');
            Route::delete('/users/{user}', [PeopleController::class, 'destroy'])->name('admin.users.destroy');
            Route::get('/teams', [PeopleController::class, 'teams'])->name('admin.teams');
            Route::get('/teams/{team}', [PeopleController::class, 'team'])->name('admin.teams.show');
            Route::post('/teams/{team}/grant', [PeopleController::class, 'teamGrant'])->name('admin.teams.grant');
            Route::post('/teams/{team}/revoke', [PeopleController::class, 'teamRevoke'])->name('admin.teams.revoke');
            Route::get('/withdrawals', [PeopleController::class, 'withdrawals'])->name('admin.withdrawals');
            Route::post('/withdrawals/{withdrawal}', [PeopleController::class, 'withdrawal'])->name('admin.withdrawals.update');
            Route::get('/api-requests', [ApiRequestController::class, 'index'])->name('admin.api-requests');
            Route::get('/api-requests/{apiRequest}', [ApiRequestController::class, 'show'])->name('admin.api-requests.show');
            Route::post('/api-requests/{apiRequest}', [ApiRequestController::class, 'update'])->name('admin.api-requests.update');
            Route::get('/coupons', [AccessController::class, 'coupons'])->name('admin.coupons');
            Route::post('/coupons', [AccessController::class, 'storeCoupon'])->name('admin.coupons.store');
            Route::post('/coupons/{coupon}/toggle', [AccessController::class, 'toggleCoupon'])->name('admin.coupons.toggle');
        });

        Route::middleware(EnsureStaff::class.':roles')->group(function () {
            Route::post('/users/{user}/role', [PeopleController::class, 'role'])->name('admin.users.role');
        });

        Route::middleware(EnsureStaff::class.':keys')->group(function () {
            Route::get('/api-keys', [AccessController::class, 'keys'])->name('admin.keys');
            Route::get('/api-keys/organizations', [AccessController::class, 'organizations'])->name('admin.keys.organizations');
            Route::post('/api-keys', [AccessController::class, 'issueKey'])->name('admin.keys.store');
            Route::post('/api-keys/{key}/toggle', [AccessController::class, 'toggleKey'])->name('admin.keys.toggle');
            Route::post('/api-keys/{key}/refresh', [AccessController::class, 'refreshKey'])->name('admin.keys.refresh');
            Route::post('/api-requests/{apiRequest}/key', [ApiRequestController::class, 'issueKey'])->name('admin.api-requests.key');
        });

        Route::middleware(EnsureStaff::class.':settings')->group(function () {
            Route::get('/connectors', [SettingsController::class, 'edit'])->name('admin.connectors');
            Route::post('/connectors', [SettingsController::class, 'update'])->name('admin.connectors.update');
        });

        Route::middleware(EnsureStaff::class.':data')->group(function () {
            Route::get('/directory', [DirectoryController::class, 'index'])->name('admin.directory');
            Route::get('/directory/{org}', [DirectoryController::class, 'edit'])->where('org', '.*')->name('admin.directory.edit');
            Route::post('/directory/{org}', [DirectoryController::class, 'update'])->where('org', '.*')->name('admin.directory.update');
        });
    });
});
