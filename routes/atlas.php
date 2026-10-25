<?php

use App\Http\Controllers\AtlasController;
use Illuminate\Support\Facades\Route;

/*
| The atlas page and its data. These routes run without the web middleware group: an anonymous
| reader gets the open edition with no cookie at all. A signed-in reader's session cookie starts
| the session (SessionIfCookie), so the server can serve their plan.
*/
Route::get('/', [AtlasController::class, 'index'])->name('atlas');
Route::get('/app/live', [AtlasController::class, 'live'])->name('atlas.live');
Route::get('/app/audio/{file}', [AtlasController::class, 'audio'])->name('atlas.audio');
