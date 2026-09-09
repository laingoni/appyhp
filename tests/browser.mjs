import assert from 'node:assert/strict';
import { mkdtemp, readFile, mkdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawn } from 'node:child_process';

const playwrightModule = process.env.APPYHP_PLAYWRIGHT || 'playwright';
const { chromium } = await import(playwrightModule.startsWith('/') ? pathToFileURL(playwrightModule).href : playwrightModule);
const project = await mkdtemp(join(tmpdir(), 'appyhp-browser-'));
const artifacts = process.env.APPYHP_BROWSER_ARTIFACTS || join(project, 'screenshots');
await mkdir(artifacts, { recursive: true });
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
    await page.locator('.module-node[data-type="table"] .module-node-label').click();
    const prompt = page.getByLabel('What should this module do?', { exact: true });
    await prompt.fill('Create customers with name and unique email.');
    await page.locator('[data-ai-code]').filter({ hasText: 'Schema::create' }).waitFor();
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    assert.equal(await page.locator('[data-ai-table] tbody tr').count(), 3);
    assert.equal(await prompt.inputValue(), 'Create customers with name and unique email.');
    assert.equal(await page.locator('[data-ai-write]').isEnabled(), true);
    await page.screenshot({ path: join(artifacts, 'table-desktop-dark.png') });
    const firstCode = await page.locator('[data-ai-code]').textContent();

    await prompt.fill('slow create users with status');
    await page.getByText('Streaming code', { exact: true }).waitFor();
    await prompt.fill('Create users with status and unique email.');
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    assert.equal(await prompt.inputValue(), 'Create users with status and unique email.');
    assert.equal(await page.locator('[data-ai-table] tbody tr').count(), 4);
    assert.match(await page.locator('[data-ai-code]').textContent(), /status/);

    await page.locator('[data-autosave]').check();
    await page.locator('[data-manual-save]').click();
    await page.waitForFunction(() => !document.querySelector('[data-manual-save]').classList.contains('dirty'));
    assert.equal(await page.locator('[data-ai-write]').isEnabled(), true, 'Saving must not stale the generated draft');
    await page.locator('[data-ai-write]').click();
    await page.locator('[data-ai-message]').filter({ hasText: 'Written to ' }).waitFor();
    const target = await page.locator('[data-ai-path]').textContent();
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
    assert.match(await page.locator('[data-ai-code]').textContent(), /status/);
    assert.equal(await page.locator('[data-ai-write]').isEnabled(), false);
    await page.locator('[data-undo]').click();
    assert.equal(await prompt.inputValue(), 'Create users with status and unique email.');

    await page.locator('[data-ai-close]').click();
    await page.locator('.module-node[data-type="route"] .module-node-label').click();
    await page.getByText('Laravel configuration', { exact: true }).click();
    await page.getByLabel('URI', { exact: true }).fill('/customers');
    await page.getByLabel('URI', { exact: true }).press('Tab');
    await page.locator('[data-ai-connections] .ai-connection').first().click();
    await prompt.fill('Return the customers for the connected route.');
    await page.getByText('Draft ready.', { exact: true }).waitFor();
    const context = JSON.parse(await readFile(join(project, 'last-ai-context.json'), 'utf8'));
    assert.equal(context.workflow.modules.find((module) => module.type === 'route').config.uri, '/customers');
    assert.ok(context.workflow.edges.length > 0);

    await page.locator('[data-ai-close]').click();
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
        assert.ok((await page.locator('[data-ai-code]').textContent()).includes(adapter));
    }
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
    console.log(JSON.stringify({ passed: true, checks: ['settings', 'streaming', 'cancellation', 'table schema', 'file write', 'save/reload', 'provider failure', 'undo', 'connected context', 'Vue', 'React', 'Svelte', 'desktop/mobile'], artifacts, project }));
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
