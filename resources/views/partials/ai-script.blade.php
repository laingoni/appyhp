var aiSettings = null;
var aiProject = { frontend: 'blade', folders: [] };
var aiJob = null;
var aiTimer = null;
var aiPreview = null;
var aiFeedback = { message: '', error: false };
var aiConfigKey = '';
var aiWriting = false;
var aiSourceLoads = {};
var aiPendingFileRequest = null;
var aiFileEditorActive = null;
var aiTextEditorActive = null;
var loveModal = document.querySelector('[data-love-modal]');
var loveCard = loveModal.querySelector('.love-card');
var loveCopyStatus = loveModal.querySelector('[data-love-copy-status]');

function closeLoveModal() {
    loveModal.classList.remove('active');
    loveModal.setAttribute('aria-hidden', 'true');
    loveCopyStatus.textContent = '';
}

shell.querySelector('[data-love-open]').addEventListener('click', function () {
    loveModal.classList.add('active');
    loveModal.setAttribute('aria-hidden', 'false');
    loveCard.focus();
});
loveModal.querySelector('[data-love-close]').addEventListener('click', closeLoveModal);
loveModal.addEventListener('click', function (event) {
    if (event.target === loveModal) closeLoveModal();
});
loveModal.querySelectorAll('[data-love-copy]').forEach(function (button) {
    button.addEventListener('click', async function () {
        try {
            await navigator.clipboard.writeText(button.dataset.loveCopy);
            loveCopyStatus.textContent = 'Address copied.';
        } catch (error) {
            loveCopyStatus.textContent = 'Copy is unavailable in this browser.';
        }
    });
});
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && loveModal.classList.contains('active')) closeLoveModal();
});
var aiDialog = document.querySelector('[data-ai-settings-dialog]');
var aiForm = document.querySelector('[data-ai-settings-form]');
var aiSettingsButton = shell.querySelector('[data-ai-settings]');
var aiProviderDefaults = { openai: 'https://api.openai.com/v1', compatible: 'http://localhost:11434/v1', anthropic: 'https://api.anthropic.com/v1' };
aiSettingsButton.innerHTML = workflowCardActionIcon('ai');

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
    if (type === 'route') {
        var routeTypes = { web: 'web.php', api: 'api.php', console: 'console.php', channels: 'channels.php' };
        filename = routeTypes[config.routeType] || routeTypes.web;
    }
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
    return { folder: folder, filename: filename, prompt: '', live: false };
}

function workflowFingerprint(workflow) {
    var value = JSON.stringify({
        name: workflow.name, description: workflow.description,
        frontend: workflow.meta.frontend || aiProject.frontend || 'blade',
        modules: workflow.modules.map(function (module) {
            var config = Object.assign({}, module.config);
            delete config.ai;
            delete config.live;
            return { id: module.id, type: module.type, label: module.label, description: module.description, config: config };
        }),
        edges: workflow.edges.map(function (edge) { return { from: edge.from, to: edge.to, label: edge.label }; })
    });
    var hash = 2166136261;
    for (var i = 0; i < value.length; i += 1) hash = Math.imul(hash ^ value.charCodeAt(i), 16777619);
    return (hash >>> 0).toString(16) + ':' + value.length;
}

