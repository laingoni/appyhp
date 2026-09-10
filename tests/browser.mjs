import assert from 'node:assert/strict';
import { mkdtemp, readFile, writeFile, mkdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawn } from 'node:child_process';

const playwrightModule = process.env.APPYHP_PLAYWRIGHT || 'playwright';
const { chromium } = await import(playwrightModule.startsWith('/') ? pathToFileURL(playwrightModule).href : playwrightModule);
const project = await mkdtemp(join(tmpdir(), 'appyhp-browser-'));
const artifacts = process.env.APPYHP_BROWSER_ARTIFACTS || join(project, 'screenshots');
await mkdir(artifacts, { recursive: true });
await mkdir(join(project, 'routes'), { recursive: true });
await mkdir(join(project, 'app/Support'), { recursive: true });
await writeFile(join(project, 'routes/web.php'), "<?php\n// WEB_ROUTE_FILE\n");
await writeFile(join(project, 'routes/api.php'), "<?php\n// API_ROUTE_FILE\n");
await writeFile(join(project, 'app/Support/TaxRules.php'), "<?php\n// TAX_RULES_CONTRACT\n");
const port = Number(process.env.APPYHP_BROWSER_PORT || 8127);
const url = `http://127.0.0.1:${port}`;
const server = spawn('php', ['-S', `127.0.0.1:${port}`, resolve('tests/browser-router.php')], {
    env: { ...process.env, APPYHP_BROWSER_PROJECT: project }, stdio: ['ignore', 'pipe', 'pipe']
});
let serverLog = '';
server.stderr.on('data', (chunk) => { serverLog = (serverLog + chunk).slice(-20000); });
server.stdout.on('data', (chunk) => { serverLog = (serverLog + chunk).slice(-20000); });
let browser;
try {
    for (let attempt = 0; attempt < 100; attempt++) {
        if (server.exitCode !== null) throw new Error(serverLog);
        try { if ((await fetch(`${url}/appyhp/studio`)).ok) break; } catch {}
        await new Promise((done) => setTimeout(done, 100));
        if (attempt === 99) throw new Error(`Fixture server did not start: ${serverLog}`);
    }
    browser = await chromium.launch({ headless: true, executablePath: process.env.APPYHP_CHROME || '/usr/bin/google-chrome', args: ['--no-sandbox'] });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1050 } });
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.setDefaultTimeout(15000);
    await page.goto(`${url}/appyhp/studio`);
    await page.locator('.module-node').first().waitFor();
    await page.locator('[data-ai-settings]').click();
    await page.getByLabel('Model ID', { exact: true }).fill('fixture-model');
    await page.getByLabel('API key', { exact: true }).fill('fixture-key');
    await page.getByLabel('Typing delay (milliseconds)').fill('600');
    await page.getByRole('button', { name: 'Test connection', exact: true }).click();
    await page.getByText('Connected to fixture-model.', { exact: true }).waitFor();
    await page.screenshot({ path: join(artifacts, 'settings-desktop.png') });
    await page.getByRole('button', { name: 'Save settings', exact: true }).click();
    await page.locator('[data-ai-settings-dialog]').waitFor({ state: 'hidden' });
    assert.equal(await page.locator('#appyhp-studio').evaluate((element) => element.classList.contains('sidebar-closed')), false);
    await page.locator('.module-node[data-type="table"] .module-node-label').click();
    await page.waitForFunction(() => document.querySelector('[data-module-config-panel]').clientWidth > 1300);
    assert.equal(await page.locator('#appyhp-studio').evaluate((element) => element.classList.contains('module-editor-active')), true);
    assert.equal(await page.locator('#appyhp-studio').evaluate((element) => element.classList.contains('sidebar-closed')), true);
    const moduleEditorWidths = await page.evaluate(() => ({
        panel: document.querySelector('[data-module-config-panel]').getBoundingClientRect().width,
        body: document.querySelector('.module-config-body').getBoundingClientRect().width
    }));
    assert.ok(Math.abs(moduleEditorWidths.panel - moduleEditorWidths.body) < 2, 'The full-screen module body must not have large side margins');
    const prompt = page.getByLabel('What should this module do?', { exact: true });
    await page.getByRole('button', { name: 'Text Editor', exact: true }).click();
    await page.locator('[data-module-text-editor]').waitFor({ state: 'visible' });
    assert.equal(await page.locator('[data-ai-generate]').count(), 0, 'The text editor must replace the module configuration view');
    assert.equal(await page.locator('[data-module-text-editor-shell] .code-gutter').count(), 1);
    assert.equal(await page.locator('[data-module-text-editor-shell] .code-highlight').count(), 1);
    await page.locator('[data-module-text-content]').fill('Create customers with name and unique email.');
    await page.locator('[data-undo]').click();
    assert.equal(await page.locator('[data-module-text-content]').inputValue(), '');
    await page.locator('[data-redo]').click();
    assert.equal(await page.locator('[data-module-text-content]').inputValue(), 'Create customers with name and unique email.');
    await page.locator('[data-module-text-back]').click();
    assert.equal(await prompt.inputValue(), 'Create customers with name and unique email.');
    await page.waitForFunction(() => document.querySelector('[data-ai-code]').value.includes('Schema::create'));
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    assert.equal(await page.locator('[data-ai-table] tbody tr').count(), 3);
    assert.equal(await prompt.inputValue(), 'Create customers with name and unique email.');
    assert.equal(await page.locator('[data-ai-write]').isEnabled(), true);
    await page.screenshot({ path: join(artifacts, 'table-desktop-dark.png') });
    const firstCode = await page.locator('[data-ai-code]').inputValue();

    await prompt.fill('slow create users with status');
    await page.getByText('Streaming code', { exact: true }).waitFor();
    await prompt.fill('Create users with status and unique email.');
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    assert.equal(await prompt.inputValue(), 'Create users with status and unique email.');
    assert.equal(await page.locator('[data-ai-table] tbody tr').count(), 4);
    assert.match(await page.locator('[data-ai-code]').inputValue(), /status/);
    const target = await page.locator('[data-ai-path]').textContent();
    assert.doesNotMatch(await readFile(join(project, target), 'utf8'), /status/, 'An AI draft must remain unsaved while Auto-save is off');
    await page.locator('[data-undo]').click();
    await page.waitForFunction((previous) => document.querySelector('[data-ai-code]').value === previous, firstCode);
    await page.locator('[data-redo]').click();
    await page.waitForFunction(() => document.querySelector('[data-ai-code]').value.includes('status'));

    await page.locator('[data-autosave]').check();
    await page.locator('[data-manual-save]').click();
    await page.waitForFunction(() => !document.querySelector('[data-manual-save]').classList.contains('dirty'));
    assert.equal(await page.locator('[data-ai-write]').isEnabled(), true, 'Saving must not stale the generated draft');
    await page.locator('[data-ai-write]').click();
    await page.locator('[data-ai-message]').filter({ hasText: 'Written to ' }).waitFor();
    assert.match(await readFile(join(project, target), 'utf8'), /status/);
    await page.locator('[data-manual-save]').click();
    await page.waitForFunction(() => !document.querySelector('[data-manual-save]').classList.contains('dirty'));
    await page.reload();
    await page.locator('.module-node[data-type="table"] .module-node-label').click();
    assert.equal(await page.locator('[data-ai-table] tbody tr').count(), 4);
    assert.equal(await page.locator('[data-ai-write]').isEnabled(), true);

    await prompt.fill('simulate failure');
    await page.locator('[data-ai-message][data-error="true"]').waitFor();
    assert.match(await page.locator('[data-ai-message]').textContent(), /rate limit or quota/);
    assert.match(await page.locator('[data-ai-code]').inputValue(), /status/);
    assert.equal(await page.locator('[data-ai-write]').isEnabled(), false);
    await page.locator('[data-undo]').click();
    assert.equal(await prompt.inputValue(), 'Create users with status and unique email.');

    await page.locator('[data-ai-close]').click();
    assert.equal(await page.locator('#appyhp-studio').evaluate((element) => element.classList.contains('sidebar-closed')), false);
    await page.locator('.sidebar-toggle-button').click();
    assert.equal(await page.locator('#appyhp-studio').evaluate((element) => element.classList.contains('sidebar-closed')), true);
    await page.locator('.module-node[data-type="route"] .module-node-label').click();
    await page.waitForFunction(() => document.querySelector('[data-ai-code]').value.includes('WEB_ROUTE_FILE'));
    await page.getByLabel('Route file', { exact: true }).selectOption('api');
    await page.waitForFunction(() => document.querySelector('[data-ai-code]').value.includes('API_ROUTE_FILE'));
    assert.match(await readFile(join(project, 'routes/web.php'), 'utf8'), /WEB_ROUTE_FILE/);
    assert.match(await readFile(join(project, 'routes/api.php'), 'utf8'), /API_ROUTE_FILE/);
    await page.getByLabel('Route file', { exact: true }).selectOption('channels');
    await page.waitForFunction(() => document.querySelector('[data-ai-code]').value.includes('Facades\\Broadcast'));
    assert.match(await readFile(join(project, 'routes/channels.php'), 'utf8'), /Facades\\Broadcast/);
    await page.getByLabel('Route file', { exact: true }).selectOption('web');
    await page.waitForFunction(() => document.querySelector('[data-ai-code]').value.includes('WEB_ROUTE_FILE'));
    await page.getByRole('button', { name: 'Code editor', exact: true }).click();
    await page.locator('[data-module-file-editor]').waitFor({ state: 'visible' });
    assert.equal(await page.locator('[data-ai-prompt]').count(), 0, 'The file editor must replace the module configuration view');
    assert.equal(await page.locator('[data-module-file-editor-shell] .code-gutter').count(), 1);
    assert.ok(await page.locator('[data-module-file-editor-shell] .token-comment').count() > 0, 'PHP comments should be syntax highlighted');
    await page.locator('[data-module-file-content]').fill("<?php\n// WEB_ROUTE_FILE_EDITED\n");
    await page.locator('[data-module-file-back]').click();
    assert.equal(await page.locator('#appyhp-studio').evaluate((element) => element.classList.contains('module-editor-active')), true);
    assert.match(await page.locator('[data-ai-code]').inputValue(), /WEB_ROUTE_FILE_EDITED/);
    await page.getByRole('button', { name: 'Save file', exact: true }).click();
    await page.locator('[data-ai-message]').filter({ hasText: 'Written to routes/web.php.' }).waitFor();
    assert.match(await readFile(join(project, 'routes/web.php'), 'utf8'), /WEB_ROUTE_FILE_EDITED/);
    assert.match(await readFile(join(project, 'routes/api.php'), 'utf8'), /API_ROUTE_FILE/);
    await page.getByText('Laravel configuration', { exact: true }).click();
    await page.getByLabel('URI', { exact: true }).fill('/customers');
    await page.getByLabel('URI', { exact: true }).press('Tab');
    await page.locator('[data-ai-connections] .ai-connection').first().click();
    await prompt.fill('Return the customers for the connected route.');
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    const context = JSON.parse(await readFile(join(project, 'last-ai-context.json'), 'utf8'));
    assert.equal(context.workflow.modules.find((module) => module.type === 'route').config.uri, '/customers');
    assert.ok(context.workflow.edges.length > 0);

    await prompt.fill('request tax rules and implement customer totals');
    await page.locator('[data-ai-file-request]').waitFor({ state: 'visible' });
    assert.equal(await page.locator('[data-ai-file-request-path]').textContent(), 'app/Support/TaxRules.php');
    await page.getByRole('button', { name: 'Grant access', exact: true }).click();
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    const grantedContext = JSON.parse(await readFile(join(project, 'last-ai-context.json'), 'utf8'));
    assert.equal(grantedContext.file_access.decisions[0].status, 'granted');
    assert.match(grantedContext.file_access.decisions[0].source_file.content, /TAX_RULES_CONTRACT/);
    await prompt.fill('request tax rules but continue when denied');
    await page.locator('[data-ai-file-request]').waitFor({ state: 'visible' });
    await page.getByRole('button', { name: 'Deny and continue', exact: true }).click();
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    const deniedContext = JSON.parse(await readFile(join(project, 'last-ai-context.json'), 'utf8'));
    assert.equal(deniedContext.file_access.decisions[0].status, 'denied');
    assert.equal('source_file' in deniedContext.file_access.decisions[0], false);

    await page.locator('[data-ai-close]').click();
    assert.equal(await page.locator('#appyhp-studio').evaluate((element) => element.classList.contains('sidebar-closed')), true);
    await page.locator('.sidebar-toggle-button').click();
    await page.getByRole('button', { name: '+Inertia Page', exact: true }).click();
    await page.locator('.module-node[data-type="inertia-page"] .module-node-label').click();
    await prompt.fill('Show an Inertia users page.');
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    assert.match(await page.getByLabel('Filename', { exact: true }).inputValue(), /\.vue$/);
    await page.getByText('Laravel configuration', { exact: true }).click();
    for (const [framework, extension, adapter] of [['react', 'jsx', '@inertiajs/react'], ['svelte', 'svelte', '@inertiajs/svelte'], ['vue', 'vue', '@inertiajs/vue3']]) {
        await page.getByLabel('Framework', { exact: true }).selectOption(framework);
        await page.getByText('Draft ready.', { exact: true }).waitFor();
        assert.ok((await page.getByLabel('Filename', { exact: true }).inputValue()).endsWith(`.${extension}`));
        assert.ok((await page.locator('[data-ai-code]').inputValue()).includes(adapter));
    }

    await page.reload();
    await page.waitForFunction(() => document.querySelector('[data-autosave]').checked);
    await page.locator('[data-autosave]').uncheck();
    await page.locator('.module-node[data-type="route"] .module-node-label').click();
    await page.waitForFunction(() => document.querySelector('[data-ai-code]').value.includes('WEB_ROUTE_FILE_EDITED'));
    const originalModuleCode = await page.locator('[data-ai-code]').inputValue();
    const editedModuleCode = `${originalModuleCode.trimEnd()}\n// MODULE_HISTORY_EDIT\n`;
    await page.getByRole('button', { name: 'Code editor', exact: true }).click();
    await page.locator('[data-module-file-content]').fill(editedModuleCode);
    await page.locator('[data-module-file-back]').click();
    await page.locator('[data-panel-switcher]').click();
    await page.locator('.tree-entry[title="routes"]').click();
    await page.locator('.tree-entry[title="routes/web.php"]').click();
    const originalDirectoryCode = await page.locator('[data-file-editor]').inputValue();
    const editedDirectoryCode = `${originalDirectoryCode.trimEnd()}\n// DIRECTORY_HISTORY_EDIT\n`;
    await page.locator('[data-file-editor]').fill(editedDirectoryCode);
    await page.locator('[data-undo]').click();
    assert.equal(await page.locator('[data-file-editor]').inputValue(), originalDirectoryCode);
    await page.locator('[data-undo]').click();
    await page.locator('.studio-panel[data-studio-panel="workflows"]').waitFor({ state: 'visible' });
    assert.equal(await page.locator('[data-ai-code]').inputValue(), editedModuleCode);
    await page.locator('[data-undo]').click();
    assert.equal(await page.locator('[data-ai-code]').inputValue(), originalModuleCode);
    await page.locator('[data-redo]').click();
    assert.equal(await page.locator('[data-ai-code]').inputValue(), editedModuleCode);
    await page.locator('[data-redo]').click();
    await page.locator('.studio-panel[data-studio-panel="directories"]').waitFor({ state: 'visible' });
    await page.locator('[data-redo]').click();
    assert.equal(await page.locator('[data-file-editor]').inputValue(), editedDirectoryCode);

    await page.locator('[data-panel-switcher]').click();
    const routeModules = page.locator('.module-node[data-type="route"] .module-node-label');
    await routeModules.first().click();
    const sharedRoutePrompt = await page.getByLabel('What should this module do?', { exact: true }).inputValue();
    const sharedRouteUri = await page.getByLabel('URI', { exact: true }).inputValue();
    await page.locator('[data-ai-close]').click();
    await page.getByRole('button', { name: '+Route', exact: true }).click();
    await page.waitForFunction(() => document.querySelectorAll('.module-node[data-type="route"]').length === 2);
    await routeModules.last().click();
    assert.equal(await page.getByLabel('What should this module do?', { exact: true }).inputValue(), sharedRoutePrompt);
    assert.equal(await page.getByLabel('URI', { exact: true }).inputValue(), sharedRouteUri);
    await page.getByRole('button', { name: 'Text Editor', exact: true }).click();
    await page.locator('[data-module-text-content]').fill('Shared behavior for routes/web.php');
    await page.locator('[data-module-text-back]').click();
    await page.locator('[data-ai-close]').click();
    await routeModules.first().click();
    assert.equal(await page.getByLabel('What should this module do?', { exact: true }).inputValue(), 'Shared behavior for routes/web.php');
    await page.locator('[data-undo]').click();
    assert.equal(await page.getByLabel('What should this module do?', { exact: true }).inputValue(), sharedRoutePrompt);
    await page.locator('[data-redo]').click();
    assert.equal(await page.getByLabel('What should this module do?', { exact: true }).inputValue(), 'Shared behavior for routes/web.php');

    await page.locator('.theme-button').click();
    await page.screenshot({ path: join(artifacts, 'inertia-desktop-light.png') });
    assert.ok(firstCode.includes('Schema::create'));

    for (const width of [390, 320]) {
        await page.setViewportSize({ width, height: 844 });
        await page.reload();
        await page.locator('.module-node[data-type="table"] .module-node-label').click();
        await page.screenshot({ path: join(artifacts, `table-mobile-${width}.png`) });
        const overflow = await page.evaluate(() => {
            const panel = document.querySelector('[data-module-config-panel]');
            const header = document.querySelector('.studio-header');
            return { panel: panel.scrollWidth > panel.clientWidth + 1, header: header.scrollWidth > header.clientWidth + 1, page: document.documentElement.scrollWidth > innerWidth };
        });
        assert.deepEqual(overflow, { panel: false, header: false, page: false });
        await page.locator('[data-ai-settings]').click();
        await page.screenshot({ path: join(artifacts, `settings-mobile-${width}.png`) });
        assert.equal(await page.locator('#ai-api-key').inputValue(), '');
        await page.locator('[data-ai-settings-close]').click();
    }
    assert.deepEqual(errors, []);
    console.log(JSON.stringify({ passed: true, checks: ['settings', 'streaming', 'cancellation', 'table schema', 'full-canvas module editor', 'nested file editor', 'sidebar restoration', 'draft save boundary', 'undo/redo', 'chronological cross-panel history', 'shared file module attributes', 'file permission grant/deny', 'file write', 'save/reload', 'provider failure', 'connected context', 'Vue', 'React', 'Svelte', 'desktop/mobile'], artifacts, project }));
} catch (error) {
    console.error(`Browser artifacts: ${artifacts}`);
    if (browser) {
        const page = browser.contexts()[0]?.pages()[0];
        if (page) {
            await page.screenshot({ path: join(artifacts, 'failure.png') });
            console.error((await page.locator('body').innerText()).slice(-3500));
        }
    }
    console.error(serverLog.slice(-3000));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill('SIGTERM');
    await new Promise((done) => { if (server.exitCode !== null) done(); else server.once('exit', done); });
}
