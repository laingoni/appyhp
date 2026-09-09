<?php

use Alliswell\Appyhp\Http\Controllers\StudioController;
use Alliswell\Appyhp\Http\Controllers\DirectoryController;
use Alliswell\Appyhp\Http\Controllers\SetupController;
use Alliswell\Appyhp\Http\Controllers\WorkflowController;
use Alliswell\Appyhp\Http\Controllers\AiController;
use Alliswell\Appyhp\Http\Middleware\StudioSession;
use Alliswell\Appyhp\Http\Middleware\ThrottleAiRequests;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/appyhp/studio', StudioController::class)->middleware([EncryptCookies::class, StudioSession::class])->name('appyhp.studio');

Route::middleware([EncryptCookies::class, StudioSession::class, ValidateCsrfToken::class])->prefix('/appyhp/api/ai')->name('appyhp.ai.')->group(function (): void {
    Route::get('/settings', [AiController::class, 'settings'])->name('settings');
    Route::put('/settings', [AiController::class, 'updateSettings'])->name('settings.update');
    Route::post('/test', [AiController::class, 'test'])->middleware(ThrottleAiRequests::class . ':10,1')->name('test');
    Route::post('/generate', [AiController::class, 'generate'])->middleware(ThrottleAiRequests::class . ':30,1')->name('generate');
    Route::post('/file', [AiController::class, 'writeFile'])->name('file');
});

Route::get('/appyhp/api/setup', [SetupController::class, 'show'])->name('appyhp.setup.show');
Route::put('/appyhp/api/setup', [SetupController::class, 'update'])->name('appyhp.setup.update');

Route::get('/appyhp/api/workflows', [WorkflowController::class, 'index'])->name('appyhp.workflows.index');
Route::put('/appyhp/api/workflows', [WorkflowController::class, 'store'])->name('appyhp.workflows.store');

Route::prefix('/appyhp/api/directories')->name('appyhp.directories.')->group(function (): void {
    Route::get('/', [DirectoryController::class, 'index'])->name('index');
    Route::get('/file', [DirectoryController::class, 'show'])->name('show');
    Route::post('/file', [DirectoryController::class, 'storeFile'])->name('file.store');
    Route::put('/file', [DirectoryController::class, 'update'])->name('file.update');
    Route::post('/folder', [DirectoryController::class, 'storeFolder'])->name('folder.store');
    Route::post('/transfer', [DirectoryController::class, 'transfer'])->name('transfer');
});