async function publishGeneratedFiles(workflows) {
    var jobs = {};
    var fileChanges = [];
    (workflows || []).forEach(function (workflow) {
        (workflow.modules || []).forEach(function (module) {
            var config = module.config || {};
            var path = [config.folder, config.filename].filter(Boolean).join('/');
            if (!path) return;
            if (!jobs[path]) jobs[path] = { path: path, workflow: workflow, module: module, configs: [] };
            jobs[path].configs.push(config);
        });
    });

    var pending = Object.keys(jobs).map(function (path) {
        var job = jobs[path];
        var config = job.module.config || {};
        var result = config.ai || {};
        var aligned = result.path === path && typeof result.code === 'string';
        var shouldWrite = aligned && (result.dirty === true || (result.dirty !== false && result.source !== 'file'));
        var previousPath = config.previousPath || (config.ai && config.ai.path !== path ? config.ai.path : '');
        var publish = Promise.resolve();
        if (job.module.type !== 'route' && previousPath && previousPath !== path) {
            publish = postJson(directoryUrl('/relocate'), { source: previousPath, target: path, allowMissing: true });
        }
        return publish.then(function () {
            var moduleConfig = Object.assign({}, config);
            delete moduleConfig.ai;
            delete moduleConfig.previousPath;
            moduleConfig.frontend = (job.workflow.meta || {}).frontend || aiProject.frontend || 'blade';
            if (shouldWrite) {
                return postJson(aiUrl('file'), {
                    path: path,
                    content: result.code,
                    expectedHash: result.baseHash == null ? null : result.baseHash
                });
            }
            return postJson(directoryUrl('/file'), {
                path: path,
                content: '',
                createOnly: true,
                moduleType: job.module.type,
                moduleConfig: moduleConfig
            }, 'PUT');
        }).then(function (response) {
            fileChanges.push({
                oldPath: previousPath && previousPath !== path ? previousPath : '',
                path: path,
                hash: response.hash || null,
                content: shouldWrite ? result.code : null
            });
            job.configs.forEach(function (config) {
                delete config.previousPath;
                if (config.ai && config.ai.path !== path) {
                    delete config.ai;
                } else if (shouldWrite && config.ai) {
                    config.ai.dirty = false;
                    config.ai.baseHash = response.hash;
                    config.ai.writtenAt = new Date().toISOString();
                }
            });
        });
    });
    await Promise.all(pending);
    await reflectFileSystemChanges(fileChanges);
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
    var values = aiSettings || { provider: 'openai', base_url: aiProviderDefaults.openai, model: '', live: false, debounce_ms: 1200 };
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

function saveAiLivePreference(enabled) {
    if (!aiSettings) return Promise.reject(new Error('AI settings are not loaded.'));

    return postJson(aiUrl('settings'), {
        provider: aiSettings.provider,
        base_url: aiSettings.base_url,
        model: aiSettings.model,
        api_key: '',
        live: Boolean(enabled),
        debounce_ms: Number(aiSettings.debounce_ms || 1200)
    }, 'PUT').then(function (response) {
        aiSettings = response.settings;
        aiSettingsButton.title = aiSettings.configured ? 'AI settings: ' + aiSettings.model : 'AI settings';
        return aiSettings;
    });
}

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

async function generateModule(fileAccess) {
    fileAccess = Array.isArray(fileAccess) ? fileAccess : [];
    if (aiWriting) return;
    var workflow = workflowStore.getActiveWorkflow();
    var module = workflowStore.getSelectedModule();
    if (!workflow || !module) return;
    if (!aiSettings || !aiSettings.configured) { openAiSettings(); return; }
    cancelAiGeneration();
    aiPendingFileRequest = null;
    if (!(module.config.prompt || '').trim()) {
        aiFeedback = { message: 'Describe what this module should do.', error: true };
        renderAiOutput(module, workflow);
        return;
    }
    var job = {
        controller: new AbortController(), workflowId: workflow.id, moduleId: module.id,
        fingerprint: workflowFingerprint(workflow), raw: '', complete: false, fileAccess: fileAccess
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
            body: JSON.stringify({ moduleId: module.id, workflow: workflow, fileAccess: fileAccess })
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
                result.source = 'ai';
                result.dirty = true;
                next.config = Object.assign({}, next.config, payload.config, { ai: result });
                result.contextHash = workflowFingerprint(predicted);
                job.complete = true;
                aiJob = null;
                aiPreview = null;
                aiFeedback = { message: 'Draft ready.', error: false };
                workflowStore.updateModule(job.moduleId, { config: next.config });
            }
            if (event === 'file_request') {
                var currentWorkflow = workflowStore.getActiveWorkflow();
                if (!currentWorkflow || currentWorkflow.id !== job.workflowId || workflowFingerprint(currentWorkflow) !== job.fingerprint) {
                    throw new Error('The workflow changed during generation. Generate again.');
                }
                job.complete = true;
                aiJob = null;
                aiPreview = null;
                aiPendingFileRequest = {
                    workflowId: job.workflowId,
                    moduleId: job.moduleId,
                    fingerprint: job.fingerprint,
                    path: payload.path,
                    reason: payload.reason,
                    fileAccess: job.fileAccess.slice()
                };
                aiFeedback = { message: 'The AI needs permission to read another project file.', error: false };
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
        aiPendingFileRequest = null;
        aiFileEditorActive = null;
        aiTextEditorActive = null;
        aiConfigKey = key;
        aiFeedback = { message: '', error: false };
        moduleConfigPanel.innerHTML = '';
    }
    moduleConfigPanel.classList.toggle('active', Boolean(module));
    if (!module) return;
    if (aiJob && aiJob.fingerprint !== workflowFingerprint(workflow)) cancelAiGeneration('Workflow changed. Generate again.');
    if (aiFileEditorActive && aiFileEditorActive.workflowId === workflow.id && aiFileEditorActive.moduleId === module.id) {
        renderModuleFileEditor(module, workflow);
        ensureModuleSource(module, workflow);
        return;
    }
    if (aiTextEditorActive && aiTextEditorActive.workflowId === workflow.id && aiTextEditorActive.moduleId === module.id) {
        renderModuleTextEditor(module);
        return;
    }

    if (!moduleConfigPanel.firstChild) {
        var definition = moduleDefinition(module.type);
        moduleConfigPanel.innerHTML = '<header class="module-config-header"><button type="button" class="module-editor-back" data-ai-close aria-label="Back to workflow canvas" title="Back to workflow canvas"><span aria-hidden="true">&#8249;</span><span>Back</span></button><span><span class="workflow-title" data-ai-module-title></span><span class="workflow-meta" data-ai-module-kind></span></span></header>' +
            '<div data-ai-route-type></div>' +
            '<div class="module-config-body"><div data-ai-name></div><div class="ai-target-fields" data-ai-target></div><div data-ai-prompt></div>' +
            '<div class="ai-toolbar"><label class="ai-live"><input type="checkbox" data-ai-live>Live</label><button class="workflow-action" type="button" data-ai-stop hidden>Stop</button><button class="workflow-action ai-primary" type="button" data-ai-generate>Generate</button><button class="workflow-action" type="button" data-ai-setup>AI settings</button></div>' +
            '<p class="ai-status" role="status" aria-live="polite" data-ai-message></p>' +
            '<section class="ai-file-request" data-ai-file-request hidden><strong>AI requests another file</strong><code data-ai-file-request-path></code><p data-ai-file-request-reason></p><div class="ai-toolbar"><button class="workflow-action ai-primary" type="button" data-ai-file-grant>Grant access</button><button class="workflow-action" type="button" data-ai-file-deny>Deny and continue</button></div></section>' +
            '<div class="ai-toolbar"><span class="ai-status" data-ai-code-state>Module file</span><button class="workflow-action" type="button" data-ai-copy>Copy</button><button class="workflow-action" type="button" data-ai-download>Download</button><button class="workflow-action" type="button" data-ai-edit-file>Code editor</button></div>' +
            '<textarea class="ai-code" data-ai-code spellcheck="false" wrap="off" aria-label="Module file code" placeholder="Loading the module file..." readonly></textarea>' +
            '<section class="ai-section" data-ai-table hidden></section>' +
            '<div class="ai-toolbar"><span class="ai-status" data-ai-path></span><button class="workflow-action ai-primary" type="button" data-ai-write>Save file</button></div>' +
            '<p class="ai-status" data-ai-summary></p>' +
            '<section class="ai-section" data-ai-suggestions hidden></section>' +
            '<section class="ai-section"><h3 class="ai-section-title">Connections</h3><div class="ai-connections" data-ai-connections></div></section></div>';
        moduleConfigPanel.querySelector('[data-ai-module-kind]').textContent = definition.title;
        addConfigField(moduleConfigPanel.querySelector('[data-ai-name]'), module, { key: 'label', label: 'Module name', type: 'text', root: true });
        if (module.type === 'route') {
            addConfigField(moduleConfigPanel.querySelector('[data-ai-route-type]'), module, { key: 'routeType', label: 'Route file', type: 'select', options: ['web', 'api', 'console', 'channels'] });
        }
        var folder = addConfigField(moduleConfigPanel.querySelector('[data-ai-target]'), module, { key: 'folder', label: 'Folder', type: 'text' });
        folder.setAttribute('list', 'ai-folder-options');
        folder.autocomplete = 'off';
        folder.spellcheck = false;
        var datalist = document.createElement('datalist');
        datalist.id = 'ai-folder-options';
        folder.parentElement.appendChild(datalist);
        var filename = addConfigField(moduleConfigPanel.querySelector('[data-ai-target]'), module, { key: 'filename', label: 'Filename', type: 'text' });
        if (module.type === 'route') {
            filename.readOnly = true;
            filename.title = 'The route file is determined by Route file.';
        }
        var prompt = addConfigField(moduleConfigPanel.querySelector('[data-ai-prompt]'), module, { key: 'prompt', label: 'What should this module do?', type: 'textarea' });
        prompt.maxLength = 16000;
        var promptLabel = prompt.previousElementSibling;
        var promptHeader = document.createElement('div');
        promptHeader.className = 'ai-prompt-field-header';
        prompt.parentElement.insertBefore(promptHeader, promptLabel);
        promptHeader.appendChild(promptLabel);
        var promptEditorButton = document.createElement('button');
        promptEditorButton.type = 'button';
        promptEditorButton.className = 'workflow-action ai-prompt-editor-button';
        promptEditorButton.dataset.aiEditPrompt = '';
        promptEditorButton.textContent = 'Text Editor';
        promptHeader.appendChild(promptEditorButton);
        mountCompactSharedTextEditor(prompt, 'markdown', 'Describe what this module should do...');
        mountCompactSharedTextEditor(moduleConfigPanel.querySelector('[data-ai-code]'), detectLanguage([module.config.folder, module.config.filename].filter(Boolean).join('/')), 'Loading the module file...');
        moduleConfigPanel.querySelector('[data-ai-close]').addEventListener('click', closeModuleEditor);
        moduleConfigPanel.querySelector('[data-ai-generate]').addEventListener('click', function () { generateModule(); });
        moduleConfigPanel.querySelector('[data-ai-setup]').addEventListener('click', openAiSettings);
        moduleConfigPanel.querySelector('[data-ai-stop]').addEventListener('click', function () {
            cancelAiGeneration('Generation stopped.');
            renderModuleConfig(workflowStore.getState());
        });
        moduleConfigPanel.querySelector('[data-ai-live]').addEventListener('change', function (event) {
            var enabled = event.target.checked;
            if (!event.target.checked) cancelAiGeneration('Live generation paused.');
            workflowStore.updateModule(module.id, { config: { live: enabled } });
            saveAiLivePreference(enabled).then(function () {
                renderModuleConfig(workflowStore.getState());
                if (enabled) queueModuleGeneration();
            }).catch(function (error) {
                event.target.checked = false;
                workflowStore.updateModule(module.id, { config: { live: false } });
                aiFeedback = { message: error.message || 'Unable to save the Live setting.', error: true };
                renderModuleConfig(workflowStore.getState());
            });
        });
        moduleConfigPanel.querySelector('[data-ai-copy]').addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(displayedAiCode());
                aiFeedback = { message: 'Code copied.', error: false };
            } catch (error) { aiFeedback = { message: 'Clipboard is unavailable. Download the code instead.', error: true }; }
            renderModuleConfig(workflowStore.getState());
        });
        moduleConfigPanel.querySelector('[data-ai-code]').addEventListener('input', function (event) {
            var current = workflowStore.getSelectedModule();
            if (!current || current.id !== module.id) return;
            if (aiPreview || aiJob) cancelAiGeneration('Code draft edited manually.');
            var code = event.target.value;
            var ai = Object.assign({}, current.config.ai || {}, {
                code: code,
                path: [current.config.folder, current.config.filename].filter(Boolean).join('/'),
                dirty: true,
                editedAt: new Date().toISOString()
            });
            aiFeedback = { message: 'Unsaved changes', error: false };
            workflowStore.updateModule(module.id, { config: { ai: ai } }, { historyGroup: module.id + ':code' });
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
        moduleConfigPanel.querySelector('[data-ai-edit-file]').addEventListener('click', openModuleFileEditor);
        moduleConfigPanel.querySelector('[data-ai-edit-prompt]').addEventListener('click', openModuleTextEditor);
        moduleConfigPanel.querySelector('[data-ai-write]').addEventListener('click', writeGeneratedFile);
        moduleConfigPanel.querySelector('[data-ai-file-grant]').addEventListener('click', function () { answerAiFileRequest('grant'); });
        moduleConfigPanel.querySelector('[data-ai-file-deny]').addEventListener('click', function () { answerAiFileRequest('deny'); });
    }

    moduleConfigPanel.querySelector('[data-ai-module-title]').textContent = module.label;
    moduleConfigPanel.querySelectorAll('[data-config-key]').forEach(function (control) {
        var value = control.dataset.configRoot === 'true' ? module[control.dataset.configKey] : module.config[control.dataset.configKey];
        var next = value == null ? '' : String(value);
        if (control.value !== next) control.value = next;
    });
    var compactPrompt = moduleConfigPanel.querySelector('[data-ai-prompt] .shared-text-editor');
    if (compactPrompt) updateSharedTextEditor(compactPrompt, compactPrompt.querySelector('[data-ai-prompt-input]'), 'markdown');
    var folderOptions = Array.from(new Set(aiProject.folders.concat([module.config.folder])));
    moduleConfigPanel.querySelector('#ai-folder-options').innerHTML = folderOptions.map(function (folder) { return '<option value="' + escapeHtml(folder) + '"></option>'; }).join('');
    var live = moduleConfigPanel.querySelector('[data-ai-live]');
    live.checked = module.config.live === true;
    live.disabled = !aiSettings;
    live.title = live.disabled ? 'AI settings are still loading' : 'Live generation';
    renderAiOutput(module, workflow);
    renderAiConnections(module, workflow);
    ensureModuleSource(module, workflow);
}

