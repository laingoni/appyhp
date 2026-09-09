<?php

namespace Alliswell\Appyhp;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppyhpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/appyhp.php', 'appyhp');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'appyhp');

        if ($this->studioEnabled()) {
            $containsSource = fn (Request $request): bool => $request->is('appyhp/api/workflows', 'appyhp/api/ai/generate', 'appyhp/api/ai/file');
            TrimStrings::skipWhen($containsSource);
            ConvertEmptyStringsToNull::skipWhen($containsSource);
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    private function studioEnabled(): bool
    {
        $mode = config('appyhp.mode');

        if (is_string($mode) && trim($mode) !== '') {
            $normalizedMode = strtolower(trim($mode));

            if (! in_array($normalizedMode, ['dev', 'dist'], true)) {
                Log::warning('APPY_MODE must be either "dev" or "dist". Appyhp Studio route is disabled.');

                return false;
            }

            return $normalizedMode === 'dev';
        }

        Log::warning('APPY_MODE is not set. Appyhp Studio route availability is falling back to APP_DEBUG.');

        return (bool) config('app.debug');
    }
}
