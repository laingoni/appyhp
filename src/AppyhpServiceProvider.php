<?php

namespace Alliswell\Appyhp;

use Alliswell\Appyhp\Console\DoctorCommand;
use Alliswell\Appyhp\Support\StudioMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
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
        if ($this->app->runningInConsole()) {
            $this->commands([DoctorCommand::class]);
            $this->publishes([
                __DIR__ . '/../config/appyhp.php' => config_path('appyhp.php'),
            ], 'appyhp-config');
        }

        if (StudioMode::enabled()) {
            $containsSource = fn (Request $request): bool => $request->is(
                'appyhp/api/workflows',
                'appyhp/api/ai/generate',
                'appyhp/api/ai/file',
                'appyhp/api/directories/file',
            );
            TrimStrings::skipWhen($containsSource);
            ConvertEmptyStringsToNull::skipWhen($containsSource);
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}
