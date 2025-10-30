<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\WorkspaceController::class, 'showTemplates'])
    ->name('workspace.show-templates');

Route::post('/editor', [\App\Http\Controllers\EditorController::class, 'save'])
    ->name('editor.save');

Route::get('/contract-generator/{template}',
    [\App\Http\Controllers\DocumentController::class, 'show'])
    ->name('contract-generator.show');

Route::post('/contract-generator/{template}/preview',
    [\App\Http\Controllers\DocumentController::class, 'preview'])
    ->name('contract-generator.preview');

Route::post('/contract-generator/{template}/download',
    [\App\Http\Controllers\DocumentController::class, 'download'])
    ->name('contract-generator.download');

Route::post('/contract-generator/update-preview',
    [\App\Http\Controllers\DocumentController::class, 'updatePreview'])
    ->name('contract-generator.update-preview');

Route::get('/editor', [\App\Http\Controllers\DocumentEditorController::class, 'editor'])
    ->name('contract-generator.editor');

Route::post('/templates/import', [\App\Http\Controllers\DocImportController::class, 'store'])
    ->name('templates.import');

Route::post('/templates/update', [\App\Http\Controllers\DocImportController::class, 'update'])
    ->name('templates.update');
