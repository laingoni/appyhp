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
    .module-config-header > span { min-width: 0; overflow-wrap: anywhere; }
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
    .ai-live { align-items: center; display: inline-flex; font-size: 12px; gap: 6px; }
    .ai-live input { accent-color: #0d9477; height: 14px; margin: 0; width: 14px; }
    .ai-code { background: var(--console-light); border: 1px solid var(--light-line); border-radius: 3px; color: inherit; display: block; margin: 0; max-height: 330px; min-height: 160px; overflow: auto; padding: 10px; resize: vertical; tab-size: 4; width: 100%; }
    .dark .ai-code { background: var(--console-dark); border-color: var(--dark-line); }
    .ai-code, .ai-code * { font-family: "JetBrains Mono", "Fira Code", Consolas, monospace; font-size: 12px; line-height: 1.6; }
    .ai-section { border-top: 1px solid var(--light-line); min-width: 0; padding-top: 10px; }
    .dark .ai-section { border-top-color: var(--dark-line); }
    .ai-section-title { font-size: 12px; font-weight: 700; margin: 0 0 8px; overflow-wrap: anywhere; }
    .ai-section summary { cursor: pointer; font-size: 12px; font-weight: 700; }
    .ai-fields { display: grid; gap: 10px; padding-top: 10px; }
    .workflow-config-panel details.ai-section { background: var(--light-canvas); border: 1px solid var(--light-line); border-radius: 5px; flex: 0 0 auto; margin: 10px 12px 0; overflow: hidden; padding: 0; }
    .workflow-config-panel details.ai-section summary { list-style: none; padding: 10px 12px; }
    .workflow-config-panel details.ai-section summary::-webkit-details-marker { display: none; }
    .workflow-config-panel details.ai-section summary::before { content: '▸'; display: inline-block; margin-right: 7px; transition: transform .15s ease; }
    .workflow-config-panel details.ai-section[open] summary { border-bottom: 1px solid var(--light-line); }
    .workflow-config-panel details.ai-section[open] summary::before { transform: rotate(90deg); }
    .workflow-config-panel details.ai-section > .ai-fields { padding: 10px 12px 12px; }
    .dark .workflow-config-panel details.ai-section { background: var(--dark-canvas); border-color: var(--dark-line); }
    .dark .workflow-config-panel details.ai-section[open] summary { border-bottom-color: var(--dark-line); }
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
    .workflow-frontend { background: var(--light-canvas); border: 1px solid var(--light-line); border-radius: 2px; color: inherit; font: inherit; font-size: 12px; max-width: 160px; min-height: 28px; padding: 3px 5px; }
    .dark .workflow-frontend { background: var(--dark-canvas); border-color: var(--dark-line); }
    .ai-settings-dialog { background: var(--light-canvas); border: 1px solid var(--light-line); border-radius: 6px; color: var(--light-text); max-height: calc(100dvh - 32px); max-width: calc(100% - 24px); padding: 0; width: 480px; }
    .ai-settings-dialog::backdrop { background: rgba(0, 0, 0, .55); }
    .dark .ai-settings-dialog { background: var(--dark-canvas); border-color: var(--dark-line); color: var(--dark-text); }
    .ai-settings-header { align-items: center; border-bottom: 1px solid var(--light-line); display: flex; gap: 12px; justify-content: space-between; padding: 12px 16px; }
    .dark .ai-settings-header { border-bottom-color: var(--dark-line); }
    .ai-settings-header h2 { font-size: 16px; margin: 0; }
    .ai-settings-fields { border: 0; display: grid; gap: 14px; margin: 0; min-width: 0; padding: 16px; }
    .ai-settings-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    .ai-close { background: transparent; border: 0; color: inherit; cursor: pointer; flex: 0 0 28px; font-family: sans-serif; font-size: 22px; height: 28px; width: 28px; }
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
        .ai-code { max-height: 280px; }
        .love-card { padding: 20px 14px; }
        .love-wallet { grid-template-columns: minmax(0, 1fr) auto; }
        .love-wallet-label { grid-column: 1 / -1; }
        .love-wallet code { grid-column: 1; }
        .love-copy { grid-column: 2; grid-row: 2; }
    }
</style>
