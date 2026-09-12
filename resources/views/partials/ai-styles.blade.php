<style>
    .workflow-workspace {
        display: flex;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        position: relative;
    }

    .workflow-workspace > .workflow-canvas-shell { min-width: 0; }
    .workflow-workspace > .workflow-config-panel {
        flex: 0 0 50%;
        max-width: 620px;
        min-width: min(370px, 100%);
        position: static;
        width: 50%;
    }

    .workflow-config-panel .module-config-header { flex: 0 0 auto; gap: 8px; }
    .workflow-config-panel .module-config-header { justify-content: flex-start; }
    .module-config-header > span { min-width: 0; overflow-wrap: anywhere; }
    .module-editor-back { align-items: center; background: var(--light-canvas); border: 1px solid var(--light-line); border-radius: 3px; color: inherit; cursor: pointer; display: inline-flex; flex: 0 0 auto; font-size: 12px; gap: 5px; min-height: 30px; padding: 3px 9px; }
    .module-editor-back > span[aria-hidden="true"] { font-size: 22px; line-height: 1; }
    .module-editor-back:hover { background: #f1f5f9; }
    .dark .module-editor-back { background: var(--dark-canvas); border-color: var(--dark-line); }
    .dark .module-editor-back:hover { background: #1f2937; }
    .studio-docs-link, .studio-docs-link:hover, .studio-docs-link:focus-visible { text-decoration: none; }
    .module-file-editor-view { display: flex; flex: 1 1 auto; flex-direction: column; height: 100%; min-height: 0; }
    .shared-text-editor { background: #f8fafc; border: 1px solid #dbe3ec; border-radius: 9px; box-shadow: 0 10px 28px rgba(15, 23, 42, .08); display: flex; flex-direction: column; min-height: 260px; overflow: hidden; transition: border-color .16s ease, box-shadow .16s ease; }
    .dark .shared-text-editor { background: #0b1020; border-color: #293449; box-shadow: 0 14px 34px rgba(0, 0, 0, .3); }
    .shared-text-editor:focus-within { border-color: rgba(13, 148, 119, .75); box-shadow: 0 0 0 3px rgba(13, 148, 119, .12), 0 14px 34px rgba(15, 23, 42, .1); }
    .shared-text-editor-toolbar { align-items: center; background: linear-gradient(180deg, #fff, #f1f5f9); border-bottom: 1px solid #dbe3ec; color: #64748b; display: flex; flex: 0 0 36px; font-size: 10px; font-weight: 650; justify-content: space-between; letter-spacing: .025em; padding: 0 12px; }
    .dark .shared-text-editor-toolbar { background: linear-gradient(180deg, #151d2e, #111827); border-bottom-color: #293449; color: #94a3b8; }
    .shared-text-editor-language { align-items: center; display: inline-flex; gap: 7px; text-transform: uppercase; }
    .shared-text-editor-language > span[aria-hidden="true"] { color: #10b981; font-size: 8px; text-shadow: 0 0 8px rgba(16, 185, 129, .5); }
    .shared-text-editor-canvas { flex: 1 1 auto; min-height: 0; }
    .shared-text-editor .code-gutter, .shared-text-editor .code-highlight, .shared-text-editor .file-editor { font-family: "JetBrains Mono", "SFMono-Regular", "Cascadia Code", "Fira Code", Consolas, monospace; font-size: 13px; font-variant-ligatures: contextual; line-height: 1.65; }
    .shared-text-editor .file-editor { background: transparent; border: 0; border-radius: 0; caret-color: #0d9477; color: transparent; min-width: 0; opacity: 1; scrollbar-color: #aeb9c8 transparent; scrollbar-width: thin; width: auto; }
    .shared-text-editor .file-editor::-webkit-scrollbar { height: 10px; width: 10px; }
    .shared-text-editor .file-editor::-webkit-scrollbar-thumb { background: #aeb9c8; border: 3px solid transparent; border-radius: 999px; background-clip: padding-box; }
    .shared-text-editor .file-editor::-webkit-scrollbar-corner { background: transparent; }
    .dark .shared-text-editor .file-editor { caret-color: #6ee7b7; scrollbar-color: #3c4a61 transparent; }
    .dark .shared-text-editor .file-editor::-webkit-scrollbar-thumb { background: #3c4a61; border-color: transparent; background-clip: padding-box; }
    .shared-text-editor.empty .code-highlight code:empty::before { color: #94a3b8; content: attr(data-placeholder); font-style: italic; }
    .dark .shared-text-editor.empty .code-highlight code:empty::before { color: #526174; }
    .module-embedded-editor-shell { flex: 1 1 auto; min-height: 0; }
    .token-md-marker { color: #0d9477; font-weight: 700; }
    .token-md-heading { color: #7c3aed; font-weight: 700; }
    .token-md-link { color: #0369a1; text-decoration: underline; text-decoration-color: rgba(3, 105, 161, .35); text-underline-offset: 2px; }
    .token-md-emphasis { color: #b45309; font-style: italic; }
    .token-md-code, .token-md-fence { color: #be123c; }
    .token-md-code { background: rgba(190, 18, 60, .07); }
    .token-md-fence { font-weight: 700; }
    .token-md-quote { color: #64748b; font-style: italic; }
    .dark .token-md-marker { color: #6ee7b7; }
    .dark .token-md-heading { color: #c4b5fd; }
    .dark .token-md-link { color: #7dd3fc; text-decoration-color: rgba(125, 211, 252, .38); }
    .dark .token-md-emphasis { color: #fcd34d; }
    .dark .token-md-code, .dark .token-md-fence { color: #fda4af; }
    .dark .token-md-code { background: rgba(253, 164, 175, .08); }
    .dark .token-md-quote { color: #94a3b8; }
    .token-css-selector { color: #7c3aed; }
    .token-css-property { color: #0369a1; }
    .token-css-atrule { color: #be123c; font-weight: 700; }
    .dark .token-css-selector { color: #c4b5fd; }
    .dark .token-css-property { color: #7dd3fc; }
    .dark .token-css-atrule { color: #fda4af; }
    .directory-code-editor-host { display: flex; flex: 1 1 auto; min-height: 0; padding: 10px; }
    .directory-code-editor { flex: 1 1 auto; min-height: 0; }
    .compact-shared-text-editor-host { min-width: 0; }
    .compact-shared-text-editor { min-height: 150px; }
    .compact-shared-text-editor .shared-text-editor-toolbar { flex-basis: 32px; }
    .shared-text-editor.without-gutter .code-highlight, .shared-text-editor.without-gutter .file-editor { padding: 12px 14px; }
    .shared-text-editor.without-gutter .code-highlight { white-space: pre-wrap; }
    .shared-text-editor.without-gutter .file-editor { min-height: 0; resize: none; white-space: pre-wrap; }
    .module-file-editor-footer { align-items: center; border-top: 1px solid var(--light-line); display: flex; flex: 0 0 auto; gap: 10px; justify-content: space-between; min-height: 48px; padding: 8px 12px; }
    .dark .module-file-editor-footer { border-top-color: var(--dark-line); }
    .ai-prompt-field-header { align-items: center; display: flex; gap: 8px; justify-content: space-between; }
    .ai-prompt-field-header label { min-width: 0; }
    .ai-prompt-editor-button { flex: 0 0 auto; }
    .workflow-config-panel .module-config-body { min-width: 0; gap: 12px; padding: 12px; }
    .module-config-field { min-width: 0; }
    .module-config-field input, .module-config-field textarea, .module-config-field select { width: 100%; }
    .ai-target-fields { display: grid; gap: 8px; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    .ai-toolbar { align-items: center; display: flex; flex-wrap: wrap; gap: 6px; }
    .ai-toolbar .ai-status { flex: 1 1 120px; }
    .ai-toolbar .ai-live { margin-right: auto; }
    .ai-toolbar button, .ai-settings-actions button { min-height: 30px; }
    .ai-settings-button { flex: 0 0 28px; height: 28px; width: 28px; }
    .ai-settings-button svg { height: 19px; width: 19px; }
    .ai-primary { border-color: #0d9477 !important; color: #087c64; }
    .dark .ai-primary { color: #6ee7b7; }
    .ai-status { font-size: 12px; line-height: 1.5; margin: 0; overflow-wrap: anywhere; }
    .ai-status[data-error="true"] { color: #c63748; }
    .dark .ai-status[data-error="true"] { color: #fda4af; }
    .ai-status[data-stale="true"] { color: #94640b; }
    .dark .ai-status[data-stale="true"] { color: #fcd34d; }
    .ai-file-request { background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.35); border-radius: 5px; display: grid; gap: 7px; padding: 10px; }
    .ai-file-request[hidden] { display: none; }
    .ai-file-request strong, .ai-file-request p { font-size: 12px; margin: 0; overflow-wrap: anywhere; }
    .ai-file-request code { background: rgba(15, 23, 42, 0.08); border-radius: 3px; font-size: 11px; overflow-wrap: anywhere; padding: 5px 7px; }
    .dark .ai-file-request { background: rgba(14, 165, 233, 0.12); border-color: rgba(56, 189, 248, 0.42); }
    .dark .ai-file-request code { background: rgba(2, 6, 23, 0.42); }
    .ai-live { align-items: center; display: inline-flex; font-size: 12px; gap: 6px; }
    .ai-live input { accent-color: #0d9477; height: 14px; margin: 0; width: 14px; }
    .ai-code { width: 100%; }
    .ai-section { border-top: 1px solid var(--light-line); min-width: 0; padding-top: 10px; }
    .dark .ai-section { border-top-color: var(--dark-line); }
    .ai-section-title { font-size: 12px; font-weight: 700; margin: 0 0 8px; overflow-wrap: anywhere; }
    .workflow-config-panel [data-ai-route-type] { flex: 0 0 auto; margin: 10px 12px 0; }
    .ai-connections { display: grid; gap: 5px; }
    .ai-connection { background: transparent; border: 0; color: inherit; cursor: pointer; font: inherit; font-size: 12px; padding: 5px 0; text-align: left; overflow-wrap: anywhere; }
    .ai-connection:hover { color: #0d9477; text-decoration: underline; }
    .ai-connection small { display: block; font-size: 11px; opacity: .75; }
    .ai-suggestion { border-bottom: 1px solid var(--light-line); font-size: 12px; line-height: 1.5; padding: 8px 0; overflow-wrap: anywhere; }
    .dark .ai-suggestion { border-bottom-color: var(--dark-line); }
    .ai-suggestion p { margin: 0 0 5px; }
    .ai-table-scroll { max-height: 300px; overflow: auto; }
    .ai-schema { border-collapse: collapse; font-size: 12px; text-align: left; width: 100%; }
    .ai-schema th, .ai-schema td { border-bottom: 1px solid var(--light-line); padding: 7px 8px; overflow-wrap: anywhere; max-width: 180px; vertical-align: top; }
    .dark .ai-schema th, .dark .ai-schema td { border-bottom-color: var(--dark-line); }
    .ai-schema th { background: var(--console-light); font-weight: 600; position: sticky; top: 0; }
    .dark .ai-schema th { background: var(--console-dark); }
    .ai-schema td:first-child { font-family: Consolas, monospace; }
    .ai-schema td:nth-child(3) { color: #087c64; }
    .dark .ai-schema td:nth-child(3) { color: #6ee7b7; }
    .workflow-frontend { background: var(--light-canvas); border: 1px solid var(--light-line); border-radius: 7px; color: inherit; font: inherit; font-size: 12px; max-width: 160px; min-height: 30px; padding: 4px 8px; }
    .dark .workflow-frontend { background: var(--dark-canvas); border-color: var(--dark-line); }
    .ai-settings-modal { z-index: 220; }
    .ai-settings-dialog { background: var(--light-canvas); border: 1px solid var(--light-line); border-radius: 12px; box-shadow: 0 24px 80px rgba(2, 6, 23, .42); color: var(--light-text); max-height: calc(100dvh - 32px); max-width: calc(100% - 24px); overflow: auto; padding: 0; width: 480px; }
    .dark .ai-settings-dialog { background: var(--dark-canvas); border-color: var(--dark-line); color: var(--dark-text); }
    .ai-settings-header { align-items: center; border-bottom: 1px solid var(--light-line); display: flex; gap: 12px; justify-content: space-between; padding: 12px 16px; }
    .dark .ai-settings-header { border-bottom-color: var(--dark-line); }
    .ai-settings-header h2 { font-size: 16px; margin: 0; }
    .ai-settings-fields { border: 0; display: grid; gap: 14px; margin: 0; min-width: 0; padding: 16px; }
    .ai-settings-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    .ai-close { align-items: center; background: transparent; border: 1px solid transparent; border-radius: 7px; color: inherit; cursor: pointer; display: inline-flex; flex: 0 0 30px; font-family: sans-serif; font-size: 22px; height: 30px; justify-content: center; width: 30px; }
    .ai-close:hover { background: rgba(100, 116, 139, .12); border-color: var(--light-line); }
    .dark .ai-close:hover { border-color: var(--dark-line); }
    .ai-settings-dialog .module-config-field label { font-size: 11px; }
    .ai-settings-dialog .module-config-field input, .ai-settings-dialog .module-config-field select { font-size: 13px; min-height: 36px; }
    [data-ai-remove-key][hidden], [data-ai-stop][hidden], [data-ai-table][hidden], [data-ai-suggestions][hidden], [data-ai-setup][hidden] { display: none; }
    .love-button { color: #e25567; font-size: 17px; height: 28px; width: 28px; }
    .studio-header > .header-right > .icon-button,
    .studio-header > .header-right > .theme-button { border: 1px solid var(--light-line); border-radius: 3px; min-height: 28px; min-width: 28px; }
    .dark .studio-header > .header-right > .icon-button,
    .dark .studio-header > .header-right > .theme-button { border-color: var(--dark-line); }
    .love-button:hover, .love-button:focus-visible { color: #be123c; transform: scale(1.08); }
    .dark .love-button { color: #fb7185; }
    .dark .love-button:hover, .dark .love-button:focus-visible { color: #fda4af; }
    .love-modal { align-items: center; background: rgba(2, 6, 23, .62); display: none; inset: 0; justify-content: center; padding: 16px; position: fixed; z-index: 180; }
    .love-modal.active { display: flex; }
    .love-card { background: var(--light-canvas); border: 1px solid var(--light-line); border-radius: 6px; box-shadow: 0 20px 70px rgba(2, 6, 23, .35); color: var(--light-text); max-height: min(720px, calc(100dvh - 32px)); max-width: 520px; overflow: auto; padding: 24px; position: relative; width: 100%; }
    .dark .love-card { background: var(--dark-canvas); border-color: var(--dark-line); color: var(--dark-text); }
    .love-close { background: transparent; border: 0; color: inherit; cursor: pointer; font-family: sans-serif; font-size: 24px; line-height: 1; padding: 2px 6px; position: absolute; right: 10px; top: 10px; }
    .love-mark { color: #e25567; font-size: 28px; line-height: 1; margin-bottom: 12px; }
    .dark .love-mark { color: #fb7185; }
    .love-card h2 { font-size: 18px; line-height: 1.35; margin: 0 32px 8px 0; }
    .love-intro { color: #475569; font-size: 13px; line-height: 1.5; margin: 0 0 14px; }
    .dark .love-intro { color: #cbd5e1; }
    .love-links { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
    .love-links a { border: 1px solid var(--light-line); border-radius: 3px; color: inherit; font-size: 12px; padding: 7px 10px; text-decoration: none; }
    .dark .love-links a { border-color: var(--dark-line); }
    .love-links a:hover, .love-links a:focus-visible { border-color: #0d9477; color: #087c64; }
    .love-external-icon { display: inline-block; font-size: 14px; font-weight: 700; line-height: 0; margin-left: 3px; transform: translateY(-1px); }
    .love-wallets { display: grid; gap: 8px; }
    .love-wallet { align-items: center; background: var(--console-light); border: 1px solid var(--light-line); border-radius: 3px; display: grid; gap: 6px 8px; grid-template-columns: 105px minmax(0, 1fr) auto; padding: 8px; }
    .dark .love-wallet { background: var(--console-dark); border-color: var(--dark-line); }
    .love-wallet-label { font-size: 12px; font-weight: 700; }
    .love-wallet code { font-family: "JetBrains Mono", "Fira Code", Consolas, monospace; font-size: 10px; min-width: 0; overflow-wrap: anywhere; }
    .love-copy { background: transparent; border: 1px solid var(--light-line); border-radius: 2px; color: inherit; cursor: pointer; font: inherit; font-size: 11px; padding: 4px 7px; }
    .dark .love-copy { border-color: var(--dark-line); }
    .love-copy:hover, .love-copy:focus-visible { border-color: #0d9477; color: #087c64; }
    .love-copy-status { color: #087c64; font-size: 12px; min-height: 18px; margin: 10px 0 0; }
    .dark .love-copy-status { color: #6ee7b7; }

    /* Consistent, keyboard-friendly controls across static and generated Studio UI. */
    .workflow-action, .module-type-button, .module-node-action, .workflow-config-action,
    .manual-save-button, .directory-action, .module-editor-back, .love-copy,
    .studio-header > .header-right > .icon-button,
    .studio-header > .header-right > .theme-button { border-radius: 7px; transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .15s ease; }
    .workflow-action:hover, .module-type-button:hover, .workflow-config-action:hover,
    .manual-save-button:hover, .directory-action:hover { border-color: #94a3b8; }
    button:focus-visible, a:focus-visible, input:focus-visible, textarea:focus-visible,
    [role="option"]:focus-visible, [role="combobox"]:focus-visible {
        outline: 2px solid #0d9477;
        outline-offset: 2px;
    }
    button:active:not(:disabled), .workflow-action:active, .directory-action:active { transform: translateY(1px); }
    .module-config-field input, .module-config-field textarea, .editor-name, .directory-input { border-radius: 7px; }
    .studio-modal-card, .workflow-json-card, .directory-context-menu, .edge-settings-popover { border-radius: 10px; }
    .studio-modal { backdrop-filter: blur(3px); }
    .studio-modal-actions { border-top: 1px solid var(--light-line); }
    .dark .studio-modal-actions { border-top-color: var(--dark-line); }
    .directory-delete-action { border-color: rgba(220, 38, 38, .5) !important; color: #b91c1c; }
    .dark .directory-delete-action { color: #fca5a5; }

    .custom-select { min-width: 0; position: relative; width: 100%; }
    .workflow-editor-header .custom-select { max-width: 180px; width: auto; }
    .native-select-control { height: 1px !important; left: 0 !important; opacity: 0 !important; pointer-events: none !important; position: absolute !important; top: 100% !important; width: 1px !important; }
    .custom-select-trigger { align-items: center; background: #fff; border: 1px solid var(--light-line); border-radius: 7px; color: #0f172a; cursor: pointer; display: flex; font: inherit; font-size: .75rem; gap: 8px; justify-content: space-between; min-height: 34px; padding: 6px 9px; text-align: left; width: 100%; }
    .custom-select-trigger::after { border-bottom: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; border-top: 5px solid currentColor; content: ""; flex: 0 0 auto; opacity: .65; transition: transform .15s ease; }
    .custom-select.open .custom-select-trigger::after { transform: rotate(180deg); }
    .dark .custom-select-trigger { background: #0f172a; border-color: #334155; color: #e2e8f0; }
    .custom-select-trigger:hover { border-color: #94a3b8; }
    .custom-select-trigger:disabled { cursor: not-allowed; opacity: .5; }
    .custom-select-menu { background: #fff; border: 1px solid var(--light-line); border-radius: 9px; box-shadow: 0 16px 38px rgba(15, 23, 42, .22); display: grid; gap: 3px; left: 0; margin-top: 5px; max-height: 240px; min-width: max(100%, 180px); overflow: auto; padding: 5px; position: absolute; top: 100%; z-index: 260; }
    .custom-select-menu[hidden] { display: none; }
    .dark .custom-select-menu { background: #111827; border-color: #334155; box-shadow: 0 18px 42px rgba(0, 0, 0, .48); }
    .custom-select-option { background: transparent; border: 0; border-radius: 6px; color: inherit; cursor: pointer; font: inherit; font-size: .75rem; min-height: 30px; padding: 6px 8px; text-align: left; white-space: nowrap; width: 100%; }
    .custom-select-option:hover, .custom-select-option:focus-visible { background: #eef2f7; }
    .dark .custom-select-option:hover, .dark .custom-select-option:focus-visible { background: #243047; }
    .custom-select-option[aria-selected="true"] { background: rgba(13, 148, 119, .12); color: #087c64; font-weight: 700; }
    .dark .custom-select-option[aria-selected="true"] { color: #6ee7b7; }
    .custom-select-option:disabled { cursor: not-allowed; opacity: .45; }

    @media (max-width: 1000px) {
        .workflow-workspace > .workflow-config-panel { flex-basis: 62%; }
        .studio-header > .header-left { gap: 6px; min-width: 0; }
        .studio-header > .header-right { gap: 4px; min-width: 0; }
    }
    @media (max-width: 760px) {
        .studio-sidebar { bottom: 0; left: 0; position: absolute; top: 0; width: min(340px, 86%); z-index: 125; }
        .studio-main { width: 100%; }
        .studio-header { flex-wrap: wrap; gap: 5px; height: auto; min-height: 45px; padding: 5px 8px; }
        .studio-header > .header-left { flex: 1 1 auto; }
        .studio-header > .header-center { flex: 1 0 100%; order: 3; }
        .fullscreen-button { margin: 0; }
        .workflow-workspace > .workflow-config-panel { bottom: 0; left: 0; max-width: none; min-width: 0; position: absolute; right: 0; top: 0; width: 100%; }
        .workflow-editor-header { flex-wrap: wrap; gap: 8px; }
        .workflow-editor-title { min-width: 0; }
        .ai-target-fields { grid-template-columns: minmax(0, 1fr); }
        .compact-shared-text-editor { min-height: 135px; }
        .love-card { padding: 20px 14px; }
        .love-wallet { grid-template-columns: minmax(0, 1fr) auto; }
        .love-wallet-label { grid-column: 1 / -1; }
        .love-wallet code { grid-column: 1; }
        .love-copy { grid-column: 2; grid-row: 2; }
    }
</style>
