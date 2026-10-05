<?php

namespace Alliswell\Appyhp\Console;

use Alliswell\Appyhp\Support\RuntimeStorage;
use Alliswell\Appyhp\Support\StudioMode;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    protected $signature = 'appyhp:doctor';

    protected $description = 'Check AppyHP configuration and installation without changing project files';

    public function handle(RuntimeStorage $runtime): int
    {
        $this->info('AppyHP installation');
        $this->line('Laravel: ' . app()->version() . ' | PHP: ' . PHP_VERSION);
        $this->line('Studio: ' . (StudioMode::enabled() ? 'enabled' : 'disabled'));
        $this->line('Configuration cache: ' . (app()->configurationIsCached() ? 'enabled' : 'disabled'));
        $this->line('Route cache: ' . (app()->routesAreCached() ? 'enabled' : 'disabled'));
        $errors = [];
        try {
            $path = $runtime->path();
            $this->line('Runtime directory: ' . $path);
            $parent = $path;
            while (! file_exists($parent) && dirname($parent) !== $parent) {
                $parent = dirname($parent);
            }
            if (! is_dir($parent) || ! is_writable($parent)) {
                $errors[] = 'The runtime directory or its nearest parent must be writable by the web process.';
            }
        } catch (\RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
        if (StudioMode::enabled() && ! config('app.key')) {
            $errors[] = 'APP_KEY is missing. Run php artisan key:generate in the host application.';
        }
        $allowed = config('appyhp.allowed_ips', []);
        if (! is_array($allowed) || $allowed === []) {
            $errors[] = 'APPYHP_ALLOWED_IPS must contain at least one trusted address.';
        } elseif (array_diff($allowed, ['127.0.0.1', '::1']) !== [] && ! config('appyhp.access_token')) {
            $errors[] = 'Set APPYHP_ACCESS_TOKEN to enable access from non-loopback addresses.';
        }
        foreach ($errors as $error) {
            $this->error($error);
        }
        if ($errors !== []) {
            return self::FAILURE;
        }
        $this->info('Configuration checks passed.');
        if (StudioMode::enabled()) {
            $this->line('Open ' . url('/appyhp/studio'));
        }

        return self::SUCCESS;
    }
}
