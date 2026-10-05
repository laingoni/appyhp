<?php

namespace Alliswell\Appyhp\Tests\Feature;

use Alliswell\Appyhp\Support\RuntimeStorage;
use Alliswell\Appyhp\Tests\FixtureApplication;
use Alliswell\Appyhp\Tests\TestCase;

class WorkflowPersistenceTest extends TestCase
{
    public function test_reading_workflows_does_not_publish_or_rewrite_them(): void
    {
        $this->getJson('/appyhp/api/workflows')->assertOk()->assertJsonPath('revision', null);
        $this->assertFileDoesNotExist(app(RuntimeStorage::class)->path('workflows.json'));

        $this->putJson('/appyhp/api/workflows', ['workflows' => [FixtureApplication::workflow()]])->assertOk();
        $path = app(RuntimeStorage::class)->path('workflows.json');
        $contents = file_get_contents($path);
        $this->getJson('/appyhp/api/workflows')->assertOk()->assertJsonPath('revision', hash('sha256', $contents));
        $this->assertSame($contents, file_get_contents($path));
    }

    public function test_deleting_all_workflows_survives_reload(): void
    {
        $this->putJson('/appyhp/api/workflows', ['workflows' => []])->assertOk();
        $this->getJson('/appyhp/api/workflows')->assertOk()->assertJsonPath('workflows', []);
    }

    public function test_stale_tabs_cannot_overwrite_newer_workflows(): void
    {
        $first = $this->putJson('/appyhp/api/workflows', [
            'workflows' => [FixtureApplication::workflow()], 'expectedRevision' => null,
        ])->assertOk()->json('revision');
        $this->putJson('/appyhp/api/workflows', [
            'workflows' => [], 'expectedRevision' => $first,
        ])->assertOk();
        $this->putJson('/appyhp/api/workflows', [
            'workflows' => [FixtureApplication::workflow()], 'expectedRevision' => $first,
        ])->assertConflict();
        $this->getJson('/appyhp/api/workflows')->assertJsonPath('workflows', []);
    }

    public function test_corrupted_state_is_preserved_for_recovery(): void
    {
        $runtime = app(RuntimeStorage::class);
        $runtime->ensure();
        file_put_contents($runtime->path('workflows.json'), '{interrupted');
        $this->getJson('/appyhp/api/workflows')->assertUnprocessable();
        $this->putJson('/appyhp/api/workflows', ['workflows' => []])->assertUnprocessable();
        $this->assertSame('{interrupted', file_get_contents($runtime->path('workflows.json')));
    }

    public function test_graph_rejects_duplicate_ids_and_missing_connection_targets(): void
    {
        $workflow = FixtureApplication::workflow();
        $this->putJson('/appyhp/api/workflows', ['workflows' => [$workflow, $workflow]])->assertUnprocessable();
        $workflow['modules'][1]['id'] = 'route';
        $this->putJson('/appyhp/api/workflows', ['workflows' => [$workflow]])->assertUnprocessable();
        $workflow = FixtureApplication::workflow();
        $workflow['edges'][0]['to'] = 'missing';
        $this->putJson('/appyhp/api/workflows', ['workflows' => [$workflow]])->assertUnprocessable();
    }
}
