<?php

use App\Http\Controllers\ApiController;
use App\Http\Middleware\ApiKeyAuth;
use Illuminate\Support\Facades\Route;

// https://atlas.emmaconsulting.space/v1/...  (Authorization: Bearer <key>)
Route::middleware(ApiKeyAuth::class)->group(function () {
    Route::get('/countries', [ApiController::class, 'countries']);
    Route::get('/countries/{iso3}/file', [ApiController::class, 'countryFile'])->where('iso3', '[A-Za-z]{3}');
    Route::get('/programs', [ApiController::class, 'programs']);
    Route::get('/organizations', [ApiController::class, 'organizations']);
    Route::get('/engagements', [ApiController::class, 'engagements']);
    Route::get('/briefings', [ApiController::class, 'briefings']);
    Route::get('/briefings/{date}', [ApiController::class, 'briefing'])->where('date', 'latest|\d{4}-\d{2}-\d{2}');
    Route::get('/events', [ApiController::class, 'events']);
});