function openModuleFileEditor() {
    var workflow = workflowStore.getActiveWorkflow();
    var module = workflowStore.getSelectedModule();
    if (!workflow || !module || !module.config.ai || typeof module.config.ai.code !== 'string' || aiJob || aiWriting) return;
    aiFileEditorActive = { workflowId: workflow.id, moduleId: module.id };
    moduleConfigPanel.innerHTML = '';
    renderModuleConfig(workflowStore.getState());
}

function closeModuleFileEditor() {
    aiFileEditorActive = null;
    moduleConfigPanel.innerHTML = '';
    renderModuleConfig(workflowStore.getState());
}

function openModuleTextEditor() {
    var workflow = workflowStore.getActiveWorkflow();
    var module = workflowStore.getSelectedModule();
    if (!workflow || !module) return;
    aiTextEditorActive = { workflowId: workflow.id, moduleId: module.id };
    moduleConfigPanel.innerHTML = '';
    renderModuleConfig(workflowStore.getState());
}

function closeModuleTextEditor() {
    aiTextEditorActive = null;
    moduleConfigPanel.innerHTML = '';
    renderModuleConfig(workflowStore.getState());
}

function moduleEditorSurfaceMarkup(kind, ariaLabel, spellcheck) {
    return sharedTextEditorMarkup(
        'data-module-' + kind + '-editor-shell',
        'data-module-' + kind + '-content',
        ariaLabel,
        spellcheck === 'true',
        kind === 'text' ? 'Describe the module in Markdown...' : '',
        'module-embedded-editor-shell'
    );
}

