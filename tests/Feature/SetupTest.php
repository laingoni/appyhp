<?php

namespace Alliswell\Appyhp\Tests\Feature;

use Alliswell\Appyhp\Tests\TestCase;

class SetupTest extends TestCase
{
    public function test_sidebar_visibility_is_remembered_in_setup(): void
    {
        $this->getJson('/appyhp/api/setup')
            ->assertOk()
            ->assertJsonPath('sidebarVisible', true);

        $this->putJson('/appyhp/api/setup', [
            'activePanel' => 'workflows',
            'autosave' => false,
            'sidebarVisible' => false,
        ])->assertOk()->assertJsonPath('sidebarVisible', false);

        $this->getJson('/appyhp/api/setup')
            ->assertOk()
            ->assertJsonPath('sidebarVisible', false);
    }
}
