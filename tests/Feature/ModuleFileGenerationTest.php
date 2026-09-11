<?php

namespace Alliswell\Appyhp\Tests\Feature;

use Alliswell\Appyhp\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ModuleFileGenerationTest extends TestCase
{
    public function test_module_files_use_laravel_artisan_generators(): void
    {
        $response = $this->putJson('/appyhp/api/directories/file', [
            'path' => 'app/Http/Controllers/AccountController.php',
            'content' => '',
            'createOnly' => true,
            'moduleType' => 'controller',
            'moduleConfig' => [
                'class' => 'AccountController',
                'actions' => 'index, store, show, update, destroy',
            ],
        ]);

        $response->assertOk()->assertJsonPath('generation', 'artisan');
        $source = file_get_contents(base_path('app/Http/Controllers/AccountController.php'));
        $this->assertStringContainsString('class AccountController', $source);
        $this->assertStringContainsString('public function index()', $source);
        $this->assertStringContainsString('public function destroy(', $source);
    }

    public function test_modules_without_native_generators_receive_a_scaffold(): void
    {
        $this->putJson('/appyhp/api/directories/file', [
            'path' => 'app/Services/Billing/InvoiceService.php',
            'content' => '',
            'createOnly' => true,
            'moduleType' => 'service',
            'moduleConfig' => ['class' => 'InvoiceService'],
        ])->assertOk()->assertJsonPath('generation', 'appyhp');

        $source = file_get_contents(base_path('app/Services/Billing/InvoiceService.php'));
        $this->assertStringContainsString('namespace App\\Services\\Billing;', $source);
        $this->assertStringContainsString('class InvoiceService', $source);
    }

    public function test_route_modules_get_a_route_file_scaffold(): void
    {
        $this->putJson('/appyhp/api/directories/file', [
            'path' => 'routes/api.php',
            'content' => '',
            'createOnly' => true,
            'moduleType' => 'route',
            'moduleConfig' => ['routeType' => 'api'],
        ])->assertOk()->assertJsonPath('generation', 'appyhp');

        $source = file_get_contents(base_path('routes/api.php'));
        $this->assertStringContainsString('use Illuminate\\Support\\Facades\\Route;', $source);
        $this->getJson('/appyhp/api/directories/file?path=routes%2Fapi.php')
            ->assertOk()
            ->assertJsonPath('content', $source)
            ->assertJsonPath('hash', hash('sha256', $source));
    }

    public function test_module_generation_does_not_overwrite_an_existing_file(): void
    {
        $path = base_path('app/Http/Controllers/AccountController.php');
        file_put_contents($path, '<?php // keep me');

        $this->putJson('/appyhp/api/directories/file', [
            'path' => 'app/Http/Controllers/AccountController.php',
            'content' => '',
            'createOnly' => true,
            'moduleType' => 'controller',
            'moduleConfig' => ['class' => 'AccountController'],
        ])->assertOk()->assertJsonPath('generation', null);

        $this->assertSame('<?php // keep me', file_get_contents($path));
    }

    public function test_module_generation_upgrades_an_old_empty_module_file(): void
    {
        $path = base_path('app/Http/Controllers/AccountController.php');
        file_put_contents($path, '');

        $this->putJson('/appyhp/api/directories/file', [
            'path' => 'app/Http/Controllers/AccountController.php',
            'content' => '',
            'createOnly' => true,
            'moduleType' => 'controller',
            'moduleConfig' => ['class' => 'AccountController'],
        ])->assertOk()->assertJsonPath('generation', 'artisan');

        $this->assertStringContainsString('class AccountController', file_get_contents($path));
    }

    public function test_module_generation_rejects_symbolic_link_targets(): void
    {
        $outside = sys_get_temp_dir() . '/appyhp-outside-' . bin2hex(random_bytes(4));
        mkdir($outside);
        symlink($outside, base_path('app/Linked'));

        try {
            $this->putJson('/appyhp/api/directories/file', [
                'path' => 'app/Linked/Escape.php',
                'content' => '',
                'createOnly' => true,
                'moduleType' => 'service',
                'moduleConfig' => ['class' => 'Escape'],
            ])->assertUnprocessable();

            $this->assertFileDoesNotExist($outside . '/Escape.php');
        } finally {
            @unlink(base_path('app/Linked'));
            @rmdir($outside);
        }
    }

    public function test_module_files_can_be_relocated_across_directories(): void
    {
        $source = base_path('app/Http/Controllers/AccountController.php');
        file_put_contents($source, '<?php // account');

        $this->postJson('/appyhp/api/directories/relocate', [
            'source' => 'app/Http/Controllers/AccountController.php',
            'target' => 'app/Domain/Accounts/RenamedAccount.php',
        ])->assertOk()
            ->assertJsonPath('path', 'app/Domain/Accounts/RenamedAccount.php')
            ->assertJsonPath('moved', true);

        $this->assertFileDoesNotExist($source);
        $this->assertSame('<?php // account', file_get_contents(base_path('app/Domain/Accounts/RenamedAccount.php')));
    }

    /**
     * @param array<string, string> $config
     */
    #[DataProvider('moduleGenerators')]
    public function test_every_supported_module_gets_a_non_empty_template(
        string $type,
        string $path,
        array $config,
        string $method,
        string $expected,
    ): void {
        $this->putJson('/appyhp/api/directories/file', [
            'path' => $path,
            'content' => '',
            'createOnly' => true,
            'moduleType' => $type,
            'moduleConfig' => $config,
        ])->assertOk()->assertJsonPath('generation', $method);

        $source = file_get_contents(base_path($path));
        $this->assertNotSame('', trim($source));
        $this->assertStringContainsString($expected, $source);
    }

    public static function moduleGenerators(): array
    {
        return [
            'middleware' => ['middleware', 'app/Http/Middleware/EnsureSubscribed.php', ['class' => 'EnsureSubscribed'], 'artisan', 'class EnsureSubscribed'],
            'request' => ['request', 'app/Http/Requests/StoreInvoiceRequest.php', ['class' => 'StoreInvoiceRequest'], 'artisan', 'class StoreInvoiceRequest'],
            'resource' => ['resource', 'app/Http/Resources/InvoiceResource.php', ['class' => 'InvoiceResource'], 'artisan', 'class InvoiceResource'],
            'model' => ['model', 'app/Models/Invoice.php', ['class' => 'Invoice'], 'artisan', 'class Invoice'],
            'table' => ['table', 'database/migrations/2026_01_01_000000_create_invoices_table.php', ['name' => 'invoices'], 'artisan', "Schema::create('invoices'"],
            'migration' => ['migration', 'database/migrations/2026_01_01_000001_add_total_to_invoices_table.php', ['name' => 'add_total_to_invoices_table', 'table' => 'invoices', 'operation' => 'alter'], 'artisan', "Schema::table('invoices'"],
            'factory' => ['factory', 'database/factories/InvoiceFactory.php', ['class' => 'InvoiceFactory', 'model' => 'Invoice'], 'artisan', 'class InvoiceFactory'],
            'seeder' => ['seeder', 'database/seeders/InvoiceSeeder.php', ['class' => 'InvoiceSeeder'], 'artisan', 'class InvoiceSeeder'],
            'service' => ['service', 'app/Services/InvoiceService.php', ['class' => 'InvoiceService'], 'appyhp', 'class InvoiceService'],
            'repository' => ['repository', 'app/Repositories/InvoiceRepository.php', ['class' => 'InvoiceRepository'], 'appyhp', 'class InvoiceRepository'],
            'policy' => ['policy', 'app/Policies/InvoicePolicy.php', ['class' => 'InvoicePolicy', 'model' => 'Invoice'], 'artisan', 'class InvoicePolicy'],
            'job' => ['job', 'app/Jobs/ProcessInvoice.php', ['class' => 'ProcessInvoice'], 'artisan', 'class ProcessInvoice'],
            'command' => ['command', 'app/Console/Commands/SyncInvoices.php', ['class' => 'SyncInvoices'], 'artisan', 'class SyncInvoices'],
            'event' => ['event', 'app/Events/InvoicePaid.php', ['class' => 'InvoicePaid'], 'artisan', 'class InvoicePaid'],
            'listener' => ['listener', 'app/Listeners/SendInvoiceReceipt.php', ['class' => 'SendInvoiceReceipt', 'listensTo' => 'InvoicePaid', 'queued' => 'yes'], 'artisan', 'class SendInvoiceReceipt'],
            'notification' => ['notification', 'app/Notifications/InvoicePaidNotification.php', ['class' => 'InvoicePaidNotification'], 'artisan', 'class InvoicePaidNotification'],
            'mail' => ['mail', 'app/Mail/InvoiceReceipt.php', ['class' => 'InvoiceReceipt'], 'artisan', 'class InvoiceReceipt'],
            'view' => ['view', 'resources/views/invoices/index.blade.php', ['path' => 'invoices.index'], 'artisan', '<div>'],
            'component' => ['component', 'app/View/Components/InvoiceCard.php', ['class' => 'InvoiceCard'], 'artisan', 'class InvoiceCard'],
            'inertia page' => ['inertia-page', 'resources/js/Pages/Invoices/Index.vue', ['framework' => 'vue'], 'appyhp', '<template>'],
            'inherited React inertia page' => ['inertia-page', 'resources/js/Pages/Invoices/Index.jsx', ['framework' => 'inherit', 'frontend' => 'react'], 'appyhp', 'export default function Page()'],
            'inertia middleware without adapter' => ['inertia-middleware', 'app/Http/Middleware/HandleInertiaRequests.php', ['class' => 'HandleInertiaRequests'], 'appyhp', 'extends Middleware'],
            'auth config' => ['auth', 'config/auth.php', [], 'artisan', "'defaults'"],
            'queue config' => ['queue', 'config/queue.php', [], 'artisan', "'default'"],
            'cache config' => ['cache', 'config/cache.php', [], 'artisan', "'default'"],
            'storage config' => ['storage', 'config/filesystems.php', [], 'artisan', "'disks'"],
            'console routes' => ['route', 'routes/console.php', ['routeType' => 'console'], 'appyhp', 'Facades\\Artisan'],
            'channel routes' => ['route', 'routes/channels.php', ['routeType' => 'channels'], 'appyhp', 'Facades\\Broadcast'],
        ];
    }
}