function sharedTextEditorMarkup(shellAttribute, inputAttribute, ariaLabel, spellcheck, placeholder, extraClass, showGutter) {
    var gutter = showGutter === false ? '' : '<div class="code-gutter" aria-hidden="true"><div class="code-gutter-lines" data-shared-editor-gutter data-module-editor-gutter>1</div></div>';
    return '<section class="shared-text-editor ' + escapeHtml(extraClass || '') + (showGutter === false ? ' without-gutter' : '') + '" ' + shellAttribute + '>' +
        '<header class="shared-text-editor-toolbar"><span class="shared-text-editor-language"><span aria-hidden="true">●</span><span data-shared-editor-language>Markdown</span></span><span data-shared-editor-stats>1 line · 0 characters</span></header>' +
        '<div class="code-editor-shell shared-text-editor-canvas">' +
            gutter +
            '<pre class="code-highlight" data-shared-editor-highlight data-module-editor-highlight aria-hidden="true"><code data-placeholder="' + escapeHtml(placeholder || '') + '"></code></pre>' +
            '<textarea class="file-editor" ' + inputAttribute + ' spellcheck="' + (spellcheck ? 'true' : 'false') + '" wrap="off" aria-label="' + escapeHtml(ariaLabel) + '" placeholder="' + escapeHtml(placeholder || '') + '"></textarea>' +
        '</div>' +
    '</section>';
}

