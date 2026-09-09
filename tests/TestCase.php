<?php

namespace Alliswell\Appyhp\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends LaravelTestCase
{
    protected string $projectRoot;

    public function createApplication()
    {
        $this->projectRoot = sys_get_temp_dir() . '/appyhp-test-' . bin2hex(random_bytes(8));
        $this->traitsUsedByTest = class_uses_recursive(static::class);

        return FixtureApplication::create($this->projectRoot);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem)->deleteDirectory($this->projectRoot);
    }

    protected function settings(array $overrides = []): array
    {
        return array_replace(['provider' => 'openai', 'base_url' => 'https://api.openai.com/v1', 'model' => 'test-model', 'api_key' => 'test-secret-key', 'live' => true, 'debounce_ms' => 1200], $overrides);
    }
}
