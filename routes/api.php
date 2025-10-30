<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/import/variables', [\App\Http\Controllers\DocImportController::class, 'previewVariables']);

Route::get('/templates/{id}', [\App\Http\Controllers\DocImportController::class, 'show']);