function mountCompactSharedTextEditor(editor, language, placeholder) {
    var host = document.createElement('div');
    host.className = 'compact-shared-text-editor-host';
    editor.parentNode.insertBefore(host, editor);
    host.innerHTML = sharedTextEditorMarkup('data-compact-editor-shell', 'data-compact-editor-input', editor.getAttribute('aria-label') || 'Text editor', editor.spellcheck, placeholder, 'compact-shared-text-editor', false);
    var shell = host.firstElementChild;
    var generatedEditor = shell.querySelector('[data-compact-editor-input]');
    editor.classList.add('file-editor');
    if (editor.matches('[data-ai-prompt], [data-config-key="prompt"]')) editor.dataset.aiPromptInput = '';
    generatedEditor.replaceWith(editor);
    bindSharedTextEditor(shell, editor, language);
    return shell;
}

function syncSharedTextEditor(shell, editor) {
    var highlight = shell.querySelector('[data-module-editor-highlight]');
    highlight.scrollTop = editor.scrollTop;
    highlight.scrollLeft = editor.scrollLeft;
    var gutter = shell.querySelector('[data-module-editor-gutter]');
    if (gutter) gutter.style.transform = 'translateY(-' + editor.scrollTop + 'px)';
}

function updateSharedTextEditor(shell, editor, language) {
    var value = editor.value || '';
    var lineCount = Math.max(1, value.split('\n').length);
    var lines = [];
    var languageName = language === 'markdown' ? 'Markdown' : (language === 'plain' ? 'Plain text' : String(language || 'Plain text').toUpperCase());
    for (var index = 1; index <= lineCount; index += 1) lines.push(index);
    shell.dataset.editorLanguage = language || 'plain';
    var gutter = shell.querySelector('[data-module-editor-gutter]');
    if (gutter) gutter.textContent = lines.join('\n');
    shell.querySelector('[data-module-editor-highlight] code').innerHTML = highlightCode(value, language);
    shell.querySelector('[data-shared-editor-language]').textContent = languageName;
    shell.querySelector('[data-shared-editor-stats]').textContent = lineCount + (lineCount === 1 ? ' line · ' : ' lines · ') + value.length + (value.length === 1 ? ' character' : ' characters');
    shell.classList.toggle('empty', value.length === 0);
    syncSharedTextEditor(shell, editor);
}

