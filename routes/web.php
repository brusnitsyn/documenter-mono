<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return \Inertia\Inertia::render('Index');
});

Route::get('/contract-generator/{template}',
    [\App\Http\Controllers\DocumentController::class, 'show'])
    ->name('contract-generator.show');

Route::post('/contract-generator/update-preview',
    [\App\Http\Controllers\DocumentController::class, 'updatePreview'])
    ->name('contract-generator.update-preview');
