var aiSettings = null;
var aiProject = { frontend: 'blade', folders: [] };
var aiJob = null;
var aiTimer = null;
var aiPreview = null;
var aiFeedback = { message: '', error: false };
var aiConfigKey = '';
var aiWriting = false;
var aiDialog = document.querySelector('[data-ai-settings-dialog]');
var aiForm = document.querySelector('[data-ai-settings-form]');
var aiSettingsButton = shell.querySelector('[data-ai-settings]');
var aiProviderDefaults = { openai: 'https://api.openai.com/v1', compatible: 'http://localhost:11434/v1', anthropic: 'https://api.anthropic.com/v1' };
aiSettingsButton.innerHTML = workflowCardActionIcon('settings');

function aiUrl(path) {
    return new URL('/appyhp/api/ai/' + path, window.location.origin);
}

function workflowFrontend() {
    var workflow = workflowStore.getActiveWorkflow();
    return (workflow && workflow.meta.frontend) || aiProject.frontend || 'blade';
}

function inertiaExtension(framework, language) {
    if (framework === 'react') return language === 'typescript' ? 'tsx' : 'jsx';
    return framework === 'svelte' ? 'svelte' : 'vue';
}

function defaultModuleTarget(type, config, frontend) {
    var folders = {
        route: 'routes', controller: 'app/Http/Controllers', middleware: 'app/Http/Middleware',
        request: 'app/Http/Requests', resource: 'app/Http/Resources', model: 'app/Models',
        table: 'database/migrations', migration: 'database/migrations', factory: 'database/factories',
        seeder: 'database/seeders', service: 'app/Services', repository: 'app/Repositories',
        policy: 'app/Policies', job: 'app/Jobs', command: 'app/Console/Commands', event: 'app/Events',
        listener: 'app/Listeners', notification: 'app/Notifications', mail: 'app/Mail',
        queue: 'config', auth: 'config', cache: 'config', storage: 'config',
        component: 'app/View/Components', view: 'resources/views',
        'inertia-page': 'resources/js/Pages', 'inertia-middleware': 'app/Http/Middleware'
    };
    var folder = folders[type] || 'app/Services';
    var filename = String(config.class || 'Module').split('\\').pop() + '.php';
    if (type === 'route') filename = 'web.php';
    if (['auth', 'queue', 'cache', 'storage'].includes(type)) filename = (type === 'storage' ? 'filesystems' : type) + '.php';
    if (type === 'table' || type === 'migration') {
        var stamp = new Date().toISOString().slice(0, 19).replace(/[-:T]/g, '').replace(/^(\d{4})(\d{2})(\d{2})(\d{6})$/, '$1_$2_$3_$4');
        var name = type === 'table' ? 'create_' + (config.name || 'records') + '_table' : (config.name || 'update_records_table');
        filename = stamp + '_' + name.replace(/[^A-Za-z0-9_]/g, '_') + '.php';
    }
    if (type === 'view' || type === 'inertia-page') {
        var page = String(type === 'view' ? (config.path || 'welcome') : (config.page || 'Users/Index'));
        var parts = (type === 'view' ? page.replace(/\./g, '/') : page).split('/').filter(Boolean);
        var leaf = parts.pop() || 'Index';
        if (parts.length) folder += '/' + parts.join('/');
        var framework = config.framework && config.framework !== 'inherit' ? config.framework : (frontend || aiProject.frontend || 'vue');
        filename = leaf + (type === 'view' ? '.blade.php' : '.' + inertiaExtension(framework, config.language));
    }
    return { folder: folder, filename: filename, prompt: '', live: true };
}

function workflowFingerprint(workflow) {
    var value = JSON.stringify({
        name: workflow.name, description: workflow.description,
        frontend: workflow.meta.frontend || aiProject.frontend || 'blade',
        modules: workflow.modules.map(function (module) {
            var config = Object.assign({}, module.config);
            delete config.ai;
            delete config.live;
            return { id: module.id, type: module.type, label: module.label, description: module.description, config: config, code: (module.config.ai || {}).code || '' };
        }),
        edges: workflow.edges.map(function (edge) { return { from: edge.from, to: edge.to, label: edge.label }; })
    });
    var hash = 2166136261;
    for (var i = 0; i < value.length; i += 1) hash = Math.imul(hash ^ value.charCodeAt(i), 16777619);
    return (hash >>> 0).toString(16) + ':' + value.length;
}

