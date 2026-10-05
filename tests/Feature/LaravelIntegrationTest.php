<?php

namespace Alliswell\Appyhp\Tests\Feature;

use Alliswell\Appyhp\AppyhpServiceProvider;
use Alliswell\Appyhp\Support\RuntimeStorage;
use Alliswell\Appyhp\Tests\FixtureApplication;
use Alliswell\Appyhp\Tests\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\ServiceProvider;

class LaravelIntegrationTest extends TestCase
{
    public function test_published_config_stores_state_in_the_host_application(): void
    {
        $paths = ServiceProvider::pathsToPublish(AppyhpServiceProvider::class, 'appyhp-config');
        $config = require array_key_first($paths);
        $this->assertSame(storage_path('app/appyhp'), $config['runtime_path']);
        $this->assertSame(config_path('appyhp.php'), reset($paths));
    }

    public function test_diagnostics_do_not_create_runtime_state(): void
    {
        $kernel = app(Kernel::class);
        $this->assertSame(0, $kernel->call('appyhp:doctor'));
        $this->assertStringContainsString('Configuration checks passed.', $kernel->output());
        $this->assertDirectoryDoesNotExist(app(RuntimeStorage::class)->path());
    }

    public function test_diagnostics_report_missing_application_key(): void
    {
        config(['app.key' => '']);
        $this->assertSame(1, app(Kernel::class)->call('appyhp:doctor'));
        $this->assertStringContainsString('APP_KEY is missing', app(Kernel::class)->output());
    }

    public function test_runtime_state_cannot_be_placed_in_the_public_directory(): void
    {
        config(['appyhp.runtime_path' => public_path('appyhp-state')]);
        $this->expectException(\RuntimeException::class);
        app(RuntimeStorage::class)->ensure();
    }

    public function test_laravel_can_cache_package_routes_configuration_and_views(): void
    {
        mkdir(resource_path('views'), 0755, true);
        file_put_contents(base_path('bootstrap/app.php'), '<?php return \\' . FixtureApplication::class
            . '::create(' . var_export($this->projectRoot, true) . ');');
        $kernel = app(Kernel::class);
        foreach (['route:cache', 'config:cache', 'view:cache'] as $command) {
            $this->assertSame(0, $kernel->call($command), $kernel->output());
        }
        $this->assertFileExists($this->app->getCachedRoutesPath());
        $this->assertFileExists($this->app->getCachedConfigPath());
    }
}
