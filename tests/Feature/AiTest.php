<?php

namespace Alliswell\Appyhp\Tests\Feature;

use Alliswell\Appyhp\Support\AiGateway;
use Alliswell\Appyhp\Support\AiSettings;
use Alliswell\Appyhp\Support\GenerationFileRequest;
use Alliswell\Appyhp\Support\GenerationResult;
use Alliswell\Appyhp\Support\ProjectFiles;
use Alliswell\Appyhp\Support\WorkflowContext;
use Alliswell\Appyhp\Tests\FixtureApplication;
use Alliswell\Appyhp\Tests\TestCase;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class AiTest extends TestCase
{
    public function test_studio_loads_without_database_session_or_cache_tables(): void
    {
        $this->get('/appyhp/studio')->assertOk()->assertSee('data-ai-settings', false)->assertSee('What should this module do?', false);
        $this->getJson('/appyhp/api/ai/settings')->assertOk()->assertJsonPath('settings.configured', false);
        $this->assertSame('database', config('session.driver'));
        $this->assertSame('database', config('cache.default'));
    }

    public function test_keys_are_encrypted_and_never_returned(): void
    {
        $response = $this->putJson('/appyhp/api/ai/settings', $this->settings())->assertOk()->assertJsonPath('settings.has_key', true);
        $this->assertStringNotContainsString('test-secret-key', $response->getContent());
        $this->assertStringNotContainsString('test-secret-key', file_get_contents(storage_path('app/appyhp/ai-settings.json')));
        $this->getJson('/appyhp/api/ai/settings')->assertJsonMissingPath('settings.api_key');
        $this->assertSame('test-secret-key', app(AiSettings::class)->read()['api_key']);
        $this->assertSame(0600, fileperms(storage_path('app/appyhp/ai-settings.json')) & 0777);
    }

    public function test_blank_key_preserves_only_the_same_provider_and_address(): void
    {
        $this->putJson('/appyhp/api/ai/settings', $this->settings())->assertOk();
        $this->putJson('/appyhp/api/ai/settings', $this->settings(['api_key' => '', 'model' => 'another-model']))->assertOk()->assertJsonPath('settings.has_key', true);
        $this->putJson('/appyhp/api/ai/settings', $this->settings(['api_key' => '', 'base_url' => 'https://other.example/v1']))->assertOk()->assertJsonPath('settings.has_key', false);
    }

    public function test_removing_a_stored_key_does_not_restore_an_environment_key(): void
    {
        config(['appyhp.ai.api_key' => 'environment-secret']);
        $this->putJson('/appyhp/api/ai/settings', $this->settings(['api_key' => '', 'clear_key' => true]))->assertOk()->assertJsonPath('settings.configured', false);
        $this->assertSame('', app(AiSettings::class)->read()['api_key']);
    }

    public function test_settings_validate_urls_and_allow_local_keyless_providers(): void
    {
        foreach (['http://remote.example/v1', 'https://api.example/v1?key=secret', 'https://user:pass@api.example/v1', 'file:///etc/passwd'] as $url) {
            $this->putJson('/appyhp/api/ai/settings', $this->settings(['base_url' => $url]))->assertUnprocessable()->assertJsonValidationErrors('base_url');
        }
        $this->putJson('/appyhp/api/ai/settings', $this->settings(['provider' => 'compatible', 'base_url' => 'http://localhost:11434/v1', 'api_key' => '']))->assertOk()->assertJsonPath('settings.configured', true);
    }

    public function test_ai_mutations_require_csrf_outside_the_test_environment(): void
    {
        $this->app['env'] = 'local';
        $this->putJson('/appyhp/api/ai/settings', $this->settings())->assertStatus(419);
        $this->postJson('/appyhp/api/ai/file', ['path' => 'routes/web.php', 'content' => '<?php', 'expectedHash' => null])->assertStatus(419);
    }

    public function test_generation_uses_unsaved_graph_drafts_and_connected_source_files(): void
    {
        file_put_contents(base_path('routes/web.php'), '<?php // existing project route');
        $workflow = FixtureApplication::workflow();
        $workflow['edges'][] = ['from' => 'page', 'to' => 'route', 'label' => 'links'];
        $context = app(WorkflowContext::class)->build($workflow, 'controller');
        $this->assertSame('/accounts', $context['workflow']['modules'][0]['config']['uri']);
        $this->assertSame('<?php // latest route draft', $context['workflow']['modules'][0]['draft_code']);
        $this->assertSame('<?php // existing project route', $context['workflow']['modules'][0]['source_file']['content']);
        $this->assertSame('bidirectional', $context['workflow']['modules'][0]['relationship_to_selected']);
        $this->assertSame('Return the accounts Inertia page', $context['generation_task']['user_description']);
        $this->assertSame('AccountController', $context['generation_task']['selected_module']['config']['class']);
        $this->assertTrue($context['workflow']['modules'][2]['connected']);
        $this->assertSame('vue', $context['frontend']);
        $this->assertStringNotContainsString('api_key', json_encode($context));
    }

    #[DataProvider('frontends')]
    public function test_project_detects_all_three_inertia_adapters(string $framework, string $adapter): void
    {
        file_put_contents(base_path('package.json'), json_encode(['dependencies' => [$adapter => '^3.0', $framework => '^5.0']]));
        $project = app(ProjectFiles::class)->project();
        $this->assertSame($framework, $project['frontend']);
        $this->assertSame('^3.0', $project['frontend_dependencies'][$adapter]);
    }

    public static function frontends(): array
    {
        return [['vue', '@inertiajs/vue3'], ['react', '@inertiajs/react'], ['svelte', '@inertiajs/svelte']];
    }

    public function test_connected_controllers_follow_explicit_inertia_pages_in_a_blade_workflow(): void
    {
        $workflow = FixtureApplication::workflow();
        $workflow['meta']['frontend'] = 'blade';
        $this->assertSame('vue', app(WorkflowContext::class)->build($workflow, 'page')['frontend']);
        $workflow['modules'][2]['config']['framework'] = 'svelte';
        $context = app(WorkflowContext::class)->build($workflow, 'controller');
        $this->assertSame('svelte', $context['frontend']);
        $this->assertSame('svelte', $context['workflow']['modules'][2]['effective_frontend']);
    }

    #[DataProvider('providers')]
    public function test_provider_streams_survive_fragmentation(string $provider, string $endpoint): void
    {
        $source = Utils::streamFor(FixtureApplication::sse('Hello, code.', $provider));
        $fragmented = FnStream::decorate($source, ['read' => fn ($length) => $source->read(3)]);
        Http::fake(['*' => Http::response($fragmented, 200, ['Content-Type' => 'text/event-stream'])]);
        $chunks = [];
        $output = app(AiGateway::class)->stream($this->settings(['provider' => $provider]), 'Instructions', 'Context', function ($text) use (&$chunks) { $chunks[] = $text; });
        $this->assertSame('Hello, code.', $output);
        $this->assertSame($output, implode('', $chunks));
        Http::assertSent(function ($request) use ($provider, $endpoint) {
            $this->assertTrue(str_ends_with($request->url(), $endpoint));
            $this->assertTrue($request['stream']);
            if ($provider === 'openai') $this->assertFalse($request['store']);
            if ($provider === 'anthropic') $this->assertTrue($request->hasHeader('x-api-key', 'test-secret-key'));

            return true;
        });
    }

    public static function providers(): array
    {
        return [['openai', '/responses'], ['compatible', '/chat/completions'], ['anthropic', '/messages']];
    }

    public function test_truncated_stream_is_an_error(): void
    {
        Http::fake(['*' => Http::response(FixtureApplication::sse('unfinished', 'openai', false))]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('without a complete response');
        app(AiGateway::class)->stream($this->settings(), '', '', fn () => true);
    }

    public function test_provider_errors_do_not_echo_credentials_or_provider_responses(): void
    {
        Http::fake(['*' => Http::response(['error' => 'test-secret-key'], 401)]);
        $this->postJson('/appyhp/api/ai/test', $this->settings())->assertUnprocessable()->assertJsonPath('message', 'The provider rejected the API key or model access. Check AI settings.');
        $this->assertFileDoesNotExist(storage_path('app/appyhp/ai-settings.json'));
    }

    public function test_test_connection_does_not_persist_unsaved_settings(): void
    {
        Http::fake(['*' => Http::response(FixtureApplication::sse('OK'))]);
        $this->postJson('/appyhp/api/ai/test', $this->settings())->assertOk()->assertJsonPath('connected', true);
        $this->assertFileDoesNotExist(storage_path('app/appyhp/ai-settings.json'));
    }

    public function test_generation_stream_returns_code_and_reconciliation_metadata_without_writing_files(): void
    {
        $this->putJson('/appyhp/api/ai/settings', $this->settings())->assertOk();
        Http::fake(['*' => Http::response(FixtureApplication::sse(FixtureApplication::output()))]);
        $response = $this->postJson('/appyhp/api/ai/generate', ['moduleId' => 'controller', 'workflow' => FixtureApplication::workflow()])->assertOk();
        $stream = $response->streamedContent();
        $this->assertStringContainsString('event: delta', $stream);
        $this->assertStringContainsString('event: done', $stream);
        $this->assertStringContainsString('accounts.index', $stream);
        $this->assertStringNotContainsString('event: error', $stream);
        $this->assertFileDoesNotExist(base_path('app/Http/Controllers/AccountController.php'));
        Http::assertSent(function ($request) {
            $context = json_decode($request['input'], true);
            $this->assertSame('AccountController', $context['workflow']['modules'][1]['config']['class']);
            $this->assertSame('/accounts', $context['workflow']['modules'][0]['config']['uri']);
            $this->assertSame('Return the accounts Inertia page', $context['generation_task']['user_description']);

            return true;
        });
    }

    public function test_generation_can_request_an_additional_file_without_creating_a_draft(): void
    {
        $this->putJson('/appyhp/api/ai/settings', $this->settings())->assertOk();
        $request = "```appyhp-file-request\n{\"path\":\"app/Support/TaxRules.php\",\"reason\":\"I need the existing tax contract.\"}\n```";
        Http::fake(['*' => Http::response(FixtureApplication::sse($request))]);

        $stream = $this->postJson('/appyhp/api/ai/generate', [
            'moduleId' => 'controller',
            'workflow' => FixtureApplication::workflow(),
        ])->assertOk()->streamedContent();

        $this->assertStringContainsString('event: file_request', $stream);
        $this->assertStringContainsString('app/Support/TaxRules.php', $stream);
        $this->assertStringNotContainsString('event: done', $stream);
    }

    public function test_granted_and_denied_file_decisions_are_structured_for_continuation(): void
    {
        $this->putJson('/appyhp/api/ai/settings', $this->settings())->assertOk();
        mkdir(base_path('app/Support'), 0755, true);
        file_put_contents(base_path('app/Support/TaxRules.php'), '<?php // tax contract');
        Http::fake(['*' => Http::response(FixtureApplication::sse(FixtureApplication::output()))]);

        $this->postJson('/appyhp/api/ai/generate', [
            'moduleId' => 'controller',
            'workflow' => FixtureApplication::workflow(),
            'fileAccess' => [
                ['path' => 'app/Support/TaxRules.php', 'decision' => 'grant'],
                ['path' => 'app/Support/PrivateRules.php', 'decision' => 'deny'],
            ],
        ])->assertOk()->streamedContent();

        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/responses')) return false;
            $context = json_decode($request['input'], true);
            $this->assertSame('granted', $context['file_access']['decisions'][0]['status']);
            $this->assertSame('<?php // tax contract', $context['file_access']['decisions'][0]['source_file']['content']);
            $this->assertSame('denied', $context['file_access']['decisions'][1]['status']);
            $this->assertArrayNotHasKey('source_file', $context['file_access']['decisions'][1]);

            return true;
        });
    }

    public function test_file_request_protocol_rejects_surrounding_prose(): void
    {
        $this->expectException(RuntimeException::class);
        GenerationFileRequest::parse("Please approve:\n```appyhp-file-request\n{\"path\":\"app/Test.php\",\"reason\":\"Needed\"}\n```");
    }

    public function test_table_metadata_and_code_roundtrip_through_workflow_storage(): void
    {
        $workflow = FixtureApplication::workflow();
        $workflow['modules'][1]['type'] = 'table';
        $table = ['name' => 'accounts', 'columns' => [['name' => 'id', 'type' => 'bigint', 'nullable' => false, 'key' => 'primary', 'default' => null]]];
        $context = app(WorkflowContext::class)->build($workflow, 'controller');
        $result = GenerationResult::parse(FixtureApplication::output($table), $context);
        $workflow['modules'][1]['config']['ai'] = $result;
        $this->putJson('/appyhp/api/workflows', ['workflows' => [$workflow]])->assertOk();
        $this->getJson('/appyhp/api/workflows')->assertJsonPath('workflows.0.modules.1.config.ai.table.columns.0.name', 'id')->assertJsonPath('workflows.0.modules.1.config.ai.code', $result['code']);
    }

    public function test_source_whitespace_and_empty_fields_survive_saves(): void
    {
        $code = "  <?php // preserve source\n\n";
        $this->postJson('/appyhp/api/ai/file', ['path' => 'app/Whitespace.php', 'content' => $code, 'expectedHash' => null])->assertOk();
        $this->assertSame($code, file_get_contents(base_path('app/Whitespace.php')));
        $workflow = FixtureApplication::workflow();
        $workflow['modules'][0]['config']['ai']['code'] = $code;
        $workflow['modules'][0]['config']['name'] = '';
        $this->putJson('/appyhp/api/workflows', ['workflows' => [$workflow]])->assertOk()->assertJsonPath('workflows.0.modules.0.config.ai.code', $code)->assertJsonPath('workflows.0.modules.0.config.name', '');
    }

    public function test_malformed_generation_does_not_become_a_valid_draft(): void
    {
        $this->expectException(RuntimeException::class);
        GenerationResult::parse('```php' . "\n<?php\n```", app(WorkflowContext::class)->build(FixtureApplication::workflow(), 'controller'));
    }

    public function test_target_writes_use_conflict_checks_and_create_nested_folders(): void
    {
        $payload = ['path' => 'app/Services/Accounts/Report.php', 'content' => '<?php // first', 'expectedHash' => null];
        $created = $this->postJson('/appyhp/api/ai/file', $payload)->assertOk();
        $this->postJson('/appyhp/api/ai/file', $payload)->assertConflict();
        $payload['expectedHash'] = $created->json('hash');
        $payload['content'] = '<?php // second';
        $this->postJson('/appyhp/api/ai/file', $payload)->assertOk();
        $this->assertSame($payload['content'], file_get_contents(base_path($payload['path'])));
    }

    public function test_paths_cannot_escape_source_folders_or_read_secrets(): void
    {
        foreach (['../outside.php', 'app/../../outside.php', '/app/Test.php', '.env', 'storage/app/appyhp/ai-settings.json', 'app/.hidden/secret.php', 'app/../.env', 'app\\Test.php'] as $path) {
            $this->postJson('/appyhp/api/ai/file', ['path' => $path, 'content' => '<?php', 'expectedHash' => null])->assertUnprocessable();
        }
        symlink(sys_get_temp_dir(), base_path('app/linked'));
        $this->postJson('/appyhp/api/ai/file', ['path' => 'app/linked/outside.php', 'content' => '<?php', 'expectedHash' => null])->assertUnprocessable();
    }
}