function bindSharedTextEditor(shell, editor, language) {
    editor.addEventListener('scroll', function () { syncSharedTextEditor(shell, editor); });
    editor.addEventListener('input', function () {
        updateSharedTextEditor(shell, editor, shell.dataset.editorLanguage || language || 'plain');
    });
    editor.addEventListener('keydown', function (event) {
        if (event.key !== 'Tab') return;
        event.preventDefault();
        var start = editor.selectionStart;
        editor.setRangeText('    ', start, editor.selectionEnd, 'end');
        editor.dispatchEvent(new Event('input', { bubbles: true }));
    });
    updateSharedTextEditor(shell, editor, language || 'plain');
}

function renderModuleTextEditor(module) {
    if (!moduleConfigPanel.querySelector('[data-module-text-editor]')) {
        moduleConfigPanel.innerHTML = '<section class="module-file-editor-view" data-module-text-editor>' +
            '<header class="module-config-header"><button type="button" class="module-editor-back" data-module-text-back aria-label="Back to module editor" title="Back to module editor"><span aria-hidden="true">&#8249;</span><span>Back</span></button><span><span class="workflow-title">What should this module do?</span><span class="workflow-meta" data-module-text-title></span></span></header>' +
            moduleEditorSurfaceMarkup('text', 'What should this module do?', 'true') +
            '<footer class="module-file-editor-footer"><span class="ai-status" role="status" aria-live="polite">Changes are kept in the module draft.</span></footer>' +
            '</section>';
        moduleConfigPanel.querySelector('[data-module-text-back]').addEventListener('click', closeModuleTextEditor);
        var textShell = moduleConfigPanel.querySelector('[data-module-text-editor-shell]');
        var textEditor = moduleConfigPanel.querySelector('[data-module-text-content]');
        bindSharedTextEditor(textShell, textEditor, 'markdown');
        textEditor.addEventListener('input', function (event) {
            var current = workflowStore.getSelectedModule();
            if (!current || current.id !== module.id) return;
            event.target.dataset.source = event.target.value;
            workflowStore.updateModule(module.id, { config: { prompt: event.target.value } }, { historyGroup: module.id + ':prompt' });
            queueModuleGeneration();
        });
    }
    var editor = moduleConfigPanel.querySelector('[data-module-text-content]');
    var prompt = module.config.prompt || '';
    if (editor.dataset.source !== prompt) {
        editor.value = prompt;
        editor.dataset.source = prompt;
    }
    moduleConfigPanel.querySelector('[data-module-text-title]').textContent = module.label + ' · Markdown';
    updateSharedTextEditor(moduleConfigPanel.querySelector('[data-module-text-editor-shell]'), editor, 'markdown');
}