function loadAiSettings() {
    return requestJson(aiUrl('settings')).then(function (payload) {
        aiSettings = payload.settings;
        aiProject = payload.project;
        aiSettingsButton.title = aiSettings.configured ? 'AI settings: ' + aiSettings.model : 'AI settings';
        renderModuleConfig(workflowStore.getState());
        var frontend = shell.querySelector('[data-workflow-frontend]');
        frontend.value = workflowFrontend();
    }).catch(function (error) {
        aiFeedback = { message: error.message || 'Unable to load AI settings.', error: true };
        renderModuleConfig(workflowStore.getState());
    });
}

function settingsPayload() {
    return {
        provider: aiForm.elements.provider.value,
        base_url: aiForm.elements.base_url.value.trim(),
        model: aiForm.elements.model.value.trim(),
        api_key: aiForm.elements.api_key.value,
        clear_key: aiForm.elements.clear_key.checked,
        live: aiForm.elements.live.checked,
        debounce_ms: Number(aiForm.elements.debounce_ms.value)
    };
}

function settingsStatus(message, error) {
    var status = aiForm.querySelector('[data-ai-settings-status]');
    status.textContent = message;
    status.dataset.error = error ? 'true' : 'false';
}

function refreshKeyPlaceholder() {
    var same = aiSettings && aiForm.elements.provider.value === aiSettings.provider
        && aiForm.elements.base_url.value.trim().replace(/\/+$/, '') === aiSettings.base_url;
    var hasKey = Boolean(same && aiSettings.has_key);
    aiForm.elements.api_key.placeholder = hasKey ? 'Saved key; leave blank to keep' : 'API key';
    aiForm.querySelector('[data-ai-remove-key]').hidden = !hasKey;
    if (!hasKey) aiForm.elements.clear_key.checked = false;
}

function openAiSettings() {
    cancelAiGeneration();
    var values = aiSettings || { provider: 'openai', base_url: aiProviderDefaults.openai, model: '', live: true, debounce_ms: 1200 };
    aiForm.elements.provider.value = values.provider;
    aiForm.elements.base_url.value = values.base_url;
    aiForm.elements.model.value = values.model;
    aiForm.elements.api_key.value = '';
    aiForm.elements.clear_key.checked = false;
    aiForm.elements.live.checked = values.live;
    aiForm.elements.debounce_ms.value = values.debounce_ms;
    refreshKeyPlaceholder();
    settingsStatus('', false);
    aiDialog.showModal();
}

aiSettingsButton.addEventListener('click', openAiSettings);
aiForm.querySelector('[data-ai-settings-close]').addEventListener('click', function () { aiDialog.close(); });
aiDialog.addEventListener('close', function () {
    aiForm.elements.api_key.value = '';
    aiForm.elements.clear_key.checked = false;
    renderModuleConfig(workflowStore.getState());
});
aiForm.elements.provider.addEventListener('change', function () {
    aiForm.elements.base_url.value = aiProviderDefaults[aiForm.elements.provider.value];
    aiForm.elements.api_key.value = '';
    refreshKeyPlaceholder();
});
aiForm.elements.base_url.addEventListener('input', refreshKeyPlaceholder);
aiForm.elements.api_key.addEventListener('input', function () { aiForm.elements.clear_key.checked = false; });
aiForm.addEventListener('submit', function (event) {
    event.preventDefault();
    var payload = settingsPayload();
    aiForm.querySelector('fieldset').disabled = true;
    settingsStatus('Saving...', false);
    postJson(aiUrl('settings'), payload, 'PUT').then(function (response) {
        aiSettings = response.settings;
        aiSettingsButton.title = aiSettings.configured ? 'AI settings: ' + aiSettings.model : 'AI settings';
        aiFeedback = { message: aiSettings.configured ? 'AI settings saved.' : 'Add an API key before generating.', error: false };
        aiDialog.close();
    }).catch(function (error) {
        settingsStatus(error.message, true);
    }).finally(function () { aiForm.querySelector('fieldset').disabled = false; });
});
aiForm.querySelector('[data-ai-test]').addEventListener('click', function () {
    if (!aiForm.reportValidity()) return;
    var payload = settingsPayload();
    aiForm.querySelector('fieldset').disabled = true;
    settingsStatus('Connecting...', false);
    postJson(aiUrl('test'), payload).then(function (response) {
        settingsStatus(response.message, false);
    }).catch(function (error) {
        settingsStatus(error.message, true);
    }).finally(function () { aiForm.querySelector('fieldset').disabled = false; });
});

