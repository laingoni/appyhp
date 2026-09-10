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
Route::get('/appyhp/studio/web', fn () => redirect('/appyhp/studio/web/'))->name('appyhp.docs.redirect');
Route::get('/appyhp/studio/web/', function () {
    return response()->file(__DIR__ . '/../web/index.html');
})->name('appyhp.docs.index');
Route::get('/appyhp/studio/web/{path}', function (string $path) {
    $root = realpath(__DIR__ . '/../web');
    $file = realpath($root . DIRECTORY_SEPARATOR . ltrim($path, '/'));

    abort_unless($root !== false && $file !== false && is_file($file), 404);
    abort_unless($file === $root || str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $root) . '/'), 404);

    $contentType = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'html' => 'text/html; charset=UTF-8',
        default => 'application/octet-stream',
    };

    return response()->file($file, ['Content-Type' => $contentType]);
})->where('path', '.*')->name('appyhp.docs.asset');

// Compatibility for browsers that opened /appyhp/studio/web without its trailing slash.
Route::get('/appyhp/studio/{path}', function (string $path) {
    $root = realpath(__DIR__ . '/../web');
    $file = realpath($root . DIRECTORY_SEPARATOR . ltrim($path, '/'));

    abort_unless($root !== false && $file !== false && is_file($file), 404);
    abort_unless($file === $root || str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $root) . '/'), 404);

    $contentType = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'html' => 'text/html; charset=UTF-8',
        default => 'application/octet-stream',
    };

    return response()->file($file, ['Content-Type' => $contentType]);
})->where('path', '(assets/.*|.*\.html)')->name('appyhp.docs.compat');

Route::middleware([EncryptCookies::class, StudioSession::class, ValidateCsrfToken::class])->prefix('/appyhp/api/ai')->name('appyhp.ai.')->group(function (): void {
    Route::get('/settings', [AiController::class, 'settings'])->name('settings');
    Route::put('/settings', [AiController::class, 'updateSettings'])->name('settings.update');
    Route::post('/test', [AiController::class, 'test'])->middleware(ThrottleAiRequests::class . ':10,1')->name('test');
    Route::post('/generate', [AiController::class, 'generate'])->middleware(ThrottleAiRequests::class . ':30,1')->name('generate');
    Route::post('/explain-file', [AiController::class, 'explainFile'])->middleware(ThrottleAiRequests::class . ':10,1')->name('explain-file');
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
    Route::post('/rename', [DirectoryController::class, 'rename'])->name('rename');
    Route::get('/metadata', [DirectoryController::class, 'metadata'])->name('metadata');
    Route::put('/metadata', [DirectoryController::class, 'updateMetadata'])->name('metadata.update');
    Route::post('/folder', [DirectoryController::class, 'storeFolder'])->name('folder.store');
    Route::post('/transfer', [DirectoryController::class, 'transfer'])->name('transfer');
    Route::delete('/item', [DirectoryController::class, 'destroy'])->name('destroy');
});
