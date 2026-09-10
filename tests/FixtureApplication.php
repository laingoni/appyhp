<?php

namespace Alliswell\Appyhp\Tests;

use Alliswell\Appyhp\AppyhpServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

class FixtureApplication
{
    public static function create(string $root): Application
    {
        foreach (['bootstrap/cache', 'config', 'storage/framework/views', 'storage/framework/cache/data', 'app/Http/Controllers', 'resources/js/Pages', 'routes', 'database/migrations'] as $directory) {
            if (! is_dir($root . '/' . $directory)) {
                mkdir($root . '/' . $directory, 0755, true);
            }
        }
        if (! is_file($root . '/composer.json')) {
            file_put_contents($root . '/composer.json', json_encode(['require' => ['laravel/framework' => '^13.0'], 'autoload' => ['psr-4' => ['App\\' => 'app/']]]));
        }

        $app = Application::configure(basePath: $root)
            ->withProviders([AppyhpServiceProvider::class])
            ->withRouting(using: function (): void {})
            ->withMiddleware()
            ->withExceptions()
            ->create();
        $app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($root): void {
            $app['config']->set([
                'app.env' => 'testing',
                'app.debug' => true,
                'app.key' => 'base64:' . base64_encode(str_repeat('t', 32)),
                'appyhp.mode' => 'dev',
                'session.driver' => 'database',
                'cache.default' => 'database',
                'logging.default' => 'errorlog',
                'appyhp.runtime_path' => $root . '/storage/app/appyhp',
            ]);
            $app['env'] = 'testing';
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public static function workflow(): array
    {
        return [
            'id' => 'wf_test', 'name' => 'Accounts', 'description' => 'Account workflow', 'meta' => ['frontend' => 'vue'],
            'modules' => [
                ['id' => 'route', 'type' => 'route', 'label' => 'Accounts route', 'config' => ['method' => 'GET', 'uri' => '/accounts', 'action' => 'index', 'folder' => 'routes', 'filename' => 'web.php', 'prompt' => 'List accounts', 'ai' => ['code' => '<?php // latest route draft']]],
                ['id' => 'controller', 'type' => 'controller', 'label' => 'Accounts controller', 'config' => ['class' => 'AccountController', 'actions' => 'index', 'folder' => 'app/Http/Controllers', 'filename' => 'AccountController.php', 'prompt' => 'Return the accounts Inertia page']],
                ['id' => 'page', 'type' => 'inertia-page', 'label' => 'Accounts page', 'config' => ['page' => 'Accounts/Index', 'framework' => 'inherit', 'props' => 'accounts', 'folder' => 'resources/js/Pages', 'filename' => 'Index.vue', 'prompt' => 'List accounts']],
            ],
            'edges' => [['id' => 'e1', 'from' => 'route', 'to' => 'controller', 'label' => 'dispatch'], ['id' => 'e2', 'from' => 'controller', 'to' => 'page', 'label' => 'renders']],
        ];
    }

    public static function output(?array $table = null): string
    {
        $metadata = ['summary' => 'Render the accounts page.', 'config' => new \stdClass, 'suggestions' => [['moduleId' => 'route', 'message' => 'Name the GET /accounts route accounts.index.']], 'table' => $table];

        return "```php\n<?php\nnamespace App\\Http\\Controllers;\nuse Inertia\\Inertia;\nclass AccountController { public function index() { return Inertia::render('Accounts/Index', ['accounts' => []]); } }\n```\n```appyhp\n" . json_encode($metadata) . "\n```";
    }

    public static function sse(string $output, string $provider = 'openai', bool $complete = true): string
    {
        $stream = ": keepalive\r\n\r\n";
        foreach (str_split($output, 17) as $text) {
            $event = match ($provider) {
                'anthropic' => ['type' => 'content_block_delta', 'delta' => ['type' => 'text_delta', 'text' => $text]],
                'compatible' => ['choices' => [['delta' => ['content' => $text], 'finish_reason' => null]]],
                default => ['type' => 'response.output_text.delta', 'delta' => $text],
            };
            $stream .= 'data: ' . json_encode($event) . "\r\n\r\n";
        }
        if ($complete) {
            $stream .= 'data: ' . match ($provider) {
                'compatible' => '[DONE]',
                'anthropic' => '{"type":"message_stop"}',
                default => '{"type":"response.completed"}',
            } . "\r\n\r\n";
        }

        return $stream;
    }
}