function renderModuleFileEditor(module, workflow) {
    var path = [module.config.folder, module.config.filename].filter(Boolean).join('/');
    var result = module.config.ai || {};
    var stale = Boolean(result.code && result.contextHash && result.contextHash !== workflowFingerprint(workflow));
    if (!moduleConfigPanel.querySelector('[data-module-file-editor]')) {
        moduleConfigPanel.innerHTML = '<section class="module-file-editor-view" data-module-file-editor>' +
            '<header class="module-config-header"><button type="button" class="module-editor-back" data-module-file-back aria-label="Back to module editor" title="Back to module editor"><span aria-hidden="true">&#8249;</span><span>Back</span></button><span><span class="workflow-title" data-module-file-title></span><span class="workflow-meta" data-module-file-path></span></span></header>' +
            moduleEditorSurfaceMarkup('file', 'Edit module file', 'false') +
            '<footer class="module-file-editor-footer"><span class="ai-status" role="status" aria-live="polite" data-module-file-status></span><button class="workflow-action ai-primary" type="button" data-module-file-save>Save file</button></footer>' +
            '</section>';
        moduleConfigPanel.querySelector('[data-module-file-back]').addEventListener('click', closeModuleFileEditor);
        var fileShell = moduleConfigPanel.querySelector('[data-module-file-editor-shell]');
        var fileEditor = moduleConfigPanel.querySelector('[data-module-file-content]');
        bindSharedTextEditor(fileShell, fileEditor, 'plain');
        fileEditor.addEventListener('input', function (event) {
            var current = workflowStore.getSelectedModule();
            if (!current || current.id !== module.id) return;
            var code = event.target.value;
            event.target.dataset.source = code;
            updateSharedTextEditor(fileShell, event.target, detectLanguage([current.config.folder, current.config.filename].filter(Boolean).join('/')));
            var ai = Object.assign({}, current.config.ai || {}, {
                code: code,
                path: [current.config.folder, current.config.filename].filter(Boolean).join('/'),
                dirty: true,
                editedAt: new Date().toISOString()
            });
            aiFeedback = { message: 'Unsaved changes', error: false };
            workflowStore.updateModule(module.id, { config: { ai: ai } }, { historyGroup: module.id + ':code' });
        });
        moduleConfigPanel.querySelector('[data-module-file-save]').addEventListener('click', writeGeneratedFile);
    }
    var editor = moduleConfigPanel.querySelector('[data-module-file-content]');
    var code = typeof result.code === 'string' ? result.code : '';
    if (editor.dataset.source !== code) {
        editor.value = code;
        editor.dataset.source = code;
    }
    updateSharedTextEditor(moduleConfigPanel.querySelector('[data-module-file-editor-shell]'), editor, detectLanguage(path));
    moduleConfigPanel.querySelector('[data-module-file-title]').textContent = module.label;
    moduleConfigPanel.querySelector('[data-module-file-path]').textContent = path;
    var status = moduleConfigPanel.querySelector('[data-module-file-status]');
    status.textContent = aiFeedback.message || (stale ? 'Workflow changed since generation' : (result.dirty ? 'Unsaved changes' : 'Editing file content'));
    status.dataset.error = aiFeedback.error ? 'true' : 'false';
    var save = moduleConfigPanel.querySelector('[data-module-file-save]');
    save.disabled = typeof result.code !== 'string' || stale || aiWriting;
    save.textContent = aiWriting ? 'Saving...' : 'Save file';
}

function answerAiFileRequest(decision) {
    var request = aiPendingFileRequest;
    var workflow = workflowStore.getActiveWorkflow();
    var module = workflowStore.getSelectedModule();
    if (!request || !workflow || !module || workflow.id !== request.workflowId || module.id !== request.moduleId
        || workflowFingerprint(workflow) !== request.fingerprint) {
        aiPendingFileRequest = null;
        aiFeedback = { message: 'The workflow changed. Generate again.', error: true };
        renderModuleConfig(workflowStore.getState());
        return;
    }
    var access = request.fileAccess.concat([{ path: request.path, decision: decision }]);
    aiPendingFileRequest = null;
    aiFeedback = { message: decision === 'grant' ? 'Reading ' + request.path + ' and continuing...' : 'Access denied. Continuing with the available context...', error: false };
    renderAiOutput(module, workflow);
    generateModule(access);
}

function moduleSourceKey(module, workflow) {
    return workflow.id + ':' + module.id + ':' + [module.config.folder, module.config.filename].filter(Boolean).join('/');
}

function ensureModuleSource(module, workflow) {
    var path = [module.config.folder, module.config.filename].filter(Boolean).join('/');
    var result = module.config.ai || {};
    if (!path || (result.path === path && typeof result.code === 'string') || module.config.previousPath) return;
    var key = moduleSourceKey(module, workflow);
    if (aiSourceLoads[key]) return;
    aiSourceLoads[key] = 'loading';
    renderAiOutput(module, workflow);

    var moduleConfig = Object.assign({}, module.config);
    delete moduleConfig.ai;
    delete moduleConfig.previousPath;
    moduleConfig.frontend = (workflow.meta || {}).frontend || aiProject.frontend || 'blade';

    postJson(directoryUrl('/file'), {
        path: path,
        content: '',
        createOnly: true,
        moduleType: module.type,
        moduleConfig: moduleConfig
    }, 'PUT').then(function () {
        return requestJson(directoryUrl('/file', { path: path }));
    }).then(function (payload) {
        aiSourceLoads[key] = 'loaded';
        var state = workflowStore.getState();
        var latestWorkflow = state.workflows.find(function (entry) { return entry.id === workflow.id; });
        var latestModule = latestWorkflow && latestWorkflow.modules.find(function (entry) { return entry.id === module.id; });
        if (!latestModule || [latestModule.config.folder, latestModule.config.filename].filter(Boolean).join('/') !== path) return;
        workflowStore.updateModule(module.id, {
            config: {
                ai: {
                    code: payload.content || '',
                    path: path,
                    baseHash: payload.hash,
                    source: 'file',
                    dirty: false,
                    loadedAt: new Date().toISOString()
                }
            }
        }, { workflowId: workflow.id, skipHistory: true });
        return reflectFileSystemChanges([{
            path: path,
            hash: payload.hash,
            content: payload.content || ''
        }]);
    }).catch(function (error) {
        aiSourceLoads[key] = 'error';
        if (workflowStore.getSelectedModule() && workflowStore.getSelectedModule().id === module.id) {
            aiFeedback = { message: error.message || 'Unable to load the module file.', error: true };
            renderModuleConfig(workflowStore.getState());
        }
    });
}