function cancelAiGeneration(message) {
    if (aiTimer) window.clearTimeout(aiTimer);
    aiTimer = null;
    var previous = aiJob;
    aiJob = null;
    if (previous) previous.controller.abort();
    aiPreview = null;
    if (message) aiFeedback = { message: message, error: false };
}

function queueModuleGeneration() {
    if (aiTimer) window.clearTimeout(aiTimer);
    aiTimer = null;
    var module = workflowStore.getSelectedModule();
    if (!module || !aiSettings || !aiSettings.configured || !aiSettings.live || module.config.live === false || aiWriting || aiDialog.open) return;
    if (!(module.config.prompt || '').trim()) return;
    aiFeedback = { message: 'Waiting for typing...', error: false };
    renderAiOutput(module, workflowStore.getActiveWorkflow());
    aiTimer = window.setTimeout(function () { aiTimer = null; generateModule(); }, aiSettings.debounce_ms);
}

function streamCode(source) {
    var opening = source.match(/^\s*```[a-z]+\r?\n/);
    if (!opening) return '';
    var content = source.slice(opening[0].length);
    var closing = content.indexOf('\n```');
    return closing >= 0 ? content.slice(0, closing).replace(/\r$/, '') : content.replace(/\n`{1,2}$/, '');
}

async function generateModule() {
    if (aiWriting) return;
    var workflow = workflowStore.getActiveWorkflow();
    var module = workflowStore.getSelectedModule();
    if (!workflow || !module) return;
    if (!aiSettings || !aiSettings.configured) { openAiSettings(); return; }
    cancelAiGeneration();
    if (!(module.config.prompt || '').trim()) {
        aiFeedback = { message: 'Describe what this module should do.', error: true };
        renderAiOutput(module, workflow);
        return;
    }
    var job = {
        controller: new AbortController(), workflowId: workflow.id, moduleId: module.id,
        fingerprint: workflowFingerprint(workflow), raw: '', complete: false
    };
    aiJob = job;
    aiPreview = { code: '' };
    aiFeedback = { message: 'Generating with ' + aiSettings.model + '...', error: false };
    renderAiOutput(module, workflow);
    var reader;
    try {
        var response = await fetch(aiUrl('generate'), {
            method: 'POST', signal: job.controller.signal,
            headers: { Accept: 'text/event-stream', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ moduleId: module.id, workflow: workflow })
        });
        if (!response.ok) {
            var payload = await response.json().catch(function () { return {}; });
            throw new Error(payload.message || 'Generation failed (HTTP ' + response.status + ').');
        }
        if (!response.body || !(response.headers.get('content-type') || '').includes('text/event-stream')) {
            throw new Error('The server did not return a code stream. Check the session and provider settings.');
        }
        reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';
        function handleFrame(frame) {
            if (aiJob !== job || job.controller.signal.aborted) return;
            var lines = frame.split(/\r?\n/);
            var event = 'message';
            var data = [];
            lines.forEach(function (line) {
                if (line.startsWith('event:')) event = line.slice(6).trim();
                if (line.startsWith('data:')) data.push(line.slice(5).replace(/^ /, ''));
            });
            if (!data.length) return;
            var payload = JSON.parse(data.join('\n'));
            if (event === 'error') throw new Error(payload.message || 'Generation failed.');
            if (event === 'delta') {
                job.raw += payload.text;
                aiPreview.code = streamCode(job.raw);
                renderAiOutput(workflowStore.getSelectedModule(), workflowStore.getActiveWorkflow());
            }
            if (event === 'done') {
                var currentWorkflow = workflowStore.getActiveWorkflow();
                if (!currentWorkflow || currentWorkflow.id !== job.workflowId || workflowFingerprint(currentWorkflow) !== job.fingerprint) {
                    throw new Error('The workflow changed during generation. Generate again.');
                }
                var predicted = cloneWorkflowValue(currentWorkflow);
                var next = predicted.modules.find(function (entry) { return entry.id === job.moduleId; });
                var result = Object.assign({}, payload);
                delete result.config;
                next.config = Object.assign({}, next.config, payload.config, { ai: result });
                result.contextHash = workflowFingerprint(predicted);
                job.complete = true;
                aiJob = null;
                aiPreview = null;
                aiFeedback = { message: 'Draft ready.', error: false };
                workflowStore.updateModule(job.moduleId, { config: next.config });
            }
        }
        while (!job.complete && !job.controller.signal.aborted) {
            var chunk = await reader.read();
            buffer += decoder.decode(chunk.value || new Uint8Array(), { stream: !chunk.done });
            var boundary;
            while ((boundary = buffer.match(/\r?\n\r?\n/))) {
                var frame = buffer.slice(0, boundary.index);
                buffer = buffer.slice(boundary.index + boundary[0].length);
                handleFrame(frame);
            }
            if (chunk.done) {
                if (buffer.trim()) handleFrame(buffer);
                break;
            }
        }
        if (!job.complete && !job.controller.signal.aborted) throw new Error('The code stream ended early. The previous draft has been kept.');
    } catch (error) {
        if (aiJob === job && !job.controller.signal.aborted) {
            aiFeedback = { message: error.message, error: true };
        }
    } finally {
        if (reader) await reader.cancel().catch(function () {});
        if (aiJob === job) {
            aiJob = null;
            aiPreview = null;
        }
        renderModuleConfig(workflowStore.getState());
    }
}

function renderAiModuleConfig(state) {
    var workflow = state.workflows.find(function (entry) { return entry.id === state.selectedWorkflowId; });
    var module = workflow && workflow.modules.find(function (entry) { return entry.id === state.selectedModuleId; });
    var key = module ? workflow.id + ':' + module.id : '';
    if (key !== aiConfigKey) {
        cancelAiGeneration();
        aiConfigKey = key;
        aiFeedback = { message: '', error: false };
        moduleConfigPanel.innerHTML = '';
    }
    moduleConfigPanel.classList.toggle('active', Boolean(module));
    if (!module) return;
    if (aiJob && aiJob.fingerprint !== workflowFingerprint(workflow)) cancelAiGeneration('Workflow changed. Generate again.');

    if (!moduleConfigPanel.firstChild) {
        var definition = moduleDefinition(module.type);
        moduleConfigPanel.innerHTML = '<header class="module-config-header"><span><span class="workflow-title" data-ai-module-title></span><span class="workflow-meta" data-ai-module-kind></span></span><button type="button" class="ai-close" data-ai-close aria-label="Close module configuration" title="Close">&times;</button></header>' +
            '<div class="module-config-body"><div data-ai-name></div><div class="ai-target-fields" data-ai-target></div><div data-ai-prompt></div>' +
            '<div class="ai-toolbar"><label class="ai-live"><input type="checkbox" data-ai-live>Live</label><button class="workflow-action" type="button" data-ai-stop hidden>Stop</button><button class="workflow-action ai-primary" type="button" data-ai-generate>Generate</button><button class="workflow-action" type="button" data-ai-setup>AI settings</button></div>' +
            '<p class="ai-status" role="status" aria-live="polite" data-ai-message></p>' +
            '<div class="ai-toolbar"><span class="ai-status" data-ai-code-state>Generated code</span><button class="workflow-action" type="button" data-ai-copy>Copy</button><button class="workflow-action" type="button" data-ai-download>Download</button></div>' +
            '<pre class="ai-code" tabindex="0" aria-label="Generated code"><code data-ai-code></code></pre>' +
            '<section class="ai-section" data-ai-table hidden></section>' +
            '<div class="ai-toolbar"><span class="ai-status" data-ai-path></span><button class="workflow-action ai-primary" type="button" data-ai-write>Write file</button></div>' +
            '<p class="ai-status" data-ai-summary></p>' +
            '<section class="ai-section" data-ai-suggestions hidden></section>' +
            '<section class="ai-section"><h3 class="ai-section-title">Connections</h3><div class="ai-connections" data-ai-connections></div></section>' +
            '<details class="ai-section"><summary>Laravel configuration</summary><div class="ai-fields" data-ai-fields></div></details></div>';
        moduleConfigPanel.querySelector('[data-ai-module-kind]').textContent = definition.title;
        addConfigField(moduleConfigPanel.querySelector('[data-ai-name]'), module, { key: 'label', label: 'Module name', type: 'text', root: true });
        var folder = addConfigField(moduleConfigPanel.querySelector('[data-ai-target]'), module, { key: 'folder', label: 'Folder', type: 'text' });
        folder.setAttribute('list', 'ai-folder-options');
        folder.autocomplete = 'off';
        folder.spellcheck = false;
        var datalist = document.createElement('datalist');
        datalist.id = 'ai-folder-options';
        folder.parentElement.appendChild(datalist);
        addConfigField(moduleConfigPanel.querySelector('[data-ai-target]'), module, { key: 'filename', label: 'Filename', type: 'text' });
        var prompt = addConfigField(moduleConfigPanel.querySelector('[data-ai-prompt]'), module, { key: 'prompt', label: 'What should this module do?', type: 'textarea' });
        prompt.maxLength = 16000;
        var fields = moduleConfigPanel.querySelector('[data-ai-fields]');
        addConfigField(fields, module, { key: 'description', label: 'Description', type: 'textarea', root: true });
        (definition.fields || []).forEach(function (field) { addConfigField(fields, module, field); });
        moduleConfigPanel.querySelector('[data-ai-close]').addEventListener('click', function () { workflowStore.selectModule(null); });
        moduleConfigPanel.querySelector('[data-ai-generate]').addEventListener('click', generateModule);
        moduleConfigPanel.querySelector('[data-ai-setup]').addEventListener('click', openAiSettings);
        moduleConfigPanel.querySelector('[data-ai-stop]').addEventListener('click', function () {
            cancelAiGeneration('Generation stopped.');
            renderModuleConfig(workflowStore.getState());
        });
        moduleConfigPanel.querySelector('[data-ai-live]').addEventListener('change', function (event) {
            if (!event.target.checked) cancelAiGeneration('Live generation paused.');
            workflowStore.updateModule(module.id, { config: { live: event.target.checked } });
            if (event.target.checked) queueModuleGeneration();
        });
        moduleConfigPanel.querySelector('[data-ai-copy]').addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(displayedAiCode());
                aiFeedback = { message: 'Code copied.', error: false };
            } catch (error) { aiFeedback = { message: 'Clipboard is unavailable. Download the code instead.', error: true }; }
            renderModuleConfig(workflowStore.getState());
        });
        moduleConfigPanel.querySelector('[data-ai-download]').addEventListener('click', function () {
            var selected = workflowStore.getSelectedModule();
            if (!selected) return;
            var url = URL.createObjectURL(new Blob([displayedAiCode()], { type: 'text/plain;charset=utf-8' }));
            var link = document.createElement('a');
            link.href = url;
            link.download = selected.config.filename || 'module.txt';
            link.click();
            window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        });
        moduleConfigPanel.querySelector('[data-ai-write]').addEventListener('click', writeGeneratedFile);
    }

    moduleConfigPanel.querySelector('[data-ai-module-title]').textContent = module.label;
    moduleConfigPanel.querySelectorAll('[data-config-key]').forEach(function (control) {
        var value = control.dataset.configRoot === 'true' ? module[control.dataset.configKey] : module.config[control.dataset.configKey];
        var next = value == null ? '' : String(value);
        if (control.value !== next) control.value = next;
    });
    var folderOptions = Array.from(new Set(aiProject.folders.concat([module.config.folder])));
    moduleConfigPanel.querySelector('#ai-folder-options').innerHTML = folderOptions.map(function (folder) { return '<option value="' + escapeHtml(folder) + '"></option>'; }).join('');
    var live = moduleConfigPanel.querySelector('[data-ai-live]');
    live.checked = module.config.live !== false;
    live.disabled = !aiSettings || !aiSettings.live;
    live.title = live.disabled ? 'Enable live generation in AI settings' : 'Live generation';
    renderAiOutput(module, workflow);
    renderAiConnections(module, workflow);
}

function displayedAiCode() {
    var module = workflowStore.getSelectedModule();
    return aiPreview ? aiPreview.code : (module && module.config.ai && module.config.ai.code) || '';
}

function renderAiOutput(module, workflow) {
    if (!module || !workflow || !moduleConfigPanel.firstChild) return;
    var result = module.config.ai || {};
    var code = displayedAiCode();
    var stale = Boolean(result.code && result.contextHash !== workflowFingerprint(workflow));
    var path = module.config.folder + '/' + module.config.filename;
    var codeEl = moduleConfigPanel.querySelector('[data-ai-code]');
    if (codeEl.dataset.source !== code) {
        var frame = codeEl.parentElement;
        var follow = frame.scrollHeight - frame.scrollTop - frame.clientHeight < 35;
        codeEl.innerHTML = code ? highlightCode(code, detectLanguage(path)) : 'No generated code.';
        codeEl.dataset.source = code;
        if (follow && aiJob) frame.scrollTop = frame.scrollHeight;
    }
    var status = moduleConfigPanel.querySelector('[data-ai-message]');
    status.textContent = aiFeedback.message || (!aiSettings ? 'Loading AI settings...' : (!aiSettings.configured ? 'AI provider not configured.' : ''));
    status.dataset.error = aiFeedback.error ? 'true' : 'false';
    var codeState = moduleConfigPanel.querySelector('[data-ai-code-state]');
    codeState.textContent = aiJob ? 'Streaming code' : stale ? 'Workflow changed since generation' : result.code ? 'Generated code' : 'Code draft';
    codeState.dataset.stale = stale ? 'true' : 'false';
    moduleConfigPanel.querySelector('[data-ai-stop]').hidden = !aiJob && !aiTimer;
    moduleConfigPanel.querySelector('[data-ai-generate]').disabled = Boolean(aiJob || aiWriting);
    moduleConfigPanel.querySelector('[data-ai-setup]').hidden = Boolean(aiSettings && aiSettings.configured);
    moduleConfigPanel.querySelector('[data-ai-copy]').disabled = !code;
    moduleConfigPanel.querySelector('[data-ai-download]').disabled = !code;
    moduleConfigPanel.querySelector('[data-ai-write]').disabled = !result.code || Boolean(aiJob) || stale || aiWriting || result.path !== path;
    moduleConfigPanel.querySelector('[data-ai-write]').textContent = aiWriting ? 'Writing...' : 'Write file';
    moduleConfigPanel.querySelector('[data-ai-path]').textContent = path;
    moduleConfigPanel.querySelector('[data-ai-summary]').textContent = result.summary || '';
    renderAiTable(result.table, Boolean(aiJob || stale));
    renderAiSuggestions(result.suggestions || [], workflow, stale);
}

function renderAiTable(table, stale) {
    var container = moduleConfigPanel.querySelector('[data-ai-table]');
    container.hidden = !table;
    if (!table) { container.innerHTML = ''; return; }
    var cacheKey = JSON.stringify([table, stale]);
    if (container.dataset.snapshot === cacheKey) return;
    container.dataset.snapshot = cacheKey;
    container.innerHTML = '<h3 class="ai-section-title"></h3><div class="ai-table-scroll"><table class="ai-schema"><thead><tr><th scope="col">Column</th><th scope="col">Type</th><th scope="col">Constraints</th><th scope="col">Default</th></tr></thead><tbody></tbody></table></div>';
    container.querySelector('h3').textContent = table.name + (stale ? ' / previous schema' : ' / schema');
    var body = container.querySelector('tbody');
    (table.columns || []).forEach(function (column) {
        var row = document.createElement('tr');
        [column.name, column.type, [column.key, column.nullable ? 'nullable' : 'not null'].filter(Boolean).join(', '), column.default == null ? '-' : column.default].forEach(function (value) {
            var cell = document.createElement('td');
            cell.textContent = value;
            row.appendChild(cell);
        });
        body.appendChild(row);
    });
}

function renderAiSuggestions(suggestions, workflow, stale) {
    var container = moduleConfigPanel.querySelector('[data-ai-suggestions]');
    container.hidden = !suggestions.length;
    var cacheKey = JSON.stringify([suggestions, stale, workflow.modules.map(function (module) { return [module.id, module.label]; })]);
    if (container.dataset.snapshot === cacheKey) return;
    container.dataset.snapshot = cacheKey;
    container.innerHTML = '<h3 class="ai-section-title">' + (stale ? 'Previous connection review' : 'Connection review') + '</h3>';
    suggestions.forEach(function (suggestion) {
        var item = document.createElement('div');
        item.className = 'ai-suggestion';
        var message = document.createElement('p');
        message.textContent = suggestion.message;
        item.appendChild(message);
        var related = workflow.modules.find(function (module) { return module.id === suggestion.moduleId; });
        if (related) {
            var review = document.createElement('button');
            review.type = 'button';
            review.className = 'ai-connection';
            review.textContent = 'Review ' + related.label;
            review.addEventListener('click', function () { workflowStore.selectModule(related.id); });
            item.appendChild(review);
        }
        container.appendChild(item);
    });
}

function renderAiConnections(module, workflow) {
    var container = moduleConfigPanel.querySelector('[data-ai-connections]');
    var edges = workflow.edges.filter(function (edge) { return edge.from === module.id || edge.to === module.id; });
    var cacheKey = JSON.stringify([edges, workflow.modules.map(function (entry) { return [entry.id, entry.label, entry.config.folder, entry.config.filename]; })]);
    if (container.dataset.snapshot === cacheKey) return;
    container.dataset.snapshot = cacheKey;
    container.innerHTML = '';
    if (!edges.length) { container.textContent = 'No connected modules.'; return; }
    edges.forEach(function (edge) {
        var incoming = edge.to === module.id;
        var related = workflow.modules.find(function (entry) { return entry.id === (incoming ? edge.from : edge.to); });
        if (!related) return;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ai-connection';
        var title = document.createElement('span');
        title.textContent = incoming ? related.label + ' -> ' + module.label : module.label + ' -> ' + related.label;
        var detail = document.createElement('small');
        detail.textContent = (edge.label ? edge.label + ' / ' : '') + related.config.folder + '/' + related.config.filename;
        button.append(title, detail);
        button.addEventListener('click', function () { workflowStore.selectModule(related.id); });
        container.appendChild(button);
    });
}

async function writeGeneratedFile() {
    var workflow = workflowStore.getActiveWorkflow();
    var module = workflowStore.getSelectedModule();
    if (!module || !module.config.ai || aiWriting || aiJob) return;
    var result = cloneWorkflowValue(module.config.ai);
    if (result.contextHash !== workflowFingerprint(workflow)) return;
    var workflowId = workflow.id;
    var moduleId = module.id;
    aiWriting = true;
    aiFeedback = { message: 'Writing ' + result.path + '...', error: false };
    renderModuleConfig(workflowStore.getState());
    try {
        var response = await postJson(aiUrl('file'), { path: result.path, content: result.code, expectedHash: result.baseHash });
        var latestWorkflow = workflowStore.getState().workflows.find(function (entry) { return entry.id === workflowId; });
        var latestModule = latestWorkflow && latestWorkflow.modules.find(function (entry) { return entry.id === moduleId; });
        if (latestModule && latestModule.config.ai && latestModule.config.ai.code === result.code && latestModule.config.ai.path === result.path) {
            workflowStore.updateModule(moduleId, { config: { ai: Object.assign({}, latestModule.config.ai, { baseHash: response.hash, writtenAt: new Date().toISOString() }) } }, { workflowId: workflowId });
        }
        if (selectedFilePath === result.path && !fileDirty) { fileEditor.value = result.code; updateCodeEditor(); }
        var parent = parentPathOf(result.path);
        if (directoryContainers[parent]) loadDirectory(parent);
        aiFeedback = { message: 'Written to ' + result.path + '.', error: false };
    } catch (error) {
        aiFeedback = { message: error.message, error: true };
    } finally {
        aiWriting = false;
        renderModuleConfig(workflowStore.getState());
    }
}
