<dialog class="ai-settings-dialog" data-ai-settings-dialog aria-labelledby="ai-settings-title">
    <form data-ai-settings-form>
        <header class="ai-settings-header">
            <h2 id="ai-settings-title">AI settings</h2>
            <button type="button" class="ai-close" data-ai-settings-close aria-label="Close AI settings" title="Close">&times;</button>
        </header>
        <fieldset class="ai-settings-fields" data-ai-settings-fields>
            <div class="module-config-field">
                <label for="ai-provider">Provider protocol</label>
                <select id="ai-provider" name="provider">
                    <option value="openai">OpenAI Responses</option>
                    <option value="compatible">OpenAI-compatible / local</option>
                    <option value="anthropic">Anthropic Messages</option>
                </select>
            </div>
            <div class="module-config-field">
                <label for="ai-base-url">API base URL</label>
                <input id="ai-base-url" name="base_url" type="url" required autocomplete="off" spellcheck="false" placeholder="https://api.openai.com/v1">
            </div>
            <div class="module-config-field">
                <label for="ai-model">Model ID</label>
                <input id="ai-model" name="model" required maxlength="200" autocomplete="off" spellcheck="false">
            </div>
            <div class="module-config-field">
                <label for="ai-api-key">API key</label>
                <input id="ai-api-key" name="api_key" type="password" autocomplete="new-password" spellcheck="false" placeholder="API key" maxlength="4096">
            </div>
            <label class="ai-live" data-ai-remove-key hidden>
                <input type="checkbox" name="clear_key">
                Remove stored key
            </label>
            <label class="ai-live">
                <input type="checkbox" name="live">
                Live generation
            </label>
            <div class="module-config-field">
                <label for="ai-debounce">Typing delay (milliseconds)</label>
                <input id="ai-debounce" name="debounce_ms" type="number" min="600" max="5000" step="100" required>
            </div>
            <p class="ai-status" data-ai-settings-status role="status" aria-live="polite"></p>
            <div class="ai-settings-actions">
                <button type="button" class="workflow-action" data-ai-test>Test connection</button>
                <button type="submit" class="workflow-action ai-primary">Save settings</button>
            </div>
        </fieldset>
    </form>
</dialog>