function displayedAiCode() {
    var module = workflowStore.getSelectedModule();
    return aiPreview ? aiPreview.code : (module && module.config.ai && module.config.ai.code) || '';
}

function renderAiOutput(module, workflow) {
    if (!module || !workflow || !moduleConfigPanel.firstChild) return;
    var result = module.config.ai || {};
    var code = displayedAiCode();
    var stale = Boolean(result.code && result.contextHash && result.contextHash !== workflowFingerprint(workflow));
    var path = module.config.folder + '/' + module.config.filename;
    var codeEl = moduleConfigPanel.querySelector('[data-ai-code]');
    if (codeEl.dataset.source !== code) {
        var follow = codeEl.scrollHeight - codeEl.scrollTop - codeEl.clientHeight < 35;
        codeEl.value = code;
        codeEl.dataset.source = code;
        if (follow && aiJob) codeEl.scrollTop = codeEl.scrollHeight;
    }
    updateSharedTextEditor(codeEl.closest('.shared-text-editor'), codeEl, detectLanguage(path));
    var status = moduleConfigPanel.querySelector('[data-ai-message]');
    status.textContent = aiFeedback.message || (!aiSettings ? 'Loading AI settings...' : (!aiSettings.configured ? 'AI provider not configured.' : ''));
    status.dataset.error = aiFeedback.error ? 'true' : 'false';
    var fileRequest = moduleConfigPanel.querySelector('[data-ai-file-request]');
    var requestMatches = aiPendingFileRequest && aiPendingFileRequest.workflowId === workflow.id && aiPendingFileRequest.moduleId === module.id;
    fileRequest.hidden = !requestMatches;
    if (requestMatches) {
        fileRequest.querySelector('[data-ai-file-request-path]').textContent = aiPendingFileRequest.path;
        fileRequest.querySelector('[data-ai-file-request-reason]').textContent = aiPendingFileRequest.reason;
    }
    var codeState = moduleConfigPanel.querySelector('[data-ai-code-state]');
    var loading = aiSourceLoads[moduleSourceKey(module, workflow)] === 'loading';
    codeState.textContent = aiJob ? 'Streaming code' : loading ? 'Loading file' : stale ? 'Workflow changed since generation' : result.dirty && result.source === 'file' ? 'Edited file' : result.source === 'file' ? 'File content' : result.code ? 'Generated code' : 'Module file';
    codeState.dataset.stale = stale ? 'true' : 'false';
    moduleConfigPanel.querySelector('[data-ai-stop]').hidden = !aiJob && !aiTimer;
    moduleConfigPanel.querySelector('[data-ai-generate]').disabled = Boolean(aiJob || aiWriting);
    moduleConfigPanel.querySelector('[data-ai-setup]').hidden = Boolean(aiSettings && aiSettings.configured);
    moduleConfigPanel.querySelector('[data-ai-copy]').disabled = !code;
    moduleConfigPanel.querySelector('[data-ai-download]').disabled = !code;
    moduleConfigPanel.querySelector('[data-ai-edit-file]').disabled = typeof result.code !== 'string' || Boolean(aiJob) || aiWriting || loading;
    moduleConfigPanel.querySelector('[data-ai-write]').disabled = typeof result.code !== 'string' || Boolean(aiJob) || stale || aiWriting || result.path !== path;
    moduleConfigPanel.querySelector('[data-ai-write]').textContent = aiWriting ? 'Saving...' : 'Save file';
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
    if (result.contextHash && result.contextHash !== workflowFingerprint(workflow)) return;
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
            workflowStore.updateModule(moduleId, { config: { ai: Object.assign({}, latestModule.config.ai, { baseHash: response.hash, dirty: false, writtenAt: new Date().toISOString() }) } }, { workflowId: workflowId, skipHistory: true });
        }
        if (selectedFilePath === result.path && !fileDirty) {
            fileEditor.value = result.code;
            selectedFileHash = response.hash || selectedFileHash;
            fileHistoryCurrent = captureFileHistorySnapshot();
            updateCodeEditor();
        }
        await reflectFileSystemChanges([{
            path: result.path,
            hash: response.hash,
            content: result.code
        }]);
        aiFeedback = { message: 'Written to ' + result.path + '.', error: false };
    } catch (error) {
        aiFeedback = { message: error.message, error: true };
    } finally {
        aiWriting = false;
        renderModuleConfig(workflowStore.getState());
    }
}
