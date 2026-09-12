<?php

namespace Alliswell\Appyhp\Tests\Feature;

use Alliswell\Appyhp\Support\RuntimeStorage;
use Alliswell\Appyhp\Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_studio_is_local_only_by_default(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('/appyhp/studio')
            ->assertForbidden();
    }

    public function test_optional_access_token_uses_http_basic_authentication(): void
    {
        config(['appyhp.access_token' => 'a-long-development-token']);

        $this->get('/appyhp/studio')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="AppyHP Studio", charset="UTF-8"');
        $this->withBasicAuth('appyhp', 'wrong-token')->get('/appyhp/studio')->assertUnauthorized();
        $this->withBasicAuth('appyhp', 'a-long-development-token')->get('/appyhp/studio')->assertOk();
    }

    public function test_studio_responses_include_browser_security_headers(): void
    {
        $this->get('/appyhp/studio')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_sensitive_project_paths_are_hidden_and_inaccessible(): void
    {
        file_put_contents(base_path('.env'), 'APP_KEY=secret');
        mkdir(base_path('storage/logs'), 0755, true);
        file_put_contents(base_path('storage/logs/laravel.log'), 'secret log');

        $this->getJson('/appyhp/api/directories')
            ->assertOk()
            ->assertJsonMissing(['name' => '.env'])
            ->assertJsonMissing(['name' => 'storage']);
        $this->getJson('/appyhp/api/directories/file?path=.env')->assertForbidden();
        $this->getJson('/appyhp/api/directories/file?path=storage%2Flogs%2Flaravel.log')->assertForbidden();
        $this->postJson('/appyhp/api/directories/file', ['parent' => '', 'name' => '.env.production'])->assertForbidden();
    }

    public function test_every_mutating_api_requires_csrf_outside_testing(): void
    {
        $this->app['env'] = 'local';

        $this->putJson('/appyhp/api/setup', [])->assertStatus(419);
        $this->putJson('/appyhp/api/workflows', ['workflows' => []])->assertStatus(419);
        $this->postJson('/appyhp/api/directories/file', ['parent' => '', 'name' => 'Unsafe.php'])->assertStatus(419);
        $this->deleteJson('/appyhp/api/directories/item', ['path' => 'routes'])->assertStatus(419);
    }

    public function test_setup_only_persists_valid_known_values(): void
    {
        $this->putJson('/appyhp/api/setup', [
            'activePanel' => 'directories',
            'autosave' => true,
            'sidebarVisible' => false,
            'unexpected' => 'discard me',
        ])->assertOk()->assertJsonMissingPath('unexpected');

        $stored = json_decode(file_get_contents(storage_path('app/appyhp/setup.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('unexpected', $stored);
        $this->assertSame(0600, fileperms(storage_path('app/appyhp/setup.json')) & 0777);
        $this->assertSame(0700, fileperms(storage_path('app/appyhp')) & 0777);
    }

    public function test_runtime_path_must_be_absolute(): void
    {
        config(['appyhp.runtime_path' => 'relative/runtime']);

        $this->expectException(\RuntimeException::class);
        app(RuntimeStorage::class)->path();
    }

    public function test_workflow_collection_size_is_bounded(): void
    {
        $workflows = array_fill(0, 101, ['id' => 'workflow', 'name' => 'Workflow']);

        $this->putJson('/appyhp/api/workflows', ['workflows' => $workflows])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('workflows');
    }

    public function test_manual_file_saves_reject_stale_content(): void
    {
        $path = base_path('app/Concurrent.php');
        file_put_contents($path, '<?php // original');
        $hash = hash_file('sha256', $path);
        file_put_contents($path, '<?php // changed elsewhere');

        $this->putJson('/appyhp/api/directories/file', [
            'path' => 'app/Concurrent.php',
            'content' => '<?php // editor change',
            'expectedHash' => $hash,
        ])->assertConflict();

        $this->assertSame('<?php // changed elsewhere', file_get_contents($path));
    }

    public function test_manual_file_saves_preserve_source_whitespace_exactly(): void
    {
        $path = base_path('app/Whitespace.php');
        file_put_contents($path, '<?php // original');
        $content = " \n<?php\n\nreturn true;\n ";

        $this->putJson('/appyhp/api/directories/file', [
            'path' => 'app/Whitespace.php',
            'content' => $content,
            'expectedHash' => hash_file('sha256', $path),
        ])->assertOk()->assertJsonPath('hash', hash('sha256', $content));

        $this->assertSame($content, file_get_contents($path));
    }

    public function test_folders_containing_symbolic_links_cannot_be_copied(): void
    {
        $source = base_path('app/Source');
        $outside = sys_get_temp_dir() . '/appyhp-copy-' . bin2hex(random_bytes(4));
        mkdir($source);
        mkdir($outside);
        file_put_contents($outside . '/secret.txt', 'secret');
        symlink($outside, $source . '/Linked');

        try {
            $this->postJson('/appyhp/api/directories/transfer', [
                'mode' => 'copy',
                'source' => 'app/Source',
                'parent' => 'routes',
            ])->assertUnprocessable();

            $this->assertDirectoryDoesNotExist(base_path('routes/Source'));
        } finally {
            @unlink($source . '/Linked');
            @rmdir($source);
            @unlink($outside . '/secret.txt');
            @rmdir($outside);
        }
    }

    public function test_file_notes_follow_copied_and_moved_directories(): void
    {
        mkdir(base_path('app/Domain'), 0755, true);
        file_put_contents(base_path('app/Domain/Rules.php'), '<?php');
        $this->putJson('/appyhp/api/directories/metadata', [
            'path' => 'app/Domain/Rules.php',
            'notes' => 'Business rules used by billing.',
        ])->assertOk();

        $this->postJson('/appyhp/api/directories/transfer', [
            'mode' => 'copy',
            'source' => 'app/Domain',
            'parent' => 'routes',
        ])->assertCreated();

        $this->getJson('/appyhp/api/directories/metadata?path=routes%2FDomain%2FRules.php')
            ->assertOk()
            ->assertJsonPath('notes', 'Business rules used by billing.');

        mkdir(base_path('app/Archive'));
        $this->postJson('/appyhp/api/directories/transfer', [
            'mode' => 'cut',
            'source' => 'routes/Domain',
            'parent' => 'app/Archive',
        ])->assertCreated();

        $this->getJson('/appyhp/api/directories/metadata?path=app%2FArchive%2FDomain%2FRules.php')
            ->assertOk()
            ->assertJsonPath('notes', 'Business rules used by billing.');
    }
}