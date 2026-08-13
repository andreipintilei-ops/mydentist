<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Visual diff between the Blade UI mirrors and the real shadcn-vue components.
// Never exposed outside local development.
if (app()->environment('local')) {
    Route::view('_ui-parity', 'dev.ui-parity');
}

require __DIR__.'/settings.php';
