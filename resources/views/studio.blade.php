<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Appyhp Studio">
    <meta name="csrf-token" content="{{ $studioCsrfToken }}">
    <link rel="icon" type="image/svg+xml" href="{{ url('/appyhp/studio/assets/appyhp-icon.svg') }}">
    <title>AppyHP Studio</title>
    <script>
        (function () {
            var storedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.toggle('dark', storedTheme === 'dark');
        })();
    </script>
    <style>
        :root {
            --dark-canvas: #1e2237;
            --dark-content: #101927;
            --dark-text: #b6c2c8;
            --dark-line: #2c3150;
            --console-dark: #161a30;
            --panel-dark: #232840;
            --light-canvas: #fbfcfe;
            --light-content: #b6c2c8;
            --light-text: #101927;
            --light-line: #d0d6e3;
            --console-light: #eef1f7;
            --panel-light: #e3e7f1;
            --console-border-light: #d0d6e3;
            --console-border-dark: #2c3150;
            --studio-scale: 0.85;
            color-scheme: light;
        }

        :root.dark {
            color-scheme: dark;
        }

        * {
            box-sizing: border-box;
            font-family: Spectral, Georgia, serif;
            scrollbar-width: thin;
            scrollbar-color: rgba(56, 189, 248, 0.75) rgba(15, 23, 42, 0.35);
        }

        *::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        *::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.35);
        }

        *::-webkit-scrollbar-thumb {
            background-clip: content-box;
            background-color: rgba(56, 189, 248, 0.75);
            border: 2px solid transparent;
            border-radius: 999px;
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            background: var(--light-canvas);
            color: var(--light-text);
            overflow: hidden;
        }

        .dark body {
            background: var(--dark-canvas);
            color: var(--dark-text);
        }

        button,
        a {
            color: inherit;
        }

        button {
            font: inherit;
        }

        button, a, select, summary, input[type="checkbox"], input[type="radio"] {
            cursor: pointer;
        }

        .studio-shell {
            background: var(--light-canvas);
            color: var(--light-text);
            display: flex;
            font-size: 90%;
            height: 100vh;
            line-height: 1.35;
            overflow: hidden;
            width: 100%;
        }

        .dark .studio-shell {
            background: var(--dark-canvas);
            color: var(--dark-text);
        }

        .studio-shell svg {
            transform: scale(var(--studio-scale));
            transform-origin: center;
        }

        .studio-layout {
            display: flex;
            height: 100%;
            overflow: hidden;
            width: 100%;
        }

        .studio-sidebar {
            background: var(--light-canvas);
            border-left: 1px solid var(--console-border-light);
            display: flex;
            flex-direction: column;
            height: 100%;
            min-width: 0;
            opacity: 1;
            overflow: hidden;
            transition: width 300ms ease, opacity 300ms ease;
            width: 30%;
        }

        .dark .studio-sidebar {
            background: var(--dark-canvas);
            border-left-color: var(--console-border-dark);
        }

        .studio-shell.sidebar-closed .studio-sidebar {
            opacity: 0;
            width: 0;
        }

        .studio-main {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            transition: width 300ms ease;
            width: 70%;
        }

        .studio-shell.sidebar-closed .studio-main {
            width: 100%;
        }

        .studio-header,
        .studio-sidebar-header {
            align-items: center;
            background: var(--panel-light);
            border-bottom: 1px solid var(--light-line);
            display: flex;
            height: 45px;
            min-height: 45px;
            padding: 0 0.5rem;
        }

        .dark .studio-header,
        .dark .studio-sidebar-header {
            background: var(--panel-dark);
            border-bottom-color: var(--dark-line);
        }

        .studio-sidebar-header {
            justify-content: space-between;
        }

        .studio-brand {
            align-items: center;
            display: inline-flex;
            font-size: 1.0125rem;
            font-weight: 600;
            justify-content: center;
            line-height: 1.35;
            min-width: 0;
            text-decoration: none;
            white-space: nowrap;
        }

        .studio-brand-icon {
            display: inline-flex;
            margin-right: 0.5rem;
        }

        .icon-button {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 2px;
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
            line-height: 1;
            margin: 0;
            padding: 0.25rem;
        }

        .theme-button {
            background: transparent;
            border: 0;
            cursor: pointer;
            line-height: 1;
            margin: 0;
            padding: 0;
            vertical-align: middle;
        }

        .dark .theme-button {
            background: #1f2937;
        }

        .header-left,
        .header-right {
            align-items: center;
            display: flex;
            gap: 0.5rem;
        }

        .header-left {
            gap: 1rem;
            min-width: 160px;
        }

        .header-center {
            align-items: center;
            display: flex;
            flex: 1 1 auto;
            justify-content: center;
            min-width: 0;
        }

        .header-right {
            justify-content: flex-end;
            min-width: 120px;
        }

        .sidebar-toggle-target {
            align-items: center;
            display: inline-flex;
        }

        .sidebar-content {
            background: var(--console-light);
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        .dark .sidebar-content {
            background: var(--console-dark);
        }

        .sidebar-lower {
            background: var(--console-light);
            height: 100%;
            min-height: 0;
            overflow: auto;
        }

        .dark .sidebar-lower {
            background: var(--console-dark);
        }

        .manual-save-button {
            align-items: center;
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 2px;
            color: inherit;
            cursor: pointer;
            display: inline-flex;
            font-size: 0.675rem;
            gap: 0.25rem;
            justify-content: center;
            min-height: 28px;
            padding: 0.25rem 0.55rem;
        }

        .dark .manual-save-button {
            background: var(--dark-canvas);
            border-color: var(--dark-line);
        }

        .manual-save-button:hover {
            background: #f1f5f9;
        }

        .dark .manual-save-button:hover {
            background: #1f2937;
        }

        .manual-save-button:disabled,
        .history-button:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        .dirty-dot {
            background: #ef4444;
            border-radius: 999px;
            display: none;
            height: 6px;
            width: 6px;
        }

        .manual-save-button.dirty .dirty-dot {
            display: inline-block;
        }

        .autosave-control {
            align-items: center;
            cursor: pointer;
            display: inline-flex;
            font-size: 0.675rem;
            gap: 0.4rem;
            user-select: none;
            white-space: nowrap;
        }

        .autosave-control input {
            accent-color: #16a34a;
            height: 16px;
            margin: 0;
            width: 16px;
        }

        .history-button {
            align-items: center;
            background: transparent;
            border: 0;
            color: inherit;
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
            padding: 0.25rem;
        }

        .panel-switcher {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .panel-switcher-button {
            background: #d1d5db;
            border: 0;
            border-radius: 999px;
            color: #1f2937;
            cursor: pointer;
            height: 30px;
            outline: none;
            padding: 0;
            position: relative;
            width: 122px;
        }

        .dark .panel-switcher-button {
            background: #374151;
            color: #e5e7eb;
        }

        .panel-switcher-label {
            align-items: center;
            display: flex;
            font-size: 0.65rem;
            inset: 0;
            pointer-events: none;
            position: absolute;
            transition: justify-content 300ms ease;
        }

        .panel-switcher-label.workflows {
            justify-content: flex-end;
            padding: 0 0.7rem 0 3.7rem;
        }

        .panel-switcher-label.directories {
            justify-content: flex-start;
            padding: 0 3.7rem 0 0.7rem;
        }

        .panel-switcher-thumb {
            background: #ffffff;
            border-radius: 999px;
            bottom: 2px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.24);
            left: 0;
            margin: auto 0;
            position: absolute;
            top: 2px;
            transition: left 300ms ease;
            width: 50%;
        }

        .dark .panel-switcher-thumb {
            background: #4b5563;
        }

        .panel-switcher-button.directories .panel-switcher-thumb {
            left: 50%;
        }

        .canvas-container {
            background-attachment: fixed;
            background-image:
                repeating-linear-gradient(60deg, rgba(52, 135, 146, 0.10) 0 1px, transparent 1px 22px),
                repeating-linear-gradient(120deg, rgba(52, 135, 146, 0.10) 0 1px, transparent 1px 22px),
                repeating-linear-gradient(90deg, rgba(52, 135, 146, 0.10) 0 1px, transparent 1px 22px);
            background-size: calc(1.732 * 22px) calc(3 * 22px);
            display: grid;
            flex: 1 1 auto;
            grid-template-columns: minmax(0, 1fr);
            min-height: 0;
            overflow: auto;
        }

        .workflow-services {
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 2px;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .dark .workflow-services {
            background: var(--dark-canvas);
            border-color: var(--dark-line);
        }

        .workflow-services-header,
        .workflow-editor-header,
        .workflow-toolbar,
        .module-config-header {
            align-items: center;
            border-bottom: 1px solid var(--light-line);
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            min-height: 38px;
            padding: 0.4rem 0.55rem;
        }

        .workflow-services-actions {
            align-items: center;
            display: inline-flex;
            gap: 0.3rem;
            margin-left: auto;
        }

        .dark .workflow-services-header,
        .dark .workflow-editor-header,
        .dark .workflow-toolbar,
        .dark .module-config-header {
            border-bottom-color: var(--dark-line);
        }

        .workflow-title {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .workflow-meta {
            color: #64748b;
            display: block;
            font-size: 0.625rem;
            line-height: 1.25;
        }

        .dark .workflow-meta {
            color: #94a3b8;
        }

        .workflow-action,
        .module-type-button,
        .module-node-action,
        .edge-chip,
        .workflow-config-action {
            align-items: center;
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 2px;
            color: inherit;
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
        }

        .dark .workflow-action,
        .dark .module-type-button,
        .dark .module-node-action,
        .dark .edge-chip,
        .dark .workflow-config-action {
            background: var(--dark-canvas);
            border-color: var(--dark-line);
        }

        .workflow-action {
            font-size: 0.675rem;
            gap: 0.25rem;
            min-height: 28px;
            padding: 0.25rem 0.5rem;
        }

        .workflow-action.icon-only {
            height: 28px;
            padding: 0;
            width: 28px;
        }

        .workflow-action:hover,
        .module-type-button:hover,
        .workflow-config-action:hover {
            background: #f1f5f9;
        }

        .dark .workflow-action:hover,
        .dark .module-type-button:hover,
        .dark .workflow-config-action:hover {
            background: #1f2937;
        }

        .workflow-list {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding: 0.4rem;
        }

        .workflow-card {
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid var(--light-line);
            border-radius: 4px;
            margin-bottom: 0.35rem;
            overflow: hidden;
        }

        .dark .workflow-card {
            background: rgba(16, 25, 39, 0.78);
            border-color: var(--dark-line);
        }

        .workflow-card.active {
            border-color: #10b981;
            box-shadow: inset 3px 0 0 #10b981;
        }

        .workflow-card-main {
            align-items: center;
            background: transparent;
            border: 0;
            color: inherit;
            cursor: pointer;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            min-width: 0;
            padding: 0.5rem;
            text-align: left;
            width: 100%;
        }

        .workflow-card-details {
            border-top: 1px solid var(--light-line);
            display: none;
            gap: 0.35rem;
            grid-template-columns: minmax(0, 55fr) repeat(3, minmax(28px, 15fr));
            padding: 0.45rem;
        }

        .workflow-card-details .workflow-action {
            min-width: 0;
        }

        .workflow-card-details .workflow-action svg {
            height: 16px;
            width: 16px;
        }

        .dark .workflow-card-details {
            border-top-color: var(--dark-line);
        }

        .workflow-card.open .workflow-card-details {
            display: grid;
        }

        .workflow-editor {
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 2px;
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .dark .workflow-editor {
            background: var(--dark-canvas);
            border-color: var(--dark-line);
        }

        .workflow-editor-title {
            flex: 1 1 auto;
            min-width: 0;
        }

        .workflow-editor-title .workflow-title,
        .workflow-editor-title .workflow-meta {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .workflow-toolbar {
            background: var(--console-light);
            min-height: 36px;
            padding: 0.35rem 0.45rem;
        }

        .dark .workflow-toolbar {
            background: var(--console-dark);
        }

        .module-palette {
            display: flex;
            flex: 1 1 auto;
            gap: 0.35rem;
            min-width: 0;
            overflow-x: auto;
            padding-bottom: 0.15rem;
            scroll-behavior: smooth;
            scrollbar-width: none;
        }

        .module-palette::-webkit-scrollbar {
            display: none;
        }

        .module-palette-scroll {
            flex: 0 0 28px;
            height: 28px;
            padding: 0;
            width: 28px;
        }

        .module-palette-group {
            align-items: center;
            border: 1px solid var(--light-line);
            border-radius: 3px;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 0.2rem;
            padding: 0.2rem;
        }

        .dark .module-palette-group {
            border-color: var(--dark-line);
        }

        .module-type-button {
            font-size: 0.625rem;
            min-height: 25px;
            padding: 0.2rem 0.42rem;
            white-space: nowrap;
        }

        .workflow-zoom {
            align-items: center;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 0.3rem;
        }

        .workflow-canvas-tools {
            align-items: center;
            background: rgba(251, 252, 254, 0.92);
            border: 1px solid var(--light-line);
            border-radius: 3px;
            display: inline-flex;
            gap: 0.35rem;
            padding: 0.35rem;
            position: absolute;
            right: 0.65rem;
            top: 0.65rem;
            z-index: 100;
        }

        .dark .workflow-canvas-tools {
            background: rgba(30, 34, 55, 0.92);
            border-color: var(--dark-line);
        }

        .workflow-editor-header .header-right {
            flex: 0 0 auto;
            gap: 0.35rem;
            min-width: 0;
        }

        .workflow-zoom-box {
            align-items: center;
            border: 1px solid var(--light-line);
            border-radius: 2px;
            display: inline-flex;
        }

        .dark .workflow-zoom-box {
            border-color: var(--dark-line);
        }

        .workflow-zoom-box button,
        .workflow-zoom-box span {
            background: transparent;
            border: 0;
            color: inherit;
            font-size: 0.625rem;
            min-width: 32px;
            padding: 0.25rem 0.35rem;
            text-align: center;
        }

        .workflow-zoom-box button {
            cursor: pointer;
        }

        .workflow-canvas-shell {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            position: relative;
        }

        .workflow-canvas-empty {
            align-items: center;
            color: #64748b;
            display: flex;
            font-size: 0.75rem;
            height: 100%;
            justify-content: center;
        }

        .workflow-canvas-empty[hidden],
        .workflow-stage[hidden] {
            display: none !important;
        }

        .dark .workflow-canvas-empty {
            color: #94a3b8;
        }

        .workflow-stage {
            min-height: 720px;
            min-width: 1100px;
            position: relative;
            transform-origin: top left;
        }

        .workflow-stage-grid {
            background-image: radial-gradient(circle at 1px 1px, rgba(100, 116, 139, 0.45) 1px, transparent 0);
            background-size: 16px 16px;
        }

        .workflow-edge-layer,
        .workflow-node-layer {
            inset: 0;
            position: absolute;
        }

        .workflow-edge-layer {
            overflow: visible;
            pointer-events: none;
            transform: none !important;
            transform-origin: top left;
            z-index: 60;
        }

        .workflow-node-layer {
            z-index: 40;
        }

        .module-node {
            border: 1px solid #38bdf8;
            border-radius: 7px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.22);
            color: #0f172a;
            cursor: move;
            min-height: 56px;
            overflow: visible;
            padding: 0.45rem 0.55rem;
            position: absolute;
            user-select: none;
            will-change: left, top;
            width: 148px;
            z-index: 45;
        }

        .dark .module-node {
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.35);
            color: #e2e8f0;
        }

        .module-node.connect-source {
            outline: 2px solid #10b981;
            outline-offset: 3px;
        }

        .module-node.connect-target {
            outline: 1px solid rgba(16, 185, 129, 0.7);
            outline-offset: 2px;
        }

        .module-node[data-type="route"],
        .module-node[data-type="middleware"],
        .module-node[data-type="auth"] {
            background: rgba(16, 185, 129, 0.30);
            border-color: #10b981;
        }

        .module-node[data-type="controller"],
        .module-node[data-type="request"],
        .module-node[data-type="resource"],
        .module-node[data-type="service"],
        .module-node[data-type="repository"] {
            background: rgba(14, 165, 233, 0.30);
            border-color: #0ea5e9;
        }

        .module-node[data-type="model"],
        .module-node[data-type="table"],
        .module-node[data-type="migration"],
        .module-node[data-type="seeder"],
        .module-node[data-type="factory"] {
            background: rgba(244, 63, 94, 0.24);
            border-color: #f43f5e;
        }

        .module-node[data-type="job"],
        .module-node[data-type="queue"],
        .module-node[data-type="event"],
        .module-node[data-type="listener"],
        .module-node[data-type="notification"],
        .module-node[data-type="mail"],
        .module-node[data-type="command"] {
            background: rgba(245, 158, 11, 0.28);
            border-color: #f59e0b;
        }

        .module-node[data-type="view"],
        .module-node[data-type="component"],
        .module-node[data-type="policy"],
        .module-node[data-type="cache"],
        .module-node[data-type="storage"] {
            background: rgba(168, 85, 247, 0.24);
            border-color: #a855f7;
        }

        .module-node-top {
            align-items: center;
            display: flex;
            gap: 0.35rem;
            justify-content: space-between;
            min-width: 0;
        }

        .module-node-type {
            color: rgba(15, 23, 42, 0.65);
            font-size: 0.55rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .dark .module-node-type {
            color: rgba(226, 232, 240, 0.68);
        }

        .module-node-label {
            background: transparent;
            border: 0;
            color: inherit;
            cursor: text;
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            margin-top: 0.25rem;
            min-width: 0;
            overflow: hidden;
            padding: 0;
            text-align: left;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
        }

        .module-node-description {
            color: rgba(15, 23, 42, 0.70);
            display: block;
            font-size: 0.56rem;
            margin-top: 0.2rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .module-node-description {
            color: rgba(226, 232, 240, 0.70);
        }

        .module-node-actions {
            display: inline-flex;
            gap: 0.15rem;
        }

        .module-node-action {
            background: rgba(15, 23, 42, 0.82);
            border-color: rgba(15, 23, 42, 0.38);
            color: #e2e8f0;
            font-size: 0.58rem;
            height: 17px;
            line-height: 1;
            padding: 0;
            width: 17px;
        }

        .module-node-action:hover {
            background: #10b981;
            color: #052e1c;
        }

        .module-node-action.delete:hover {
            background: #ef4444;
            color: #fff;
        }

        .edge-control {
            cursor: pointer;
            pointer-events: auto;
        }

        .edge-control:hover {
            filter: brightness(1.25);
        }

        .workflow-edge-layer path {
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .edge-settings-popover {
            background: rgba(15, 23, 42, 0.96);
            border: 1px solid #475569;
            border-radius: 4px;
            color: #e2e8f0;
            display: none;
            gap: 0.4rem;
            min-width: 190px;
            padding: 0.5rem;
            position: absolute;
            z-index: 90;
        }

        .edge-settings-popover.active {
            display: grid;
        }

        .edge-settings-popover label {
            display: grid;
            font-size: 0.625rem;
            gap: 0.2rem;
        }

        .edge-settings-popover input,
        .edge-settings-popover select {
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 2px;
            color: #e2e8f0;
            font: inherit;
            padding: 0.25rem;
        }

        .workflow-config-panel {
            background: var(--light-canvas);
            border-left: 1px solid var(--light-line);
            bottom: 0;
            display: none;
            max-width: 380px;
            overflow: auto;
            position: absolute;
            right: 0;
            top: 0;
            width: 34%;
            z-index: 95;
        }

        .dark .workflow-config-panel {
            background: var(--dark-canvas);
            border-left-color: var(--dark-line);
        }

        .workflow-config-panel.active {
            display: flex;
            flex-direction: column;
        }

        .module-config-body {
            display: grid;
            gap: 0.65rem;
            padding: 0.65rem;
        }

        .module-config-field {
            display: grid;
            gap: 0.25rem;
        }

        .module-config-field label {
            color: #475569;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .dark .module-config-field label {
            color: #cbd5e1;
        }

        .module-config-field input,
        .module-config-field textarea,
        .module-config-field select {
            background: #fff;
            border: 1px solid var(--light-line);
            border-radius: 2px;
            color: #0f172a;
            font: inherit;
            font-size: 0.75rem;
            min-width: 0;
            padding: 0.42rem 0.5rem;
        }

        .dark .module-config-field input,
        .dark .module-config-field textarea,
        .dark .module-config-field select {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        .module-config-field textarea {
            min-height: 74px;
            resize: vertical;
        }

        .workflow-json-modal {
            align-items: center;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            inset: 0;
            justify-content: center;
            padding: 1rem;
            position: fixed;
            z-index: 140;
        }

        .workflow-json-modal.active {
            display: flex;
        }

        .workflow-json-card {
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 4px;
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
            max-height: min(760px, 90vh);
            max-width: min(920px, 92vw);
            min-height: 320px;
            overflow: hidden;
            width: 760px;
        }

        .workflow-json-card header {
            align-items: center;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            padding: 0.55rem 0.7rem;
        }

        .workflow-json-actions {
            align-items: center;
            display: flex;
            gap: 0.4rem;
        }

        .workflow-json-card pre {
            flex: 1 1 auto;
            font-family: "JetBrains Mono", "Fira Code", Consolas, monospace;
            font-size: 0.72rem;
            line-height: 1.45;
            margin: 0;
            overflow: auto;
            padding: 0.7rem;
            white-space: pre;
        }

        .studio-panel[data-studio-panel="directories"] {
            background: #f8fafc;
        }

        .studio-panel[data-studio-panel="workflows"].active {
            display: flex;
            flex-direction: column;
        }

        .dark .studio-panel[data-studio-panel="directories"] {
            background: #0b1020;
        }

        .studio-panel,
        .sidebar-panel {
            display: none;
            min-height: 100%;
            width: 100%;
        }

        .studio-panel.active,
        .sidebar-panel.active {
            display: block;
        }

        .sidebar-panel {
            overflow: auto;
            padding: 0.5rem;
        }

        .sidebar-panel[data-sidebar-panel="directories"].active,
        .studio-panel[data-studio-panel="directories"].active {
            display: flex;
            flex-direction: column;
        }

        .directory-panel {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 0.5rem;
            min-height: 0;
        }

        .directory-input {
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 2px;
            color: var(--light-text);
            font: inherit;
        }

        .directory-input {
            font-size: 0.675rem;
            min-width: 0;
            padding: 0.35rem 0.45rem;
        }

        .dark .directory-input {
            background: var(--dark-canvas);
            border-color: var(--dark-line);
            color: var(--dark-text);
        }

        .directory-action {
            align-items: center;
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 2px;
            color: inherit;
            cursor: pointer;
            display: inline-flex;
            font-size: 0.675rem;
            justify-content: center;
            min-height: 28px;
            padding: 0.25rem 0.5rem;
        }

        .dark .directory-action {
            background: var(--dark-canvas);
            border-color: var(--dark-line);
        }

        .directory-action:hover {
            background: #f1f5f9;
        }

        .dark .directory-action:hover {
            background: #1f2937;
        }

        .directory-tree {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .tree-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .tree-children {
            list-style: none;
            margin: 0 0 0 0.85rem;
            padding: 0;
        }

        .tree-row {
            align-items: center;
            display: flex;
            gap: 0.15rem;
            min-width: 0;
            padding: 0.08rem 0;
        }

        .tree-toggle,
        .tree-entry {
            background: transparent;
            border: 0;
            color: inherit;
            cursor: pointer;
            font-size: 0.675rem;
            line-height: 1.25;
        }

        .tree-toggle {
            flex: 0 0 18px;
            height: 20px;
            opacity: 0.65;
            padding: 0;
            text-align: center;
            width: 18px;
        }

        .tree-entry {
            border-radius: 2px;
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            padding: 0.2rem 0.25rem;
            text-align: left;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tree-icon {
            align-items: center;
            color: #64748b;
            display: inline-flex;
            flex: 0 0 16px;
            height: 16px;
            justify-content: center;
            margin-right: 0.35rem;
            vertical-align: -3px;
            width: 16px;
        }

        .tree-icon svg {
            height: 16px;
            transform: none;
            width: 16px;
        }

        .tree-icon.folder {
            color: #d97706;
        }

        .tree-icon.file {
            color: #64748b;
        }

        .dark .tree-icon.folder {
            color: #fbbf24;
        }

        .dark .tree-icon.file {
            color: #94a3b8;
        }

        .tree-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tree-entry:hover,
        .tree-entry.active {
            background: rgba(15, 23, 42, 0.08);
        }

        .dark .tree-entry:hover,
        .dark .tree-entry.active {
            background: rgba(148, 163, 184, 0.12);
        }

        .directory-context-menu {
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 4px;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.24);
            color: var(--light-text);
            display: none;
            gap: 0.45rem;
            min-width: 230px;
            padding: 0.55rem;
            position: fixed;
            z-index: 180;
        }

        .directory-context-menu.active {
            display: grid;
        }

        .directory-context-menu [hidden] {
            display: none !important;
        }

        .dark .directory-context-menu {
            background: var(--dark-canvas);
            border-color: var(--dark-line);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.42);
            color: var(--dark-text);
        }

        .directory-context-title {
            color: #64748b;
            font-size: 0.625rem;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dark .directory-context-title {
            color: #94a3b8;
        }

        .directory-context-create {
            display: grid;
            gap: 0.35rem;
            grid-template-columns: minmax(0, 1fr) auto auto;
        }

        .directory-context-actions {
            display: grid;
            gap: 0.35rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .directory-context-menu button {
            cursor: pointer;
        }

        .directory-status,
        .editor-status,
        .editor-path {
            color: #64748b;
            font-size: 0.675rem;
            min-height: 18px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .directory-status,
        .dark .editor-status,
        .dark .editor-path {
            color: #94a3b8;
        }

        .directory-editor-panel {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
        }

        .editor-toolbar {
            align-items: center;
            background: var(--light-canvas);
            border-bottom: 1px solid var(--light-line);
            display: flex;
            gap: 0.5rem;
            min-height: 36px;
            padding: 0 0.5rem;
        }

        .dark .editor-toolbar {
            background: var(--dark-canvas);
            border-bottom-color: var(--dark-line);
        }

        .editor-path {
            flex: 1 1 auto;
        }

        .editor-name {
            background: transparent;
            border: 1px solid transparent;
            color: inherit;
            font-size: 0.675rem;
            min-width: 120px;
            padding: 0.25rem;
        }

        .editor-name:focus { border-color: var(--light-line); outline: none; }
        .dark .editor-name:focus { border-color: var(--dark-line); }

        .editor-toolbar button { flex: 0 0 auto; }

        .studio-modal {
            align-items: center;
            background: rgba(15, 23, 42, 0.62);
            display: none;
            inset: 0;
            justify-content: center;
            padding: 1rem;
            position: fixed;
            z-index: 20;
        }
        .studio-modal.active { display: flex; }
        .studio-modal-card {
            background: var(--light-canvas);
            border: 1px solid var(--light-line);
            border-radius: 4px;
            box-shadow: 0 18px 60px rgba(15, 23, 42, .3);
            max-width: 520px;
            width: min(100%, 520px);
        }
        .dark .studio-modal-card { background: var(--dark-canvas); border-color: var(--dark-line); }
        .studio-modal-header, .studio-modal-actions { align-items: center; display: flex; gap: .5rem; justify-content: space-between; padding: .65rem .8rem; }
        .studio-modal-header { border-bottom: 1px solid var(--light-line); }
        .dark .studio-modal-header { border-bottom-color: var(--dark-line); }
        .studio-modal-body { padding: .8rem; }
        .studio-modal-body textarea { min-height: 150px; resize: vertical; width: 100%; }
        .studio-modal-ai-result { border-top: 1px solid var(--light-line); margin-top: .75rem; padding-top: .75rem; white-space: pre-wrap; }
        .dark .studio-modal-ai-result { border-top-color: var(--dark-line); }
        .clickable { cursor: pointer; }

        .code-editor-shell {
            background: #f8fafc;
            border: 0;
            border-radius: 0;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            position: relative;
        }

        .dark .code-editor-shell {
            background: #0b1020;
            border-color: var(--dark-line);
        }

        .code-gutter,
        .code-highlight,
        .file-editor {
            font-family: "JetBrains Mono", "Fira Code", Consolas, monospace;
            font-size: 0.75rem;
            line-height: 1.5;
            tab-size: 4;
        }

        .code-gutter {
            background: #eef2f7;
            border-right: 1px solid var(--light-line);
            bottom: 0;
            color: #94a3b8;
            left: 0;
            overflow: hidden;
            padding: 0.75rem 0.5rem;
            pointer-events: none;
            position: absolute;
            text-align: right;
            top: 0;
            user-select: none;
            white-space: pre;
            width: 3.25rem;
            z-index: 2;
        }

        .dark .code-gutter {
            background: #111827;
            border-right-color: var(--dark-line);
            color: #526174;
        }

        .code-gutter-lines {
            min-height: 100%;
            transition: transform 80ms linear;
        }

        .code-highlight {
            bottom: 0;
            color: #334155;
            left: 0;
            margin: 0;
            min-height: 0;
            overflow: hidden;
            padding: 0.75rem 0.75rem 0.75rem 3.75rem;
            pointer-events: none;
            position: absolute;
            right: 0;
            top: 0;
            white-space: pre;
            z-index: 1;
        }

        .dark .code-highlight {
            color: #d1d5db;
        }

        .code-highlight code {
            font: inherit;
        }

        .file-editor {
            background: transparent;
            border: 0;
            bottom: 0;
            caret-color: #0f172a;
            color: transparent;
            left: 0;
            outline: none;
            overflow: auto;
            padding: 0.75rem 0.75rem 0.75rem 3.75rem;
            position: absolute;
            resize: none;
            right: 0;
            top: 0;
            white-space: pre;
            z-index: 3;
        }

        .dark .file-editor {
            caret-color: #f8fafc;
        }

        .file-editor::selection {
            background: rgba(59, 130, 246, 0.28);
            color: transparent;
        }

        .file-editor:disabled {
            cursor: default;
            opacity: 0.7;
        }

        .code-editor-shell.empty .code-highlight code::before {
            color: #94a3b8;
            content: "Select a file from Directories";
        }

        .dark .code-editor-shell.empty .code-highlight code::before {
            color: #526174;
        }

        .token-comment {
            color: #64748b;
        }

        .dark .token-comment {
            color: #6b7280;
        }

        .token-keyword {
            color: #7c3aed;
        }

        .dark .token-keyword {
            color: #c084fc;
        }

        .token-string {
            color: #15803d;
        }

        .dark .token-string {
            color: #86efac;
        }

        .token-number {
            color: #c2410c;
        }

        .dark .token-number {
            color: #fdba74;
        }

        .token-variable {
            color: #0369a1;
        }

        .dark .token-variable {
            color: #7dd3fc;
        }

        .token-function {
            color: #9333ea;
        }

        .dark .token-function {
            color: #d8b4fe;
        }

        .token-tag {
            color: #be123c;
        }

        .dark .token-tag {
            color: #fb7185;
        }

        .token-attribute {
            color: #b45309;
        }

        .dark .token-attribute {
            color: #facc15;
        }

        .token-punctuation {
            color: #475569;
        }

        .dark .token-punctuation {
            color: #94a3b8;
        }

        .fullscreen-button {
            margin-left: 0.5rem;
            margin-right: 0.25rem;
        }

        @media (max-width: 760px) {
            .studio-sidebar {
                width: 78%;
            }

            .studio-main {
                width: 22%;
            }

            .studio-shell.sidebar-closed .studio-main {
                width: 100%;
            }

            .header-left {
                min-width: 0;
            }
        }
    </style>
    @include('appyhp::partials.ai-styles')
</head>
<body>
    <div id="appyhp-studio" class="studio-shell" data-active-panel="workflows">
        <div class="studio-layout">
            <aside class="studio-sidebar">
                <div class="studio-sidebar-header">
                    <a href="{{ route('appyhp.studio') }}" class="studio-brand" aria-label="AppyHP">
                        <span class="studio-brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                <path d="M467.8 98.4C479.8 93.4 493.5 96.2 502.7 105.3L566.7 169.3C572.7 175.3 576.1 183.4 576.1 191.9C576.1 200.4 572.7 208.5 566.7 214.5L502.7 278.5C493.5 287.7 479.8 290.4 467.8 285.4C455.8 280.4 448 268.9 448 256L448 224L416 224C405.9 224 396.4 228.7 390.4 236.8L358 280L318 226.7L339.2 198.4C357.3 174.2 385.8 160 416 160L448 160L448 128C448 115.1 455.8 103.4 467.8 98.4zM218 360L258 413.3L236.8 441.6C218.7 465.8 190.2 480 160 480L96 480C78.3 480 64 465.7 64 448C64 430.3 78.3 416 96 416L160 416C170.1 416 179.6 411.3 185.6 403.2L218 360zM502.6 534.6C493.4 543.8 479.7 546.5 467.7 541.5C455.7 536.5 448 524.9 448 512L448 480L416 480C385.8 480 357.3 465.8 339.2 441.6L185.6 236.8C179.6 228.7 170.1 224 160 224L96 224C78.3 224 64 209.7 64 192C64 174.3 78.3 160 96 160L160 160C190.2 160 218.7 174.2 236.8 198.4L390.4 403.2C396.4 411.3 405.9 416 416 416L448 416L448 384C448 371.1 455.8 359.4 467.8 354.4C479.8 349.4 493.5 352.2 502.7 361.3L566.7 425.3C572.7 431.3 576.1 439.4 576.1 447.9C576.1 456.4 572.7 464.5 566.7 470.5L502.7 534.5z"/>
                            </svg>
                        </span>
                        AppyHP
                    </a>
                    <button type="button" class="icon-button sidebar-toggle-button" aria-label="angle">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                            <path d="M105.4 297.4C92.9 309.9 92.9 330.2 105.4 342.7L265.4 502.7C277.9 515.2 298.2 515.2 310.7 502.7C323.2 490.2 323.2 469.9 310.7 457.4L173.3 320L310.6 182.6C323.1 170.1 323.1 149.8 310.6 137.3C298.1 124.8 277.8 124.8 265.3 137.3L105.3 297.3zM457.4 137.4L297.4 297.4C284.9 309.9 284.9 330.2 297.4 342.7L457.4 502.7C469.9 515.2 490.2 515.2 502.7 502.7C515.2 490.2 515.2 469.9 502.7 457.4L365.3 320L502.6 182.6C515.1 170.1 515.1 149.8 502.6 137.3C490.1 124.8 469.8 124.8 457.3 137.3z"/>
                        </svg>
                    </button>
                </div>

                <div class="sidebar-content">
                    <div class="sidebar-lower" aria-label="Studio sidebar lower panel">
                        <section class="sidebar-panel active" data-sidebar-panel="workflows" role="tabpanel">
                            <div class="workflow-services">
                                <header class="workflow-services-header">
                                    <span>
                                        <span class="workflow-title">Services</span>
                                        <span class="workflow-meta">Laravel workflow configurations</span>
                                    </span>
                                    <span class="workflow-services-actions">
                                        <input type="file" data-workflow-upload accept=".json,application/json" hidden>
                                        <button type="button" class="workflow-action icon-only" data-workflow-upload-button aria-label="Upload workflow" title="Upload workflow">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 20h14"/></svg>
                                        </button>
                                        <button type="button" class="workflow-action" data-workflow-new>
                                            <span aria-hidden="true">+</span>
                                            <span>New</span>
                                        </button>
                                    </span>
                                </header>
                                <div class="workflow-list" data-workflow-list></div>
                                <div class="directory-status" data-workflow-status></div>
                            </div>
                        </section>
                        <section class="sidebar-panel" data-sidebar-panel="directories" role="tabpanel">
                            <div class="directory-panel">
                                <div class="directory-tree" data-directory-tree></div>
                                <div class="directory-status" data-directory-status></div>
                            </div>
                        </section>
                    </div>
                </div>
            </aside>

            <main class="studio-main">
                <header class="studio-header">
                    <div class="header-left">
                        <span class="collapsed-brand" hidden>
                            <a href="{{ route('appyhp.studio') }}" class="studio-brand" aria-label="AppyHP">
                                <span class="studio-brand-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                        <path d="M467.8 98.4C479.8 93.4 493.5 96.2 502.7 105.3L566.7 169.3C572.7 175.3 576.1 183.4 576.1 191.9C576.1 200.4 572.7 208.5 566.7 214.5L502.7 278.5C493.5 287.7 479.8 290.4 467.8 285.4C455.8 280.4 448 268.9 448 256L448 224L416 224C405.9 224 396.4 228.7 390.4 236.8L358 280L318 226.7L339.2 198.4C357.3 174.2 385.8 160 416 160L448 160L448 128C448 115.1 455.8 103.4 467.8 98.4zM218 360L258 413.3L236.8 441.6C218.7 465.8 190.2 480 160 480L96 480C78.3 480 64 465.7 64 448C64 430.3 78.3 416 96 416L160 416C170.1 416 179.6 411.3 185.6 403.2L218 360zM502.6 534.6C493.4 543.8 479.7 546.5 467.7 541.5C455.7 536.5 448 524.9 448 512L448 480L416 480C385.8 480 357.3 465.8 339.2 441.6L185.6 236.8C179.6 228.7 170.1 224 160 224L96 224C78.3 224 64 209.7 64 192C64 174.3 78.3 160 96 160L160 160C190.2 160 218.7 174.2 236.8 198.4L390.4 403.2C396.4 411.3 405.9 416 416 416L448 416L448 384C448 371.1 455.8 359.4 467.8 354.4C479.8 349.4 493.5 352.2 502.7 361.3L566.7 425.3C572.7 431.3 576.1 439.4 576.1 447.9C576.1 456.4 572.7 464.5 566.7 470.5L502.7 534.5z"/>
                                    </svg>
                                </span>
                                AppyHP
                            </a>
                        </span>
                        <span class="sidebar-toggle-target"></span>
                        <button type="button" class="manual-save-button" data-manual-save>
                            <span data-save-label>Save</span>
                            <span class="dirty-dot" aria-hidden="true"></span>
                        </button>
                        <label class="autosave-control">
                            <input type="checkbox" data-autosave>
                            <span>Auto-save</span>
                        </label>
                    </div>

                    <div class="header-center">
                        <div class="panel-switcher">
                            <button type="button" class="history-button" data-undo disabled aria-label="Undo panel change">
                                <svg xmlns="http://www.w3.org/2000/svg" height="18px" width="18px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                    <path d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z"/>
                                </svg>
                            </button>
                            <button type="button" class="panel-switcher-button workflows" data-panel-switcher aria-label="Switch studio panel">
                                <span class="panel-switcher-label workflows" data-panel-switcher-label>Workflows</span>
                                <span class="panel-switcher-thumb"></span>
                            </button>
                            <button type="button" class="history-button" data-redo disabled aria-label="Redo panel change">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" width="20px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                    <path d="M552 256L408 256C398.3 256 389.5 250.2 385.8 241.2C382.1 232.2 384.1 221.9 391 215L437.7 168.3C362.4 109.7 253.4 115 184.2 184.2C109.2 259.2 109.2 380.7 184.2 455.7C259.2 530.7 380.7 530.7 455.7 455.7C463.9 447.5 471.2 438.8 477.6 429.6C487.7 415.1 507.7 411.6 522.2 421.7C536.7 431.8 540.2 451.8 530.1 466.3C521.6 478.5 511.9 490.1 501 501C401 601 238.9 601 139 501C39.1 401 39 239 139 139C233.3 44.7 382.7 39.4 483.3 122.8L535 71C541.9 64.1 552.2 62.1 561.2 65.8C570.2 69.5 576 78.3 576 88L576 232C576 245.3 565.3 256 552 256z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="header-right">
                        <button type="button" class="icon-button ai-settings-button" data-ai-settings aria-label="AI settings" title="AI settings"></button>
                        <button type="button" class="theme-button" aria-label="Toggle Theme">
                            <span class="theme-light-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="18px" width="18px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                    <path d="M424.5 355.1C449 329.2 464 294.4 464 256C464 176.5 399.5 112 320 112C240.5 112 176 176.5 176 256C176 294.4 191 329.2 215.5 355.1C236.8 377.5 260.4 409.1 268.8 448L371.2 448C379.6 409 403.2 377.5 424.5 355.1zM459.3 388.1C435.7 413 416 443.4 416 477.7L416 496C416 540.2 380.2 576 336 576L304 576C259.8 576 224 540.2 224 496L224 477.7C224 443.4 204.3 413 180.7 388.1C148 353.7 128 307.2 128 256C128 150 214 64 320 64C426 64 512 150 512 256C512 307.2 492 353.7 459.3 388.1zM272 248C272 261.3 261.3 272 248 272C234.7 272 224 261.3 224 248C224 199.4 263.4 160 312 160C325.3 160 336 170.7 336 184C336 197.3 325.3 208 312 208C289.9 208 272 225.9 272 248z"/>
                                </svg>
                            </span>
                            <span class="theme-dark-icon" hidden>
                                <svg xmlns="http://www.w3.org/2000/svg" height="18px" width="18px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                    <path d="M420.9 448C428.2 425.7 442.8 405.5 459.3 388.1C492 353.7 512 307.2 512 256C512 150 426 64 320 64C214 64 128 150 128 256C128 307.2 148 353.7 180.7 388.1C197.2 405.5 211.9 425.7 219.1 448L420.8 448zM416 496L224 496L224 512C224 556.2 259.8 592 304 592L336 592C380.2 592 416 556.2 416 512L416 496zM312 176C272.2 176 240 208.2 240 248C240 261.3 229.3 272 216 272C202.7 272 192 261.3 192 248C192 181.7 245.7 128 312 128C325.3 128 336 138.7 336 152C336 165.3 325.3 176 312 176z"/>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="icon-button fullscreen-button" aria-label="Toggle fullscreen">
                            <span class="fullscreen-enter-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="18px" width="18px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                    <path d="M128 96C110.3 96 96 110.3 96 128L96 224C96 241.7 110.3 256 128 256C145.7 256 160 241.7 160 224L160 160L224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L128 96zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 512C96 529.7 110.3 544 128 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480L160 416zM416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160L480 224C480 241.7 494.3 256 512 256C529.7 256 544 241.7 544 224L544 128C544 110.3 529.7 96 512 96L416 96zM544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480L416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L512 544C529.7 544 544 529.7 544 512L544 416z"/>
                                </svg>
                            </span>
                            <span class="fullscreen-exit-icon" hidden>
                                <svg xmlns="http://www.w3.org/2000/svg" height="18px" width="18px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true">
                                    <path d="M256 128C256 110.3 241.7 96 224 96C206.3 96 192 110.3 192 128L192 192L128 192C110.3 192 96 206.3 96 224C96 241.7 110.3 256 128 256L224 256C241.7 256 256 241.7 256 224L256 128zM128 384C110.3 384 96 398.3 96 416C96 433.7 110.3 448 128 448L192 448L192 512C192 529.7 206.3 544 224 544C241.7 544 256 529.7 256 512L256 416C256 398.3 241.7 384 224 384L128 384zM448 128C448 110.3 433.7 96 416 96C398.3 96 384 110.3 384 128L384 224C384 241.7 398.3 256 416 256L512 256C529.7 256 544 241.7 544 224C544 206.3 529.7 192 512 192L448 192L448 128zM416 384C398.3 384 384 398.3 384 416L384 512C384 529.7 398.3 544 416 544C433.7 544 448 529.7 448 512L448 448L512 448C529.7 448 544 433.7 544 416C544 398.3 529.7 384 512 384L416 384z"/>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="icon-button love-button" data-love-open aria-label="Support AppyHP" title="Support AppyHP">❤</button>
                    </div>
                </header>

                <div class="canvas-container">
                    <section class="studio-panel active" data-studio-panel="workflows" role="tabpanel">
                        <div class="workflow-editor">
                            <header class="workflow-editor-header">
                                <span class="workflow-editor-title">
                                    <span class="workflow-title" data-active-workflow-name>Service Workflow</span>
                                    <span class="workflow-meta" data-active-workflow-meta>No workflow selected.</span>
                                </span>
                                <span class="header-right">
                                    <select class="workflow-frontend" data-workflow-frontend aria-label="Workflow frontend">
                                        <option value="blade">Blade</option>
                                        <option value="vue">Inertia / Vue</option>
                                        <option value="react">Inertia / React</option>
                                        <option value="svelte">Inertia / Svelte</option>
                                    </select>
                                    <button type="button" class="workflow-action" data-workflow-auto>Auto</button>
                                    <a class="workflow-action" href="/appyhp/studio/web/" target="_blank" rel="noopener noreferrer" aria-label="Open AppyHP documentation" title="Open AppyHP documentation">Web ↗</a>
                                </span>
                            </header>
                            <div class="workflow-toolbar">
                                <button type="button" class="workflow-action icon-only module-palette-scroll" data-module-scroll-left aria-label="Scroll modules left" title="Scroll modules left">&lt;</button>
                                <div class="module-palette" data-module-palette></div>
                                <button type="button" class="workflow-action icon-only module-palette-scroll" data-module-scroll-right aria-label="Scroll modules right" title="Scroll modules right">&gt;</button>
                            </div>
                            <div class="workflow-workspace">
                              <div class="workflow-canvas-shell workflow-stage-grid" data-workflow-canvas-shell>
                                <div class="workflow-canvas-tools">
                                    <div class="workflow-zoom">
                                        <span class="workflow-meta">Zoom</span>
                                        <span class="workflow-zoom-box">
                                            <button type="button" data-zoom-out aria-label="Zoom out">-</button>
                                            <span data-zoom-value>100%</span>
                                            <button type="button" data-zoom-in aria-label="Zoom in">+</button>
                                        </span>
                                        <button type="button" class="workflow-action" data-zoom-reset>Reset</button>
                                    </div>
                                </div>
                                <div class="workflow-canvas-empty" data-workflow-empty>Select a workflow from the list to start editing.</div>
                                <div class="workflow-stage" data-workflow-stage hidden>
                                    <svg class="workflow-edge-layer" data-workflow-edge-layer width="1100" height="720" aria-hidden="true"></svg>
                                    <div class="workflow-node-layer" data-workflow-node-layer></div>
                                    <div class="edge-settings-popover" data-edge-settings></div>
                                </div>
                              </div>
                              <aside class="workflow-config-panel" data-module-config-panel aria-label="Module configuration"></aside>
                            </div>
                        </div>
                    </section>
                    <section class="studio-panel" data-studio-panel="directories" role="tabpanel">
                        <div class="directory-editor-panel">
                            <div class="editor-toolbar">
                                <div class="editor-path" data-editor-path></div>
                                <input class="editor-name" data-editor-name aria-label="File name" disabled>
                                <button type="button" class="workflow-action icon-only" data-editor-info aria-label="File information" title="File information">i</button>
                                <button type="button" class="workflow-action icon-only" data-editor-notes aria-label="File notes" title="File notes">✎</button>
                                <a class="workflow-action" href="/appyhp/studio/web/" target="_blank" rel="noopener noreferrer" aria-label="Open AppyHP documentation" title="Open AppyHP documentation">Web ↗</a>
                            </div>
                            <div class="code-editor-shell empty" data-code-editor-shell>
                                <div class="code-gutter" aria-hidden="true">
                                    <div class="code-gutter-lines" data-code-gutter-lines>1</div>
                                </div>
                                <pre class="code-highlight" data-code-highlight aria-hidden="true"><code></code></pre>
                                <textarea class="file-editor" data-file-editor spellcheck="false" wrap="off" disabled></textarea>
                            </div>
                            <div class="editor-status" data-editor-status></div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <div class="workflow-json-modal" data-workflow-json-modal aria-hidden="true">
        <div class="workflow-json-card" role="dialog" aria-modal="true" aria-label="Workflow JSON">
            <header>
                <strong data-workflow-json-title>Workflow JSON</strong>
                <div class="workflow-json-actions">
                    <button type="button" class="workflow-action" data-workflow-json-download>Download</button>
                    <button type="button" class="workflow-action" data-workflow-json-close>Close</button>
                </div>
            </header>
            <pre data-workflow-json-output>{}</pre>
        </div>
    </div>

    <div class="studio-modal" data-workflow-settings-modal aria-hidden="true">
        <div class="studio-modal-card" role="dialog" aria-modal="true" aria-labelledby="workflow-settings-title">
            <div class="studio-modal-header"><strong id="workflow-settings-title">Service settings</strong><button type="button" class="workflow-action" data-workflow-settings-close>Close</button></div>
            <div class="studio-modal-body">
                <div class="module-config-field"><label for="workflow-settings-name">Workflow name</label><input type="text" id="workflow-settings-name" data-workflow-settings-name maxlength="200" autocomplete="off"></div>
                <div class="module-config-field"><label for="workflow-settings-description">Description</label><textarea id="workflow-settings-description" data-workflow-settings-description maxlength="2000"></textarea></div>
            </div>
            <div class="studio-modal-actions"><span class="editor-status" data-workflow-settings-status></span><button type="button" class="workflow-action" data-workflow-settings-cancel>Cancel</button><button type="button" class="workflow-action ai-primary" data-workflow-settings-save>Save settings</button></div>
        </div>
    </div>

    <div class="studio-modal" data-workflow-delete-modal aria-hidden="true">
        <div class="studio-modal-card" role="dialog" aria-modal="true" aria-labelledby="workflow-delete-title">
            <div class="studio-modal-header"><strong id="workflow-delete-title">Delete service</strong><button type="button" class="workflow-action" data-workflow-delete-close>Close</button></div>
            <div class="studio-modal-body"><p data-workflow-delete-message>Are you sure you want to delete this service?</p></div>
            <div class="studio-modal-actions"><button type="button" class="workflow-action" data-workflow-delete-cancel>Cancel</button><button type="button" class="workflow-action directory-delete-action" data-workflow-delete-confirm>Delete service</button></div>
        </div>
    </div>

    <div class="directory-context-menu" data-directory-context-menu role="dialog" aria-label="Directory actions">
        <div class="directory-context-title" data-directory-context-title></div>
        <div class="directory-context-create" data-directory-context-create>
            <input class="directory-input" type="text" data-directory-context-name placeholder="Name">
            <button type="button" class="directory-action" data-directory-context-create-file>File</button>
            <button type="button" class="directory-action" data-directory-context-create-folder>Folder</button>
        </div>
        <div class="directory-context-actions">
            <button type="button" class="directory-action" data-directory-context-cut>Cut</button>
            <button type="button" class="directory-action" data-directory-context-copy>Copy</button>
            <button type="button" class="directory-action" data-directory-context-paste>Paste</button>
            <button type="button" class="directory-action directory-delete-action" data-directory-context-delete>Delete</button>
        </div>
    </div>

    <div class="studio-modal" data-file-info-modal aria-hidden="true">
        <div class="studio-modal-card" role="dialog" aria-modal="true" aria-labelledby="file-info-title">
            <div class="studio-modal-header"><strong id="file-info-title">File information</strong><button type="button" class="workflow-action" data-file-info-close>Close</button></div>
            <div class="studio-modal-body"><div data-file-info-body></div><button type="button" class="workflow-action ai-primary" data-file-info-ai>Ask AI</button><div class="studio-modal-ai-result" data-file-info-ai-result></div></div>
        </div>
    </div>
    <div class="studio-modal" data-file-notes-modal aria-hidden="true">
        <div class="studio-modal-card" role="dialog" aria-modal="true" aria-labelledby="file-notes-title">
            <div class="studio-modal-header"><strong id="file-notes-title">File notes</strong><button type="button" class="workflow-action" data-file-notes-close>Close</button></div>
            <div class="studio-modal-body"><textarea data-file-notes-input placeholder="Describe what this file does..."></textarea></div>
            <div class="studio-modal-actions"><span class="editor-status" data-file-notes-status></span><button type="button" class="workflow-action ai-primary" data-file-notes-save>Save notes</button></div>
        </div>
    </div>

    @include('appyhp::partials.ai-settings')
    @include('appyhp::partials.love-modal')

    <script>
        (function () {
            var shell = document.getElementById('appyhp-studio');
            var toggleButton = shell.querySelector('.sidebar-toggle-button');
            var sidebarHeader = shell.querySelector('.studio-sidebar-header');
            var toggleTarget = shell.querySelector('.sidebar-toggle-target');
            var collapsedBrand = shell.querySelector('.collapsed-brand');
            var manualSaveButton = shell.querySelector('[data-manual-save]');
            var saveLabel = shell.querySelector('[data-save-label]');
            var autosaveInput = shell.querySelector('[data-autosave]');
            var undoButton = shell.querySelector('[data-undo]');
            var redoButton = shell.querySelector('[data-redo]');
            var panelSwitcher = shell.querySelector('[data-panel-switcher]');
            var panelSwitcherLabel = shell.querySelector('[data-panel-switcher-label]');
            var themeButton = shell.querySelector('.theme-button');
            var themeLightIcon = shell.querySelector('.theme-light-icon');
            var themeDarkIcon = shell.querySelector('.theme-dark-icon');
            var fullscreenButton = shell.querySelector('.fullscreen-button');
            var fullscreenEnterIcon = shell.querySelector('.fullscreen-enter-icon');
            var fullscreenExitIcon = shell.querySelector('.fullscreen-exit-icon');
            var bodyPanels = Array.prototype.slice.call(shell.querySelectorAll('.studio-panel'));
            var sidebarPanels = Array.prototype.slice.call(shell.querySelectorAll('.sidebar-panel'));
            var directoryTree = shell.querySelector('[data-directory-tree]');
            var directoryStatus = shell.querySelector('[data-directory-status]');
            var editorPath = shell.querySelector('[data-editor-path]');
            var editorName = shell.querySelector('[data-editor-name]');
            var editorInfoButton = shell.querySelector('[data-editor-info]');
            var editorNotesButton = shell.querySelector('[data-editor-notes]');
            var editorStatus = shell.querySelector('[data-editor-status]');
            var codeEditorShell = shell.querySelector('[data-code-editor-shell]');
            var codeHighlight = shell.querySelector('[data-code-highlight] code');
            var codeHighlightLayer = shell.querySelector('[data-code-highlight]');
            var codeGutterLines = shell.querySelector('[data-code-gutter-lines]');
            var fileEditor = shell.querySelector('[data-file-editor]');
            var panelLabels = {
                workflows: 'Workflows',
                directories: 'Directories'
            };
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var closeIcon = toggleButton.innerHTML;
            var openIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true"><path d="M535.1 342.6C547.6 330.1 547.6 309.8 535.1 297.3L375.1 137.3C362.6 124.8 342.3 124.8 329.8 137.3C317.3 149.8 317.3 170.1 329.8 182.6L467.2 320L329.9 457.4C317.4 469.9 317.4 490.2 329.9 502.7C342.4 515.2 362.7 515.2 375.2 502.7L535.2 342.7zM183.1 502.6L343.1 342.6C355.6 330.1 355.6 309.8 343.1 297.3L183.1 137.3C170.6 124.8 150.3 124.8 137.8 137.3C125.3 149.8 125.3 170.1 137.8 182.6L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7z"/></svg>';
            var sidebarVisible = true;
            var selectedDirectoryPath = '';
            var selectedFilePath = '';
            var selectedLanguage = 'plain';
            var fileDirty = false;
            var fileAutosaveTimer = null;
            var directoriesLoaded = false;
            var directoryContainers = {};
            var directoryToggles = {};
            var directoryEntries = {};
            var fileEntries = {};
            var workflowList = shell.querySelector('[data-workflow-list]');
            var workflowStatus = shell.querySelector('[data-workflow-status]');
            var workflowNewButton = shell.querySelector('[data-workflow-new]');
            var workflowUploadButton = shell.querySelector('[data-workflow-upload-button]');
            var workflowUploadInput = shell.querySelector('[data-workflow-upload]');
            var workflowAutoButton = shell.querySelector('[data-workflow-auto]');
            var modulePalette = shell.querySelector('[data-module-palette]');
            var moduleScrollLeftButton = shell.querySelector('[data-module-scroll-left]');
            var moduleScrollRightButton = shell.querySelector('[data-module-scroll-right]');
            var workflowCanvasShell = shell.querySelector('[data-workflow-canvas-shell]');
            var workflowStage = shell.querySelector('[data-workflow-stage]');
            var workflowEdgeLayer = shell.querySelector('[data-workflow-edge-layer]');
            var workflowNodeLayer = shell.querySelector('[data-workflow-node-layer]');
            var workflowEmpty = shell.querySelector('[data-workflow-empty]');
            var activeWorkflowName = shell.querySelector('[data-active-workflow-name]');
            var activeWorkflowMeta = shell.querySelector('[data-active-workflow-meta]');
            var moduleConfigPanel = shell.querySelector('[data-module-config-panel]');
            var edgeSettingsPopover = shell.querySelector('[data-edge-settings]');
            var zoomOutButton = shell.querySelector('[data-zoom-out]');
            var zoomInButton = shell.querySelector('[data-zoom-in]');
            var zoomResetButton = shell.querySelector('[data-zoom-reset]');
            var zoomValue = shell.querySelector('[data-zoom-value]');
            var workflowJsonModal = document.querySelector('[data-workflow-json-modal]');
            var workflowJsonTitle = document.querySelector('[data-workflow-json-title]');
            var workflowJsonOutput = document.querySelector('[data-workflow-json-output]');
            var workflowJsonClose = document.querySelector('[data-workflow-json-close]');
            var workflowJsonDownload = document.querySelector('[data-workflow-json-download]');
            var workflowSettingsModal = document.querySelector('[data-workflow-settings-modal]');
            var workflowSettingsName = document.querySelector('[data-workflow-settings-name]');
            var workflowSettingsDescription = document.querySelector('[data-workflow-settings-description]');
            var workflowSettingsStatus = document.querySelector('[data-workflow-settings-status]');
            var workflowDeleteModal = document.querySelector('[data-workflow-delete-modal]');
            var workflowDeleteMessage = document.querySelector('[data-workflow-delete-message]');
            var directoryContextMenu = document.querySelector('[data-directory-context-menu]');
            var directoryContextTitle = document.querySelector('[data-directory-context-title]');
            var directoryContextCreate = document.querySelector('[data-directory-context-create]');
            var directoryContextName = document.querySelector('[data-directory-context-name]');
            var directoryContextCreateFile = document.querySelector('[data-directory-context-create-file]');
            var directoryContextCreateFolder = document.querySelector('[data-directory-context-create-folder]');
            var directoryContextCut = document.querySelector('[data-directory-context-cut]');
            var directoryContextCopy = document.querySelector('[data-directory-context-copy]');
            var directoryContextPaste = document.querySelector('[data-directory-context-paste]');
            var directoryContextDelete = document.querySelector('[data-directory-context-delete]');
            var workflowDragFrame = null;
            var directoryContextItem = null;
            var directoryClipboardItem = null;
            var fileInfoModal = document.querySelector('[data-file-info-modal]');
            var fileInfoBody = document.querySelector('[data-file-info-body]');
            var fileInfoAiButton = document.querySelector('[data-file-info-ai]');
            var fileInfoAiResult = document.querySelector('[data-file-info-ai-result]');
            var fileNotesModal = document.querySelector('[data-file-notes-modal]');
            var fileNotesInput = document.querySelector('[data-file-notes-input]');
            var fileNotesStatus = document.querySelector('[data-file-notes-status]');

            function setSidebarVisible(visible) {
                sidebarVisible = visible;
                shell.classList.toggle('sidebar-closed', !visible);
                collapsedBrand.hidden = visible;
                toggleButton.innerHTML = visible ? closeIcon : openIcon;

                if (visible && toggleButton.parentElement !== sidebarHeader) {
                    sidebarHeader.appendChild(toggleButton);
                }

                if (!visible && toggleButton.parentElement !== toggleTarget) {
                    toggleTarget.appendChild(toggleButton);
                }
            }

            function setTheme(theme) {
                document.documentElement.classList.toggle('dark', theme === 'dark');
                localStorage.setItem('theme', theme);
                themeLightIcon.hidden = theme !== 'light';
                themeDarkIcon.hidden = theme === 'light';
            }

            function setPanel(panelName) {
                if (!panelLabels[panelName]) {
                    panelName = 'workflows';
                }

                shell.dataset.activePanel = panelName;

                bodyPanels.forEach(function (panel) {
                    panel.classList.toggle('active', panel.dataset.studioPanel === panelName);
                    panel.setAttribute('aria-hidden', panel.dataset.studioPanel === panelName ? 'false' : 'true');
                });

                sidebarPanels.forEach(function (panel) {
                    panel.classList.toggle('active', panel.dataset.sidebarPanel === panelName);
                    panel.setAttribute('aria-hidden', panel.dataset.sidebarPanel === panelName ? 'false' : 'true');
                });

                panelSwitcher.classList.toggle('workflows', panelName === 'workflows');
                panelSwitcher.classList.toggle('directories', panelName === 'directories');
                panelSwitcherLabel.className = 'panel-switcher-label ' + panelName;
                panelSwitcherLabel.textContent = panelLabels[panelName] || panelName;

                if (panelName === 'directories') {
                    cancelAiGeneration();
                    loadDirectories();
                }
            }

            function setupUrl() {
                return new URL('/appyhp/api/setup', window.location.origin);
            }

            function setDirectoryStatus(message) {
                directoryStatus.textContent = message || '';
            }

            function setEditorStatus(message) {
                editorStatus.textContent = message || '';
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function token(className, value) {
                return '<span class="' + className + '">' + escapeHtml(value) + '</span>';
            }

            function detectLanguage(path) {
                var lowerPath = String(path || '').toLowerCase();

                if (/\.blade\.php$|\.php$/.test(lowerPath)) {
                    return 'php';
                }

                if (/\.(mjs|cjs|js|jsx|ts|tsx|json)$/.test(lowerPath)) {
                    return 'js';
                }

                if (/\.(html|htm|xml|svg)$/.test(lowerPath)) {
                    return 'html';
                }

                return 'plain';
            }

            function highlightCode(source, language) {
                if (language === 'html') {
                    return highlightMarkup(source);
                }

                if (language === 'php' || language === 'js') {
                    return highlightScript(source, language);
                }

                return escapeHtml(source);
            }

            function replaceWithEscapedGaps(source, pattern, callback) {
                var output = '';
                var lastIndex = 0;

                source.replace(pattern, function () {
                    var match = arguments[0];
                    var offset = arguments[arguments.length - 2];

                    output += escapeHtml(source.slice(lastIndex, offset));
                    output += callback(match);
                    lastIndex = offset + match.length;

                    return match;
                });

                output += escapeHtml(source.slice(lastIndex));

                return output;
            }

            function highlightScript(source, language) {
                var phpKeywords = 'abstract|and|array|as|break|callable|case|catch|class|clone|const|continue|declare|default|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|enum|eval|exit|extends|final|finally|fn|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|match|namespace|new|or|print|private|protected|public|readonly|require|require_once|return|static|switch|throw|trait|try|unset|use|var|while|xor|yield|true|false|null';
                var jsKeywords = 'await|async|break|case|catch|class|const|continue|debugger|default|delete|do|else|export|extends|false|finally|for|from|function|get|if|import|in|instanceof|let|new|null|of|return|set|static|super|switch|this|throw|true|try|typeof|undefined|var|void|while|with|yield';
                var keywords = language === 'php' ? phpKeywords : jsKeywords;
                var pattern = new RegExp('(/\\*[\\s\\S]*?\\*/|//[^\\n]*|#[^\\n]*|`(?:\\\\.|[^`\\\\])*`|"(?:\\\\.|[^"\\\\])*"|\\\'(?:\\\\.|[^\\\'\\\\])*\\\'|\\$[A-Za-z_][A-Za-z0-9_]*|\\b(?:' + keywords + ')\\b|\\b[A-Za-z_][A-Za-z0-9_]*(?=\\s*\\()|\\b\\d+(?:\\.\\d+)?\\b|[{}()[\\].,;:?]|[+\\-*\\/%=!<>|&~^]+)', 'g');

                return replaceWithEscapedGaps(source, pattern, function (match) {
                    if (/^\/\*|^\/\/|^#/.test(match)) {
                        return token('token-comment', match);
                    }

                    if (/^["'`]/.test(match)) {
                        return token('token-string', match);
                    }

                    if (/^\$/.test(match)) {
                        return token('token-variable', match);
                    }

                    if (new RegExp('^(' + keywords + ')$').test(match)) {
                        return token('token-keyword', match);
                    }

                    if (/^\d/.test(match)) {
                        return token('token-number', match);
                    }

                    if (/^[A-Za-z_]/.test(match)) {
                        return token('token-function', match);
                    }

                    return token('token-punctuation', match);
                });
            }

            function highlightMarkup(source) {
                return replaceWithEscapedGaps(source, /(<!--[\s\S]*?-->|<!doctype[^>]*>|<\/?[A-Za-z][^>]*>)/gi, function (match) {
                    if (/^<!--/.test(match)) {
                        return token('token-comment', match);
                    }

                    return escapeHtml(match).replace(/(&lt;\/?)([A-Za-z][A-Za-z0-9:-]*)([\s\S]*?)(&gt;)/, function (full, open, name, attrs, close) {
                        return '<span class="token-punctuation">' + open + '</span>' +
                            '<span class="token-tag">' + name + '</span>' +
                            highlightAttributes(attrs) +
                            '<span class="token-punctuation">' + close + '</span>';
                    });
                });
            }

            function highlightAttributes(attrs) {
                return attrs.replace(/([A-Za-z_:][-A-Za-z0-9_:.]*)(=)(&quot;.*?&quot;|&#039;.*?&#039;|[^\s&]+)?/g, function (full, name, equals, value) {
                    return '<span class="token-attribute">' + name + '</span>' +
                        '<span class="token-punctuation">' + equals + '</span>' +
                        (value ? '<span class="token-string">' + value + '</span>' : '');
                });
            }

            function updateCodeEditor() {
                var value = fileEditor.value || '';
                var lineCount = Math.max(1, value.split('\n').length);
                var lines = [];

                for (var index = 1; index <= lineCount; index += 1) {
                    lines.push(index);
                }

                codeGutterLines.textContent = lines.join('\n');
                codeHighlight.innerHTML = highlightCode(value, selectedLanguage);
                codeEditorShell.classList.toggle('empty', !selectedFilePath);
                syncCodeEditorScroll();
            }

            function syncCodeEditorScroll() {
                codeHighlightLayer.scrollTop = fileEditor.scrollTop;
                codeHighlightLayer.scrollLeft = fileEditor.scrollLeft;
                codeGutterLines.style.transform = 'translateY(-' + fileEditor.scrollTop + 'px)';
            }

            function insertAtCursor(text) {
                var start = fileEditor.selectionStart;
                var end = fileEditor.selectionEnd;
                var value = fileEditor.value;

                fileEditor.value = value.slice(0, start) + text + value.slice(end);
                fileEditor.selectionStart = start + text.length;
                fileEditor.selectionEnd = start + text.length;
            }

            function iconSvg(type, expanded) {
                if (type === 'directory') {
                    if (expanded) {
                        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 6.75A2.75 2.75 0 0 1 5.75 4h4.68c.73 0 1.43.29 1.94.8l1.45 1.45h4.43A2.75 2.75 0 0 1 21 9v.5H8.31a2.75 2.75 0 0 0-2.6 1.86L3.1 19.03A2.73 2.73 0 0 1 3 18.25V6.75Z"/><path d="M7.13 11.85A1.25 1.25 0 0 1 8.31 11h11.44a1.25 1.25 0 0 1 1.18 1.66l-2.06 6A2.75 2.75 0 0 1 16.27 20H4.75a1.25 1.25 0 0 1-1.18-1.66l3.56-6.49Z"/></svg>';
                    }

                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 6.75A2.75 2.75 0 0 1 5.75 4h4.68c.73 0 1.43.29 1.94.8l1.45 1.45h4.43A2.75 2.75 0 0 1 21 9v8.25A2.75 2.75 0 0 1 18.25 20H5.75A2.75 2.75 0 0 1 3 17.25V6.75Z"/></svg>';
                }

                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.75 2.75A2.75 2.75 0 0 0 4 5.5v13A2.75 2.75 0 0 0 6.75 21.25h10.5A2.75 2.75 0 0 0 20 18.5V8.62c0-.73-.29-1.43-.8-1.94L16.07 3.55a2.75 2.75 0 0 0-1.94-.8H6.75Zm7.5 1.81c.29.04.56.18.77.39l3.13 3.13c.21.21.35.48.39.77h-3.04c-.69 0-1.25-.56-1.25-1.25V4.56Z"/></svg>';
            }

            function setEntryContent(entry, item, expanded) {
                var icon = document.createElement('span');
                var name = document.createElement('span');

                icon.className = 'tree-icon ' + (item.type === 'directory' ? 'folder' : 'file');
                icon.innerHTML = iconSvg(item.type, expanded);
                name.className = 'tree-name';
                name.textContent = item.name;

                entry.replaceChildren(icon, name);
            }

            function directoryUrl(endpoint, params) {
                var url = new URL('/appyhp/api/directories' + endpoint, window.location.origin);

                Object.keys(params || {}).forEach(function (key) {
                    if (params[key] !== undefined && params[key] !== null) {
                        url.searchParams.set(key, params[key]);
                    }
                });

                return url;
            }

            function requestJson(url, options) {
                var requestOptions = Object.assign({
                    headers: {
                        Accept: 'application/json'
                    }
                }, options || {});

                requestOptions.headers = Object.assign({
                    Accept: 'application/json'
                }, requestOptions.headers || {});

                if (csrfToken) {
                    requestOptions.headers['X-CSRF-TOKEN'] = csrfToken;
                }

                return fetch(url, Object.assign({
                    headers: {
                        Accept: 'application/json'
                    }
                }, requestOptions)).then(function (response) {
                    return response.text().then(function (text) {
                        var payload = text ? JSON.parse(text) : {};

                        if (!response.ok) {
                            throw new Error(payload.message || 'Request failed.');
                        }

                        return payload;
                    });
                });
            }

            function postJson(url, data, method) {
                return requestJson(url, {
                    method: method || 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
            }

            function createAppyhpStore() {
                var state = {
                    activePanel: 'workflows',
                    autosave: false,
                    saving: false,
                    dirty: false,
                    canUndo: false,
                    canRedo: false
                };
                var subscribers = [];
                var history = [];
                var future = [];

                function snapshot() {
                    return Object.assign({}, state);
                }

                function notify() {
                    state.canUndo = history.length > 0;
                    state.canRedo = future.length > 0;
                    subscribers.forEach(function (subscriber) {
                        subscriber(snapshot());
                    });
                }

                function normalizePanel(panelName) {
                    return panelLabels[panelName] ? panelName : 'workflows';
                }

                function applyPanel(panelName, options) {
                    var nextPanel = normalizePanel(panelName);
                    var shouldRecord = !options || options.record !== false;
                    var shouldSave = !options || options.save !== false;

                    if (nextPanel === state.activePanel) {
                        return;
                    }

                    if (shouldRecord) {
                        history.push(state.activePanel);
                        future = [];
                    }

                    state.activePanel = nextPanel;
                    state.dirty = true;
                    notify();

                    if (shouldSave) {
                        save();
                    }
                }

                function save() {
                    state.saving = true;
                    notify();

                    return postJson(setupUrl(), {
                        activePanel: state.activePanel,
                        autosave: state.autosave
                    }, 'PUT').then(function (setup) {
                        state.activePanel = normalizePanel(setup.activePanel);
                        state.autosave = Boolean(setup.autosave);
                        state.dirty = false;
                        return snapshot();
                    }).finally(function () {
                        state.saving = false;
                        notify();
                    });
                }

                function load() {
                    return requestJson(setupUrl())
                        .then(function (setup) {
                            state.activePanel = normalizePanel(setup.activePanel);
                            state.autosave = Boolean(setup.autosave);
                            state.dirty = false;
                            notify();
                        })
                        .catch(function () {
                            state.activePanel = 'workflows';
                            state.autosave = false;
                            state.dirty = false;
                            notify();
                        });
                }

                function setAutosave(enabled) {
                    state.autosave = Boolean(enabled);
                    state.dirty = true;
                    notify();
                    save();
                }

                function undo() {
                    if (history.length === 0) {
                        return;
                    }

                    future.push(state.activePanel);
                    state.activePanel = normalizePanel(history.pop());
                    state.dirty = true;
                    notify();
                    save();
                }

                function redo() {
                    if (future.length === 0) {
                        return;
                    }

                    history.push(state.activePanel);
                    state.activePanel = normalizePanel(future.pop());
                    state.dirty = true;
                    notify();
                    save();
                }

                function subscribe(subscriber) {
                    subscribers.push(subscriber);
                    subscriber(snapshot());

                    return function () {
                        subscribers = subscribers.filter(function (entry) {
                            return entry !== subscriber;
                        });
                    };
                }

                return {
                    getState: snapshot,
                    load: load,
                    save: save,
                    setActivePanel: applyPanel,
                    setAutosave: setAutosave,
                    undo: undo,
                    redo: redo,
                    subscribe: subscribe
                };
            }

            var appyhpStore = createAppyhpStore();

            var laravelModuleGroups = [
                {
                    label: 'HTTP',
                    items: [
                        { type: 'route', label: 'Route' },
                        { type: 'middleware', label: 'Middleware' },
                        { type: 'controller', label: 'Controller' },
                        { type: 'request', label: 'Request' },
                        { type: 'resource', label: 'Resource' }
                    ]
                },
                {
                    label: 'Data',
                    items: [
                        { type: 'model', label: 'Model' },
                        { type: 'table', label: 'Table' },
                        { type: 'migration', label: 'Migration' },
                        { type: 'factory', label: 'Factory' },
                        { type: 'seeder', label: 'Seeder' }
                    ]
                },
                {
                    label: 'Domain',
                    items: [
                        { type: 'service', label: 'Service' },
                        { type: 'repository', label: 'Repository' },
                        { type: 'policy', label: 'Policy' },
                        { type: 'job', label: 'Job' },
                        { type: 'command', label: 'Command' }
                    ]
                },
                {
                    label: 'Events',
                    items: [
                        { type: 'event', label: 'Event' },
                        { type: 'listener', label: 'Listener' },
                        { type: 'notification', label: 'Notification' },
                        { type: 'mail', label: 'Mail' },
                        { type: 'queue', label: 'Queue' }
                    ]
                },
                {
                    label: 'UI',
                    items: [
                        { type: 'inertia-page', label: 'Inertia Page' },
                        { type: 'inertia-middleware', label: 'Inertia Shared Data' },
                        { type: 'view', label: 'View' },
                        { type: 'component', label: 'Component' },
                        { type: 'auth', label: 'Auth' },
                        { type: 'cache', label: 'Cache' },
                        { type: 'storage', label: 'Storage' }
                    ]
                }
            ];

            var laravelModuleDefinitions = {
                'inertia-page': {
                    title: 'Inertia Page',
                    description: 'Page and props connected to a Laravel controller.',
                    defaults: { page: 'Users/Index', framework: 'inherit', language: 'javascript', props: 'users, filters', layout: '' },
                    fields: [
                        { key: 'page', label: 'Page name', type: 'text' },
                        { key: 'framework', label: 'Framework', type: 'select', options: ['inherit', 'vue', 'react', 'svelte'] },
                        { key: 'language', label: 'Language', type: 'select', options: ['javascript', 'typescript'] },
                        { key: 'props', label: 'Props', type: 'textarea' },
                        { key: 'layout', label: 'Layout', type: 'text' }
                    ]
                },
                'inertia-middleware': {
                    title: 'Inertia Shared Data',
                    description: 'Shared authentication, flash messages and Inertia middleware.',
                    defaults: { class: 'HandleInertiaRequests', rootView: 'app', shared: 'auth.user, flash.success, flash.error' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'rootView', label: 'Root view', type: 'text' },
                        { key: 'shared', label: 'Shared props', type: 'textarea' }
                    ]
                },
                route: {
                    title: 'Route',
                    description: 'HTTP route entry point.',
                    defaults: { routeType: 'web', method: 'GET', uri: '/', middleware: 'web', name: '', action: 'index' },
                    fields: [
                        { key: 'method', label: 'Method', type: 'select', options: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] },
                        { key: 'uri', label: 'URI', type: 'text' },
                        { key: 'middleware', label: 'Middleware', type: 'text' },
                        { key: 'name', label: 'Route name', type: 'text' },
                        { key: 'action', label: 'Controller action', type: 'text' }
                    ]
                },
                middleware: {
                    title: 'Middleware',
                    description: 'Request guard or request mutation layer.',
                    defaults: { class: 'EnsureUserIsActive', alias: 'active.user', appliesTo: 'web' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'alias', label: 'Alias', type: 'text' },
                        { key: 'appliesTo', label: 'Applies to', type: 'text' }
                    ]
                },
                controller: {
                    title: 'Controller',
                    description: 'Coordinates request handling.',
                    defaults: { class: 'UserController', actions: 'index, store, show, update, destroy' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'actions', label: 'Actions', type: 'textarea' }
                    ]
                },
                request: {
                    title: 'Form Request',
                    description: 'Validation and authorization for input.',
                    defaults: { class: 'StoreUserRequest', rules: "name: required|string|max:255\nemail: required|email|unique:users" },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'rules', label: 'Rules', type: 'textarea' }
                    ]
                },
                resource: {
                    title: 'API Resource',
                    description: 'Transforms models for API responses.',
                    defaults: { class: 'UserResource', wraps: 'User', fields: 'id, name, email, created_at' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'wraps', label: 'Wraps', type: 'text' },
                        { key: 'fields', label: 'Fields', type: 'textarea' }
                    ]
                },
                model: {
                    title: 'Model',
                    description: 'Eloquent model and relationships.',
                    defaults: { class: 'User', fillable: 'name, email, password', relationships: 'hasMany:Post' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'fillable', label: 'Fillable', type: 'textarea' },
                        { key: 'relationships', label: 'Relationships', type: 'textarea' }
                    ]
                },
                table: {
                    title: 'Database Table',
                    description: 'Database table and columns.',
                    defaults: { name: 'users', columns: "id:uuid\nname:string\nemail:string:unique\ntimestamps" },
                    fields: [
                        { key: 'name', label: 'Name', type: 'text' },
                        { key: 'columns', label: 'Columns', type: 'textarea' }
                    ]
                },
                migration: {
                    title: 'Migration',
                    description: 'Schema change for a database table.',
                    defaults: { name: 'create_users_table', table: 'users', operation: 'create' },
                    fields: [
                        { key: 'name', label: 'Name', type: 'text' },
                        { key: 'table', label: 'Table', type: 'text' },
                        { key: 'operation', label: 'Operation', type: 'select', options: ['create', 'alter', 'drop'] }
                    ]
                },
                factory: {
                    title: 'Factory',
                    description: 'Test and seed data definition.',
                    defaults: { class: 'UserFactory', model: 'User', states: 'verified, suspended' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'model', label: 'Model', type: 'text' },
                        { key: 'states', label: 'States', type: 'textarea' }
                    ]
                },
                seeder: {
                    title: 'Seeder',
                    description: 'Initial or demo data loader.',
                    defaults: { class: 'UserSeeder', model: 'User', count: '25' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'model', label: 'Model', type: 'text' },
                        { key: 'count', label: 'Count', type: 'text' }
                    ]
                },
                service: {
                    title: 'Service',
                    description: 'Application service for domain logic.',
                    defaults: { class: 'UserService', responsibilities: 'Create users, update profiles, deactivate accounts' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'responsibilities', label: 'Responsibilities', type: 'textarea' }
                    ]
                },
                repository: {
                    title: 'Repository',
                    description: 'Persistence abstraction for data access.',
                    defaults: { class: 'UserRepository', model: 'User', methods: 'findByEmail, activeUsers, search' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'model', label: 'Model', type: 'text' },
                        { key: 'methods', label: 'Methods', type: 'textarea' }
                    ]
                },
                policy: {
                    title: 'Policy',
                    description: 'Authorization rules for a model.',
                    defaults: { class: 'UserPolicy', model: 'User', abilities: 'view, create, update, delete' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'model', label: 'Model', type: 'text' },
                        { key: 'abilities', label: 'Abilities', type: 'textarea' }
                    ]
                },
                job: {
                    title: 'Job',
                    description: 'Queued background work.',
                    defaults: { class: 'SendWelcomeEmail', queue: 'default', retries: '3' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'queue', label: 'Queue', type: 'text' },
                        { key: 'retries', label: 'Retries', type: 'text' }
                    ]
                },
                command: {
                    title: 'Command',
                    description: 'Artisan command.',
                    defaults: { class: 'SyncUsersCommand', signature: 'users:sync {--force}', schedule: 'daily' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'signature', label: 'Signature', type: 'text' },
                        { key: 'schedule', label: 'Schedule', type: 'text' }
                    ]
                },
                event: {
                    title: 'Event',
                    description: 'Domain event emitted by the app.',
                    defaults: { class: 'UserRegistered', payload: 'user_id, email' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'payload', label: 'Payload', type: 'textarea' }
                    ]
                },
                listener: {
                    title: 'Listener',
                    description: 'Handles an application event.',
                    defaults: { class: 'SendUserWelcomeNotification', listensTo: 'UserRegistered', queued: 'yes' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'listensTo', label: 'Listens to', type: 'text' },
                        { key: 'queued', label: 'Queued', type: 'select', options: ['yes', 'no'] }
                    ]
                },
                notification: {
                    title: 'Notification',
                    description: 'Notification delivered through channels.',
                    defaults: { class: 'WelcomeNotification', channels: 'mail, database', recipient: 'User' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'channels', label: 'Channels', type: 'text' },
                        { key: 'recipient', label: 'Recipient', type: 'text' }
                    ]
                },
                mail: {
                    title: 'Mail',
                    description: 'Mailable message.',
                    defaults: { class: 'WelcomeMail', view: 'mail.welcome', subject: 'Welcome' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'view', label: 'View', type: 'text' },
                        { key: 'subject', label: 'Subject', type: 'text' }
                    ]
                },
                queue: {
                    title: 'Queue',
                    description: 'Queue connection and worker lane.',
                    defaults: { connection: 'redis', name: 'default', workers: '2' },
                    fields: [
                        { key: 'connection', label: 'Connection', type: 'text' },
                        { key: 'name', label: 'Name', type: 'text' },
                        { key: 'workers', label: 'Workers', type: 'text' }
                    ]
                },
                view: {
                    title: 'Blade View',
                    description: 'Blade template rendered to the user.',
                    defaults: { path: 'users.index', layout: 'layouts.app', data: 'users, filters' },
                    fields: [
                        { key: 'path', label: 'Path', type: 'text' },
                        { key: 'layout', label: 'Layout', type: 'text' },
                        { key: 'data', label: 'Data', type: 'textarea' }
                    ]
                },
                component: {
                    title: 'Blade Component',
                    description: 'Reusable UI component.',
                    defaults: { class: 'UserCard', view: 'components.user-card', props: 'user, compact' },
                    fields: [
                        { key: 'class', label: 'Class', type: 'text' },
                        { key: 'view', label: 'View', type: 'text' },
                        { key: 'props', label: 'Props', type: 'textarea' }
                    ]
                },
                auth: {
                    title: 'Auth',
                    description: 'Authentication and guard behavior.',
                    defaults: { guard: 'web', provider: 'users', features: 'login, registration, password reset, email verification' },
                    fields: [
                        { key: 'guard', label: 'Guard', type: 'text' },
                        { key: 'provider', label: 'Provider', type: 'text' },
                        { key: 'features', label: 'Features', type: 'textarea' }
                    ]
                },
                cache: {
                    title: 'Cache',
                    description: 'Cache key or tagged cache boundary.',
                    defaults: { store: 'redis', key: 'users.active', ttl: '300' },
                    fields: [
                        { key: 'store', label: 'Store', type: 'text' },
                        { key: 'key', label: 'Key', type: 'text' },
                        { key: 'ttl', label: 'TTL seconds', type: 'text' }
                    ]
                },
                storage: {
                    title: 'Storage',
                    description: 'Filesystem disk and file location.',
                    defaults: { disk: 'public', path: 'avatars', visibility: 'public' },
                    fields: [
                        { key: 'disk', label: 'Disk', type: 'text' },
                        { key: 'path', label: 'Path', type: 'text' },
                        { key: 'visibility', label: 'Visibility', type: 'select', options: ['public', 'private'] }
                    ]
                }
            };

            function workflowUrl() {
                return new URL('/appyhp/api/workflows', window.location.origin);
            }

            function cloneWorkflowValue(value) {
                return JSON.parse(JSON.stringify(value));
            }

            function createId(prefix) {
                return prefix + '_' + Math.random().toString(36).slice(2, 6) + '_' + Date.now().toString(36).slice(-6);
            }

            function moduleDefinition(type) {
                return laravelModuleDefinitions[type] || laravelModuleDefinitions.service;
            }

            function normalizeWorkflow(workflow, index) {
                var wf = workflow || {};
                var modules = Array.isArray(wf.modules) ? wf.modules : [];
                var edges = Array.isArray(wf.edges) ? wf.edges : [];

                return {
                    id: String(wf.id || createId('wf')),
                    name: String(wf.name || 'Workflow ' + (index + 1)),
                    description: String(wf.description || 'Laravel application workflow'),
                    modules: modules.map(function (module, moduleIndex) {
                        var definition = moduleDefinition(module.type || 'service');
                        var config = Object.assign({}, definition.defaults || {}, module.config || {});
                        config = Object.assign({}, defaultModuleTarget(module.type, config, (wf.meta || {}).frontend), config);
                        if (module.type === 'route') {
                            config.routeType = ['web', 'api', 'console', 'channels'].includes(config.routeType) ? config.routeType : 'web';
                            config.folder = 'routes';
                            config.filename = defaultModuleTarget('route', config, (wf.meta || {}).frontend).filename;
                        }
                        return {
                            id: String(module.id || createId('mod')),
                            type: String(module.type || 'service'),
                            label: String(module.label || definition.title || 'Module'),
                            description: String(module.description || definition.description || ''),
                            x: Number.isFinite(Number(module.x)) ? Number(module.x) : 80 + moduleIndex * 180,
                            y: Number.isFinite(Number(module.y)) ? Number(module.y) : 120,
                            config: config
                        };
                    }),
                    edges: edges.map(function (edge) {
                        return {
                            id: String(edge.id || createId('edge')),
                            from: String(edge.from || ''),
                            to: String(edge.to || ''),
                            label: String(edge.label || ''),
                            type: edge.type === 'stiff' ? 'stiff' : 'flex'
                        };
                    }),
                    meta: Object.assign({}, wf.meta || {})
                };
            }

            function createLaravelWorkflowStore() {
                var state = {
                    workflows: [],
                    selectedWorkflowId: null,
                    selectedModuleId: null,
                    connectingFrom: null,
                    edgeSettingsId: null,
                    pendingFocusModuleId: null,
                    zoom: 1,
                    loading: false,
                    saving: false,
                    dirty: false,
                    canUndo: false,
                    canRedo: false
                };
                var subscribers = [];
                var history = [];
                var future = [];
                var autosaveTimer = null;
                var savePromise = null;
                var revision = 0;
                var historyGroup = null;
                var historyGroupTime = 0;

                function snapshot() {
                    return cloneWorkflowValue(state);
                }

                function notify() {
                    state.canUndo = history.length > 0;
                    state.canRedo = future.length > 0;
                    subscribers.forEach(function (subscriber) {
                        subscriber(snapshot());
                    });
                }

                function selectedWorkflow() {
                    return state.workflows.find(function (workflow) {
                        return workflow.id === state.selectedWorkflowId;
                    }) || null;
                }

                function selectedModule() {
                    var workflow = selectedWorkflow();
                    if (!workflow || !state.selectedModuleId) {
                        return null;
                    }

                    return workflow.modules.find(function (module) {
                        return module.id === state.selectedModuleId;
                    }) || null;
                }

                function recordHistory(group) {
                    if (group && historyGroup === group && Date.now() - historyGroupTime < 1500) {
                        historyGroupTime = Date.now();
                        return;
                    }
                    historyGroup = group || null;
                    historyGroupTime = Date.now();
                    history.push({
                        workflows: cloneWorkflowValue(state.workflows),
                        selectedWorkflowId: state.selectedWorkflowId,
                        selectedModuleId: state.selectedModuleId
                    });
                    if (history.length > 50) {
                        history.shift();
                    }
                    future = [];
                }

                function markDirty() {
                    revision += 1;
                    state.dirty = true;
                    notify();

                    if (appyhpStore.getState().autosave) {
                        scheduleAutosave();
                    }
                }

                function scheduleAutosave() {
                    if (autosaveTimer) {
                        clearTimeout(autosaveTimer);
                    }

                    autosaveTimer = setTimeout(function () {
                        autosaveTimer = null;
                        save().catch(function () {});
                    }, 700);
                }

                function updateActiveWorkflow(mutator, options) {
                    var changed = false;
                    var workflowId = (options && options.workflowId) || state.selectedWorkflowId;
                    recordHistory(options && options.historyGroup);
                    state.workflows = state.workflows.map(function (workflow) {
                        if (workflow.id !== workflowId) {
                            return workflow;
                        }

                        changed = true;
                        var updated = normalizeWorkflow(mutator(cloneWorkflowValue(workflow)), 0);
                        updated.meta = Object.assign({}, updated.meta, {
                            updatedAt: new Date().toISOString()
                        });
                        return updated;
                    });

                    if (!changed) {
                        history.pop();
                        return;
                    }

                    markDirty();
                }

                function load() {
                    state.loading = true;
                    notify();

                    return requestJson(workflowUrl())
                        .then(function (payload) {
                            state.workflows = (payload.workflows || []).map(normalizeWorkflow);
                            state.selectedWorkflowId = state.workflows[0] ? state.workflows[0].id : null;
                            state.selectedModuleId = null;
                            state.connectingFrom = null;
                            state.edgeSettingsId = null;
                            state.pendingFocusModuleId = null;
                            state.dirty = false;
                            history = [];
                            future = [];
                        })
                        .catch(function (error) {
                            setWorkflowStatus(error.message);
                        })
                        .finally(function () {
                            state.loading = false;
                            notify();
                        });
                }

                function save() {
                    if (autosaveTimer) {
                        clearTimeout(autosaveTimer);
                        autosaveTimer = null;
                    }

                    if (savePromise) {
                        return savePromise.then(function () { return state.dirty ? save() : undefined; });
                    }
                    var savedRevision = revision;
                    var payloadWorkflows = cloneWorkflowValue(state.workflows);
                    state.saving = true;
                    notify();

                    savePromise = publishGeneratedFiles(payloadWorkflows).then(function () {
                        return postJson(workflowUrl(), {
                            workflows: payloadWorkflows
                        }, 'PUT');
                    }).then(function (payload) {
                        if (revision === savedRevision) {
                            state.workflows = (payload.workflows || state.workflows).map(normalizeWorkflow);
                            state.dirty = false;
                        }
                        if (!state.workflows.some(function (workflow) {
                            return workflow.id === state.selectedWorkflowId;
                        })) {
                            state.selectedWorkflowId = state.workflows[0] ? state.workflows[0].id : null;
                        }
                        setWorkflowStatus(state.dirty ? 'Saved. New edits are pending.' : 'Saved');
                    }).catch(function (error) {
                        setWorkflowStatus(error.message);
                        throw error;
                    }).finally(function () {
                        savePromise = null;
                        state.saving = false;
                        notify();
                    });
                    return savePromise;
                }

                function createWorkflow() {
                    recordHistory();
                    var id = createId('wf');
                    var routeId = createId('mod');
                    var controllerId = createId('mod');
                    var tableId = createId('mod');
                    var now = new Date().toISOString();

                    state.workflows.push(normalizeWorkflow({
                        id: id,
                        name: 'Workflow ' + (state.workflows.length + 1),
                        description: 'Laravel application workflow',
                        modules: [
                            createModuleFromType('route', routeId, 80, 150),
                            createModuleFromType('controller', controllerId, 290, 150),
                            createModuleFromType('table', tableId, 510, 150)
                        ],
                        edges: [
                            { id: createId('edge'), from: routeId, to: controllerId, label: 'dispatch', type: 'flex' },
                            { id: createId('edge'), from: controllerId, to: tableId, label: 'persists', type: 'flex' }
                        ],
                        meta: { createdAt: now, updatedAt: now }
                    }, state.workflows.length));
                    state.selectedWorkflowId = id;
                    state.selectedModuleId = null;
                    state.connectingFrom = null;
                    state.edgeSettingsId = null;
                    state.pendingFocusModuleId = routeId;
                    markDirty();
                }

                function importWorkflow(workflow) {
                    recordHistory();
                    state.workflows.push(normalizeWorkflow(workflow, state.workflows.length));
                    state.selectedWorkflowId = state.workflows[state.workflows.length - 1].id;
                    state.selectedModuleId = null;
                    state.connectingFrom = null;
                    state.edgeSettingsId = null;
                    state.pendingFocusModuleId = null;
                    markDirty();
                    return save().catch(function () {});
                }

                function deleteWorkflow(id) {
                    if (!id) {
                        return;
                    }

                    var workflow = state.workflows.find(function (entry) {
                        return entry.id === id;
                    });

                    if (!workflow) {
                        return;
                    }

                    openWorkflowDeleteModal(workflow);
                }

                function confirmDeleteWorkflow() {
                    var id = workflowDeleteModal.dataset.workflowId;
                    var workflow = state.workflows.find(function (entry) {
                        return entry.id === id;
                    });

                    if (!workflow) {
                        closeStudioModal(workflowDeleteModal);
                        return;
                    }

                    recordHistory();
                    state.workflows = state.workflows.filter(function (entry) {
                        return entry.id !== id;
                    });
                    state.selectedWorkflowId = state.workflows[0] ? state.workflows[0].id : null;
                    state.selectedModuleId = null;
                    state.connectingFrom = null;
                    state.edgeSettingsId = null;
                    state.pendingFocusModuleId = null;
                    markDirty();
                    closeStudioModal(workflowDeleteModal);
                }

                function renameWorkflow(id) {
                    var workflow = state.workflows.find(function (entry) {
                        return entry.id === id;
                    });

                    if (!workflow) {
                        return;
                    }

                    workflowSettingsModal.dataset.workflowId = id;
                    workflowSettingsName.value = workflow.name || '';
                    workflowSettingsDescription.value = workflow.description || '';
                    workflowSettingsStatus.textContent = '';
                    workflowSettingsModal.classList.add('active');
                    workflowSettingsModal.setAttribute('aria-hidden', 'false');
                    window.setTimeout(function () { workflowSettingsName.focus(); workflowSettingsName.select(); }, 0);
                }

                function saveWorkflowSettings() {
                    var id = workflowSettingsModal.dataset.workflowId;
                    var workflow = state.workflows.find(function (entry) {
                        return entry.id === id;
                    });

                    if (!workflow) {
                        closeStudioModal(workflowSettingsModal);
                        return;
                    }

                    recordHistory();
                    state.workflows = state.workflows.map(function (entry) {
                        if (entry.id !== id) {
                            return entry;
                        }

                        return normalizeWorkflow(Object.assign({}, entry, {
                            name: workflowSettingsName.value.trim() || entry.name,
                            description: workflowSettingsDescription.value.trim() || 'Laravel application workflow',
                            meta: Object.assign({}, entry.meta || {}, { updatedAt: new Date().toISOString() })
                        }), 0);
                    });
                    markDirty();
                    closeStudioModal(workflowSettingsModal);
                }

                function selectWorkflow(id) {
                    state.selectedWorkflowId = id;
                    state.selectedModuleId = null;
                    state.connectingFrom = null;
                    state.edgeSettingsId = null;
                    notify();
                }

                function selectModule(moduleId) {
                    state.selectedModuleId = moduleId || null;
                    state.edgeSettingsId = null;
                    notify();
                }

                function createModuleFromType(type, id, x, y) {
                    var definition = moduleDefinition(type);
                    return {
                        id: id || createId('mod'),
                        type: type,
                        label: definition.title,
                        description: definition.description,
                        x: x,
                        y: y,
                        config: Object.assign({}, defaultModuleTarget(type, definition.defaults || {}, (selectedWorkflow() || {}).meta?.frontend), definition.defaults || {})
                    };
                }

                function nextVisibleModulePosition(workflow) {
                    var modules = workflow.modules || [];

                    for (var attempt = 0; attempt < 80; attempt += 1) {
                        var column = attempt % 5;
                        var row = Math.floor(attempt / 5);
                        var x = 80 + column * 180;
                        var y = 90 + row * 92;
                        var occupied = modules.some(function (module) {
                            return Math.abs(module.x - x) < 170 && Math.abs(module.y - y) < 78;
                        });

                        if (!occupied) {
                            return { x: x, y: y };
                        }

                    }

                    return { x: 80, y: 90 };
                }

                function addModule(type) {
                    var workflow = selectedWorkflow();
                    if (!workflow) {
                        return;
                    }

                    updateActiveWorkflow(function (current) {
                        var position = nextVisibleModulePosition(current);
                        var moduleId = createId('mod');
                        current.modules.push(createModuleFromType(
                            type,
                            moduleId,
                            position.x,
                            position.y
                        ));
                        state.pendingFocusModuleId = moduleId;
                        return current;
                    });
                }

                function clearPendingFocus() {
                    state.pendingFocusModuleId = null;
                    notify();
                }

                function moveModule(moduleId, x, y) {
                    updateActiveWorkflow(function (current) {
                        current.modules = current.modules.map(function (module) {
                            if (module.id !== moduleId) {
                                return module;
                            }

                            return Object.assign({}, module, {
                                x: Math.max(20, Math.round(x)),
                                y: Math.max(20, Math.round(y))
                            });
                        });
                        return current;
                    });
                }

                function updateModule(moduleId, patch, options) {
                    updateActiveWorkflow(function (current) {
                        current.modules = current.modules.map(function (module) {
                            if (module.id !== moduleId) {
                                return module;
                            }

                            return normalizeWorkflow({
                                id: current.id,
                                name: current.name,
                                modules: [Object.assign({}, module, patch, {
                                    config: Object.assign({}, module.config || {}, patch.config || {})
                                })],
                                edges: []
                            }, 0).modules[0];
                        });
                        return current;
                    }, options);
                }

                function setFrontend(frontend) {
                    updateActiveWorkflow(function (current) {
                        current.meta.frontend = frontend;
                        current.modules.forEach(function (module) {
                            if (module.type === 'inertia-page' && (!module.config.framework || module.config.framework === 'inherit')) {
                                module.config.filename = module.config.filename.replace(/\.(vue|jsx|tsx|svelte)$/, '.' + inertiaExtension(frontend, module.config.language));
                            }
                        });
                        return current;
                    });
                }

                function deleteModule(moduleId) {
                    updateActiveWorkflow(function (current) {
                        current.modules = current.modules.filter(function (module) {
                            return module.id !== moduleId;
                        });
                        current.edges = current.edges.filter(function (edge) {
                            return edge.from !== moduleId && edge.to !== moduleId;
                        });
                        return current;
                    });

                    if (state.selectedModuleId === moduleId) {
                        state.selectedModuleId = null;
                    }
                }

                function edgeLabelFor(fromType, toType) {
                    if (fromType === 'route' && toType === 'controller') return 'dispatch';
                    if (toType === 'inertia-page') return 'renders';
                    if (fromType === 'inertia-middleware') return 'shares props';
                    if (fromType === 'middleware') return 'passes';
                    if (toType === 'request') return 'validates';
                    if (fromType === 'controller' && toType === 'service') return 'uses';
                    if (fromType === 'service' && toType === 'repository') return 'queries';
                    if (toType === 'model') return 'uses';
                    if (fromType === 'model' && (toType === 'table' || toType === 'migration')) return 'persists';
                    if (fromType === 'event' && toType === 'listener') return 'handled by';
                    if (toType === 'queue') return 'queued on';
                    if (toType === 'notification' || toType === 'mail') return 'sends';
                    if (toType === 'policy') return 'authorizes';
                    return 'uses';
                }

                function toggleConnect(moduleId) {
                    var workflow = selectedWorkflow();
                    if (!workflow) {
                        return;
                    }

                    if (!state.connectingFrom) {
                        state.connectingFrom = moduleId;
                        notify();
                        return;
                    }

                    if (state.connectingFrom === moduleId) {
                        state.connectingFrom = null;
                        notify();
                        return;
                    }

                    connectModules(state.connectingFrom, moduleId);
                    state.connectingFrom = null;
                    notify();
                }

                function connectModules(fromId, toId) {
                    var workflow = selectedWorkflow();
                    if (!workflow || !fromId || !toId || fromId === toId) {
                        return;
                    }

                    if (workflow.edges.some(function (edge) {
                        return edge.from === fromId && edge.to === toId;
                    })) {
                        return;
                    }

                    var fromModule = workflow.modules.find(function (module) {
                        return module.id === fromId;
                    });
                    var toModule = workflow.modules.find(function (module) {
                        return module.id === toId;
                    });

                    if (!fromModule || !toModule) {
                        return;
                    }

                    updateActiveWorkflow(function (current) {
                        current.edges.push({
                            id: createId('edge'),
                            from: fromId,
                            to: toId,
                            label: edgeLabelFor(fromModule.type, toModule.type),
                            type: 'flex'
                        });
                        return current;
                    });
                }

                function updateEdge(edgeId, patch) {
                    updateActiveWorkflow(function (current) {
                        current.edges = current.edges.map(function (edge) {
                            return edge.id === edgeId ? Object.assign({}, edge, patch) : edge;
                        });
                        return current;
                    });
                }

                function toggleEdgeSettings(edgeId) {
                    state.edgeSettingsId = state.edgeSettingsId === edgeId ? null : edgeId;
                    notify();
                }

                function deleteEdge(edgeId) {
                    updateActiveWorkflow(function (current) {
                        current.edges = current.edges.filter(function (edge) {
                            return edge.id !== edgeId;
                        });
                        return current;
                    });
                    state.edgeSettingsId = null;
                }

                function autoLayout() {
                    updateActiveWorkflow(function (current) {
                        var modules = current.modules || [];
                        var edges = current.edges || [];
                        var incoming = {};
                        modules.forEach(function (module) {
                            incoming[module.id] = [];
                        });
                        edges.forEach(function (edge) {
                            if (incoming[edge.to]) {
                                incoming[edge.to].push(edge.from);
                            }
                        });

                        var cache = {};
                        function layerOf(moduleId, visiting) {
                            visiting = visiting || {};
                            if (cache[moduleId] !== undefined) {
                                return cache[moduleId];
                            }
                            if (visiting[moduleId]) {
                                return 0;
                            }
                            visiting[moduleId] = true;
                            var parents = incoming[moduleId] || [];
                            var layer = parents.length ? Math.max.apply(null, parents.map(function (parentId) {
                                return layerOf(parentId, visiting);
                            })) + 1 : 0;
                            delete visiting[moduleId];
                            cache[moduleId] = layer;
                            return layer;
                        }

                        var layers = {};
                        modules.forEach(function (module) {
                            var layer = layerOf(module.id);
                            layers[layer] = layers[layer] || [];
                            layers[layer].push(module);
                        });

                        current.modules = modules.map(function (module) {
                            var layer = layerOf(module.id);
                            var row = layers[layer].findIndex(function (entry) {
                                return entry.id === module.id;
                            });
                            var count = layers[layer].length;
                            return Object.assign({}, module, {
                                x: 80 + layer * 220,
                                y: 110 + row * 95 + Math.max(0, 3 - count) * 28
                            });
                        });
                        return current;
                    });
                }

                function undo() {
                    if (!history.length) {
                        return;
                    }

                    historyGroup = null;
                    future.push({
                        workflows: cloneWorkflowValue(state.workflows),
                        selectedWorkflowId: state.selectedWorkflowId,
                        selectedModuleId: state.selectedModuleId
                    });
                    var previous = history.pop();
                    state.workflows = previous.workflows;
                    state.selectedWorkflowId = previous.selectedWorkflowId;
                    state.selectedModuleId = previous.selectedModuleId;
                    state.connectingFrom = null;
                    state.edgeSettingsId = null;
                    markDirty();
                }

                function redo() {
                    if (!future.length) {
                        return;
                    }

                    historyGroup = null;
                    history.push({
                        workflows: cloneWorkflowValue(state.workflows),
                        selectedWorkflowId: state.selectedWorkflowId,
                        selectedModuleId: state.selectedModuleId
                    });
                    var next = future.pop();
                    state.workflows = next.workflows;
                    state.selectedWorkflowId = next.selectedWorkflowId;
                    state.selectedModuleId = next.selectedModuleId;
                    state.connectingFrom = null;
                    state.edgeSettingsId = null;
                    markDirty();
                }

                function setZoom(zoom) {
                    state.zoom = Math.min(2, Math.max(0.4, Number(zoom) || 1));
                    notify();
                }

                function subscribe(subscriber) {
                    subscribers.push(subscriber);
                    subscriber(snapshot());

                    return function () {
                        subscribers = subscribers.filter(function (entry) {
                            return entry !== subscriber;
                        });
                    };
                }

                return {
                    getState: snapshot,
                    getActiveWorkflow: selectedWorkflow,
                    getSelectedModule: selectedModule,
                    load: load,
                    save: save,
                    createWorkflow: createWorkflow,
                    importWorkflow: importWorkflow,
                    deleteWorkflow: deleteWorkflow,
                    confirmDeleteWorkflow: confirmDeleteWorkflow,
                    renameWorkflow: renameWorkflow,
                    saveWorkflowSettings: saveWorkflowSettings,
                    selectWorkflow: selectWorkflow,
                    selectModule: selectModule,
                    addModule: addModule,
                    moveModule: moveModule,
                    updateModule: updateModule,
                    setFrontend: setFrontend,
                    deleteModule: deleteModule,
                    toggleConnect: toggleConnect,
                    updateEdge: updateEdge,
                    toggleEdgeSettings: toggleEdgeSettings,
                    deleteEdge: deleteEdge,
                    autoLayout: autoLayout,
                    undo: undo,
                    redo: redo,
                    setZoom: setZoom,
                    clearPendingFocus: clearPendingFocus,
                    subscribe: subscribe
                };
            }

            var workflowStore = createLaravelWorkflowStore();

            @include('appyhp::partials.ai-script')

            function setWorkflowStatus(message) {
                workflowStatus.textContent = message || '';
            }

            function renderModulePalette() {
                modulePalette.innerHTML = '';

                laravelModuleGroups.forEach(function (group) {
                    var groupEl = document.createElement('div');
                    groupEl.className = 'module-palette-group';
                    group.items.forEach(function (item) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'module-type-button';
                        button.textContent = '+' + item.label;
                        button.title = group.label + ': ' + item.label;
                        button.addEventListener('click', function () {
                            workflowStore.addModule(item.type);
                        });
                        groupEl.appendChild(button);
                    });
                    modulePalette.appendChild(groupEl);
                });
            }

            function scrollModulePalette(direction) {
                var distance = Math.max(180, Math.floor(modulePalette.clientWidth * 0.8));
                modulePalette.scrollBy({
                    left: direction * distance,
                    behavior: 'smooth'
                });
            }

            function workflowCardActionIcon(name) {
                if (name === 'ai') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a1 1 0 0 1 1 1v1.05A7 7 0 0 1 19 11v5a3 3 0 0 1-3 3h-1.05A3.5 3.5 0 0 1 12 21.5 3.5 3.5 0 0 1 9.05 19H8a3 3 0 0 1-3-3v-5a7 7 0 0 1 6-6.95V3a1 1 0 0 1 1-1Zm-4 9v5c0 .55.45 1 1 1h6a1 1 0 0 0 1-1v-5a4 4 0 0 0-8 0Zm2.25 1.25a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm3.5 0a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5ZM4 11H3a1 1 0 1 0 0 2h1v-2Zm17 0h-1v2h1a1 1 0 1 0 0-2Z"/></svg>';
                }

                if (name === 'settings') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.28 7.28 0 0 0-1.69-.98L14.5 2.42A.5.5 0 0 0 14 2h-4a.5.5 0 0 0-.5.42L9.12 5.07c-.61.24-1.18.56-1.69.98l-2.49-1a.5.5 0 0 0-.61.22l-2 3.46a.5.5 0 0 0 .12.64l2.11 1.65c-.04.32-.06.65-.06.98s.02.66.07.98l-2.11 1.65a.5.5 0 0 0-.12.64l2 3.46c.13.22.39.31.61.22l2.49-1c.51.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.25 1.18-.58 1.69-.98l2.49 1c.22.09.48 0 .61-.22l2-3.46a.5.5 0 0 0-.12-.64l-2.13-1.65ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg>';
                }

                if (name === 'json') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8.7 16.3 4.4 12l4.3-4.3 1.4 1.4L7.2 12l2.9 2.9-1.4 1.4Zm6.6 0-1.4-1.4 2.9-2.9-2.9-2.9 1.4-1.4 4.3 4.3-4.3 4.3ZM11.1 18l-1.9-.6L12.9 6l1.9.6L11.1 18Z"/></svg>';
                }

                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.7 20.5c-.7 0-1.3-.25-1.8-.75s-.75-1.1-.75-1.8V6h-1.4V4h5V3h8.5v1h5v2h-1.4v11.95c0 .7-.25 1.3-.75 1.8s-1.1.75-1.8.75H6.7ZM17.85 6H6.15v11.95c0 .15.05.28.16.39.11.11.24.16.39.16h10.6c.15 0 .28-.05.39-.16.11-.11.16-.24.16-.39V6ZM8.9 16.5h2V8h-2v8.5Zm4.2 0h2V8h-2v8.5Z"/></svg>';
            }

            function renderWorkflowState(state) {
                renderWorkflowList(state);
                renderWorkflowEditor(state);
                manualSaveButton.disabled = appyhpStore.getState().saving || state.saving;
                saveLabel.textContent = manualSaveButton.disabled ? 'Saving...' : 'Save';
                updateManualSaveState();
                renderHistoryControls();
            }

            function renderWorkflowList(state) {
                workflowList.innerHTML = '';

                if (state.loading) {
                    workflowList.textContent = 'Loading workflows';
                    return;
                }

                if (!state.workflows.length) {
                    workflowList.textContent = 'No workflows yet.';
                    return;
                }

                state.workflows.forEach(function (workflow) {
                    var card = document.createElement('article');
                    var main = document.createElement('button');
                    var info = document.createElement('span');
                    var title = document.createElement('span');
                    var meta = document.createElement('span');
                    var details = document.createElement('div');

                    card.className = 'workflow-card open' + (workflow.id === state.selectedWorkflowId ? ' active' : '');
                    main.type = 'button';
                    main.className = 'workflow-card-main';
                    info.style.minWidth = '0';
                    title.className = 'workflow-title';
                    title.textContent = workflow.name;
                    meta.className = 'workflow-meta';
                    meta.textContent = workflow.modules.length + ' modules · ' + workflow.edges.length + ' edges';
                    info.appendChild(title);
                    info.appendChild(meta);
                    main.appendChild(info);
                    main.addEventListener('click', function () {
                        workflowStore.selectWorkflow(workflow.id);
                    });

                    details.className = 'workflow-card-details';
                    [
                        { label: 'Edit', action: function () { workflowStore.selectWorkflow(workflow.id); } },
                        { label: 'Settings', icon: workflowCardActionIcon('settings'), action: function () { workflowStore.renameWorkflow(workflow.id); } },
                        { label: 'JSON', icon: workflowCardActionIcon('json'), action: function () { openWorkflowJson(workflow); } },
                        { label: 'Delete', icon: workflowCardActionIcon('delete'), action: function () { workflowStore.deleteWorkflow(workflow.id); } }
                    ].forEach(function (item) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'workflow-action';
                        button.title = item.label;
                        button.setAttribute('aria-label', item.label);
                        if (item.icon) {
                            button.innerHTML = item.icon;
                        } else {
                            button.textContent = item.label;
                        }
                        button.addEventListener('click', item.action);
                        details.appendChild(button);
                    });

                    card.appendChild(main);
                    card.appendChild(details);
                    workflowList.appendChild(card);
                });
            }

            function renderWorkflowEditor(state) {
                var workflow = state.workflows.find(function (entry) {
                    return entry.id === state.selectedWorkflowId;
                });

                zoomValue.textContent = Math.round(state.zoom * 100) + '%';
                workflowStage.style.transform = 'scale(' + state.zoom + ')';

                if (!workflow) {
                    activeWorkflowName.textContent = 'Service Workflow';
                    activeWorkflowMeta.textContent = 'No workflow selected.';
                    workflowEmpty.hidden = false;
                    workflowStage.hidden = true;
                    renderModuleConfig(state);
                    return;
                }

                activeWorkflowName.textContent = workflow.name;
                shell.querySelector('[data-workflow-frontend]').value = workflow.meta.frontend || aiProject.frontend || 'blade';
                activeWorkflowMeta.textContent = workflow.modules.length + ' modules · ' + workflow.edges.length + ' edges' + (state.dirty ? ' · unsaved changes' : '');
                workflowEmpty.hidden = true;
                workflowStage.hidden = false;
                resizeWorkflowStage(workflow);
                renderWorkflowModules(workflow, state);
                renderWorkflowEdges(workflow, state);
                renderModuleConfig(state);
                renderEdgeSettings(workflow, state);
                focusPendingModule(workflow, state);
            }

            function resizeWorkflowStage(workflow) {
                var maxX = 1100;
                var maxY = 720;
                (workflow.modules || []).forEach(function (module) {
                    maxX = Math.max(maxX, module.x + 260);
                    maxY = Math.max(maxY, module.y + 180);
                });
                workflowStage.style.width = maxX + 'px';
                workflowStage.style.height = maxY + 'px';
                workflowEdgeLayer.setAttribute('width', maxX);
                workflowEdgeLayer.setAttribute('height', maxY);
            }

            function moduleCenter(module, positions) {
                var position = positions && positions[module.id] ? positions[module.id] : module;
                var node = moduleNodeById(module.id);
                var width = node ? node.offsetWidth : 148;
                var height = node ? node.offsetHeight : 56;

                return {
                    x: position.x + width / 2,
                    y: position.y + height / 2
                };
            }

            function moduleNodeById(moduleId) {
                return Array.prototype.find.call(workflowNodeLayer.children, function (node) {
                    return node.dataset.moduleId === moduleId;
                }) || null;
            }

            function svgEl(name) {
                return document.createElementNS('http://www.w3.org/2000/svg', name);
            }

            function renderWorkflowEdges(workflow, state, positions) {
                workflowEdgeLayer.innerHTML = '';
                var defs = svgEl('defs');
                var marker = svgEl('marker');
                marker.setAttribute('id', 'workflow-arrow');
                marker.setAttribute('markerWidth', '8');
                marker.setAttribute('markerHeight', '8');
                marker.setAttribute('refX', '4');
                marker.setAttribute('refY', '3');
                marker.setAttribute('orient', 'auto');
                var polygon = svgEl('polygon');
                polygon.setAttribute('points', '0 0, 8 3, 0 6');
                polygon.setAttribute('fill', 'rgba(148,163,184,0.95)');
                marker.appendChild(polygon);
                defs.appendChild(marker);
                workflowEdgeLayer.appendChild(defs);

                workflow.edges.forEach(function (edge) {
                    var from = workflow.modules.find(function (module) {
                        return module.id === edge.from;
                    });
                    var to = workflow.modules.find(function (module) {
                        return module.id === edge.to;
                    });

                    if (!from || !to) {
                        return;
                    }

                    var c1 = moduleCenter(from, positions);
                    var c2 = moduleCenter(to, positions);
                    var midX = (c1.x + c2.x) / 2;
                    var midY = (c1.y + c2.y) / 2;
                    var controlGap = 15;
                    var pathD = edge.type === 'stiff'
                        ? 'M ' + c1.x + ' ' + c1.y + ' L ' + c2.x + ' ' + c2.y
                        : 'M ' + c1.x + ' ' + c1.y + ' C ' + midX + ' ' + c1.y + ', ' + midX + ' ' + c2.y + ', ' + c2.x + ' ' + c2.y;
                    var color = state.edgeSettingsId === edge.id ? '#10b981' : 'rgba(148,163,184,0.92)';
                    var group = svgEl('g');
                    var path = svgEl('path');
                    var gear = svgEl('circle');
                    var gearText = svgEl('text');
                    var deleteCircle = svgEl('circle');
                    var deleteText = svgEl('text');
                    var label = svgEl('text');

                    path.setAttribute('d', pathD);
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke', color);
                    path.setAttribute('stroke-width', '2');
                    path.setAttribute('marker-end', 'url(#workflow-arrow)');
                    group.appendChild(path);

                    label.setAttribute('x', midX);
                    label.setAttribute('y', midY - 14);
                    label.setAttribute('fill', color);
                    label.setAttribute('font-size', '10');
                    label.setAttribute('text-anchor', 'middle');
                    label.textContent = edge.label || edge.id;
                    group.appendChild(label);

                    gear.setAttribute('class', 'edge-control');
                    gear.setAttribute('cx', midX - controlGap);
                    gear.setAttribute('cy', midY);
                    gear.setAttribute('r', '10');
                    gear.setAttribute('fill', 'rgba(15,23,42,0.94)');
                    gear.setAttribute('stroke', color);
                    gear.addEventListener('click', function () {
                        workflowStore.toggleEdgeSettings(edge.id);
                    });
                    group.appendChild(gear);

                    gearText.setAttribute('x', midX - controlGap);
                    gearText.setAttribute('y', midY + 4);
                    gearText.setAttribute('fill', color);
                    gearText.setAttribute('font-size', '11');
                    gearText.setAttribute('text-anchor', 'middle');
                    gearText.style.pointerEvents = 'none';
                    gearText.textContent = '⚙';
                    group.appendChild(gearText);

                    deleteCircle.setAttribute('class', 'edge-control');
                    deleteCircle.setAttribute('cx', midX + controlGap);
                    deleteCircle.setAttribute('cy', midY);
                    deleteCircle.setAttribute('r', '10');
                    deleteCircle.setAttribute('fill', 'rgba(15,23,42,0.94)');
                    deleteCircle.setAttribute('stroke', color);
                    deleteCircle.addEventListener('click', function () {
                        workflowStore.deleteEdge(edge.id);
                    });
                    group.appendChild(deleteCircle);

                    deleteText.setAttribute('x', midX + controlGap);
                    deleteText.setAttribute('y', midY + 4);
                    deleteText.setAttribute('fill', color);
                    deleteText.setAttribute('font-size', '11');
                    deleteText.setAttribute('text-anchor', 'middle');
                    deleteText.style.pointerEvents = 'none';
                    deleteText.textContent = 'x';
                    group.appendChild(deleteText);

                    workflowEdgeLayer.appendChild(group);
                });
            }

            function renderWorkflowModules(workflow, state) {
                workflowNodeLayer.innerHTML = '';

                workflow.modules.forEach(function (module) {
                    var node = document.createElement('div');
                    var top = document.createElement('div');
                    var type = document.createElement('span');
                    var actions = document.createElement('span');
                    var config = document.createElement('button');
                    var connect = document.createElement('button');
                    var remove = document.createElement('button');
                    var label = document.createElement('button');
                    var description = document.createElement('span');

                    node.className = 'module-node';
                    node.dataset.moduleId = module.id;
                    node.dataset.type = module.type;
                    node.style.left = module.x + 'px';
                    node.style.top = module.y + 'px';
                    node.classList.toggle('connect-source', state.connectingFrom === module.id);
                    node.classList.toggle('connect-target', Boolean(state.connectingFrom && state.connectingFrom !== module.id));
                    node.title = module.id;

                    top.className = 'module-node-top';
                    type.className = 'module-node-type';
                    type.textContent = module.type;
                    actions.className = 'module-node-actions';

                    config.type = 'button';
                    config.className = 'module-node-action';
                    config.textContent = '@';
                    config.title = 'Configure module';
                    config.addEventListener('click', function (event) {
                        event.stopPropagation();
                        openModuleConfig(module.id);
                    });

                    connect.type = 'button';
                    connect.className = 'module-node-action';
                    connect.textContent = '<>';
                    connect.title = 'Connect module';
                    connect.addEventListener('click', function (event) {
                        event.stopPropagation();
                        workflowStore.toggleConnect(module.id);
                    });

                    remove.type = 'button';
                    remove.className = 'module-node-action delete';
                    remove.textContent = 'x';
                    remove.title = 'Delete module';
                    remove.addEventListener('click', function (event) {
                        event.stopPropagation();
                        workflowStore.deleteModule(module.id);
                    });

                    actions.appendChild(config);
                    actions.appendChild(connect);
                    actions.appendChild(remove);
                    top.appendChild(type);
                    top.appendChild(actions);

                    label.type = 'button';
                    label.className = 'module-node-label';
                    label.textContent = module.label;
                    label.addEventListener('click', function (event) {
                        event.stopPropagation();
                        openModuleConfig(module.id);
                    });
                    description.className = 'module-node-description';
                    description.textContent = module.description || moduleDefinition(module.type).description;

                    node.appendChild(top);
                    node.appendChild(label);
                    node.appendChild(description);
                    node.addEventListener('mousedown', function (event) {
                        startModuleDrag(event, module);
                    });
                    node.addEventListener('dblclick', function () {
                        openModuleConfig(module.id);
                    });
                    workflowNodeLayer.appendChild(node);
                });
            }

            function focusPendingModule(workflow, state) {
                if (!state.pendingFocusModuleId) {
                    return;
                }

                var module = workflow.modules.find(function (entry) {
                    return entry.id === state.pendingFocusModuleId;
                });

                if (!module) {
                    workflowStore.clearPendingFocus();
                    return;
                }

                window.setTimeout(function () {
                    var zoom = workflowStore.getState().zoom || 1;
                    workflowCanvasShell.scrollLeft = Math.max(0, module.x * zoom - 60);
                    workflowCanvasShell.scrollTop = Math.max(0, module.y * zoom - 60);
                    workflowStore.clearPendingFocus();
                }, 0);
            }

            function openModuleConfig(moduleId) {
                workflowStore.selectModule(moduleId);
            }

            function startModuleDrag(event, module) {
                if (event.button !== 0 || event.target.closest('button, input, textarea, select, a')) {
                    return;
                }

                var state = workflowStore.getState();
                var startX = event.clientX;
                var startY = event.clientY;
                var originalX = module.x;
                var originalY = module.y;
                var node = event.currentTarget;
                var moved = false;
                var latestX = originalX;
                var latestY = originalY;

                function renderDragPreview() {
                    workflowDragFrame = null;
                    var workflow = workflowStore.getActiveWorkflow();
                    if (!workflow) {
                        return;
                    }

                    var positions = {};
                    positions[module.id] = { x: latestX, y: latestY };
                    resizeWorkflowStage({
                        modules: workflow.modules.map(function (entry) {
                            return entry.id === module.id
                                ? Object.assign({}, entry, positions[module.id])
                                : entry;
                        })
                    });
                    renderWorkflowEdges(workflow, workflowStore.getState(), positions);
                    renderEdgeSettings(workflow, workflowStore.getState(), positions);
                }

                function scheduleDragPreview() {
                    if (workflowDragFrame !== null) {
                        return;
                    }

                    workflowDragFrame = window.requestAnimationFrame(renderDragPreview);
                }

                function handleMove(moveEvent) {
                    moved = true;
                    var nextX = originalX + (moveEvent.clientX - startX) / state.zoom;
                    var nextY = originalY + (moveEvent.clientY - startY) / state.zoom;
                    latestX = Math.max(20, Math.round(nextX));
                    latestY = Math.max(20, Math.round(nextY));
                    node.style.left = latestX + 'px';
                    node.style.top = latestY + 'px';
                    scheduleDragPreview();
                }

                function handleUp() {
                    document.removeEventListener('mousemove', handleMove);
                    document.removeEventListener('mouseup', handleUp);

                    if (!moved) {
                        return;
                    }

                    workflowStore.moveModule(
                        module.id,
                        latestX,
                        latestY
                    );
                }

                document.addEventListener('mousemove', handleMove);
                document.addEventListener('mouseup', handleUp);
                event.preventDefault();
            }

            function startCanvasPan(event) {
                if (event.button !== 0) {
                    return;
                }

                if (event.target.closest('.module-node, .edge-control, .edge-settings-popover, .workflow-config-panel, button, input, textarea, select, a')) {
                    return;
                }

                var startX = event.clientX;
                var startY = event.clientY;
                var startLeft = workflowCanvasShell.scrollLeft;
                var startTop = workflowCanvasShell.scrollTop;

                function handleMove(moveEvent) {
                    workflowCanvasShell.scrollLeft = startLeft - (moveEvent.clientX - startX);
                    workflowCanvasShell.scrollTop = startTop - (moveEvent.clientY - startY);
                }

                function handleUp() {
                    document.removeEventListener('mousemove', handleMove);
                    document.removeEventListener('mouseup', handleUp);
                }

                document.addEventListener('mousemove', handleMove);
                document.addEventListener('mouseup', handleUp);
                event.preventDefault();
            }

            function renderModuleConfig(state) {
                renderAiModuleConfig(state);
            }

            function addConfigField(parent, module, field) {
                var wrapper = document.createElement('div');
                var label = document.createElement('label');
                var control;
                var value = field.root ? module[field.key] : (module.config || {})[field.key];

                wrapper.className = 'module-config-field';
                label.textContent = field.label;

                if (field.type === 'textarea') {
                    control = document.createElement('textarea');
                    control.rows = 4;
                } else if (field.type === 'select') {
                    control = document.createElement('select');
                    (field.options || []).forEach(function (option) {
                        var optionEl = document.createElement('option');
                        optionEl.value = option;
                        optionEl.textContent = option;
                        control.appendChild(optionEl);
                    });
                } else {
                    control = document.createElement('input');
                    control.type = 'text';
                }

                control.value = value || '';
                control.id = 'module-field-' + field.key;
                control.dataset.configKey = field.key;
                control.dataset.configRoot = field.root ? 'true' : 'false';
                label.htmlFor = control.id;
                control.addEventListener(field.key === 'prompt' ? 'input' : 'change', function () {
                    var current = workflowStore.getSelectedModule();
                    if (!current || current.id !== module.id) return;
                    var patch = {};
                    if (field.root) {
                        patch[field.key] = control.value;
                    } else {
                        patch.config = {};
                        patch.config[field.key] = control.value;
                        if (field.key === 'filename') {
                            patch.config.previousPath = [current.config.folder, current.config.filename].filter(Boolean).join('/');
                        }
                        var before = defaultModuleTarget(current.type, current.config, workflowFrontend());
                        var after = defaultModuleTarget(current.type, Object.assign({}, current.config, patch.config), workflowFrontend());
                        if (current.type === 'route' && field.key === 'routeType') {
                            patch.config.folder = 'routes';
                            patch.config.filename = after.filename;
                            patch.config.previousPath = [current.config.folder, current.config.filename].filter(Boolean).join('/');
                        }
                        if (['class', 'path', 'page', 'name'].includes(field.key)) {
                            if (current.config.folder === before.folder) patch.config.folder = after.folder;
                            if (current.config.filename === before.filename) {
                                patch.config.filename = after.filename;
                                patch.config.previousPath = [current.config.folder, current.config.filename].filter(Boolean).join('/');
                            }
                            if (['table', 'migration'].includes(current.type)) {
                                var previousSuffix = before.filename.replace(/^\d{4}_\d{2}_\d{2}_\d{6}_/, '');
                                var nextSuffix = after.filename.replace(/^\d{4}_\d{2}_\d{2}_\d{6}_/, '');
                                if (current.config.filename.endsWith(previousSuffix)) patch.config.filename = current.config.filename.slice(0, -previousSuffix.length) + nextSuffix;
                            }
                        }
                    }
                    if (current.type === 'inertia-page' && ['framework', 'language'].includes(field.key)) {
                        var next = Object.assign({}, current.config, patch.config);
                        var framework = next.framework === 'inherit' ? workflowFrontend() : next.framework;
                        patch.config.filename = next.filename.replace(/\.(vue|jsx|tsx|svelte)$/, '.' + inertiaExtension(framework, next.language));
                    }
                    workflowStore.updateModule(module.id, patch, { historyGroup: module.id + ':' + field.key });
                    queueModuleGeneration();
                });

                wrapper.appendChild(label);
                wrapper.appendChild(control);
                parent.appendChild(wrapper);
                return control;
            }

            function renderEdgeSettings(workflow, state, positions) {
                var edge = workflow.edges.find(function (entry) {
                    return entry.id === state.edgeSettingsId;
                });

                edgeSettingsPopover.innerHTML = '';
                edgeSettingsPopover.classList.toggle('active', Boolean(edge));

                if (!edge) {
                    return;
                }

                var from = workflow.modules.find(function (module) {
                    return module.id === edge.from;
                });
                var to = workflow.modules.find(function (module) {
                    return module.id === edge.to;
                });

                if (!from || !to) {
                    edgeSettingsPopover.classList.remove('active');
                    return;
                }

                var c1 = moduleCenter(from, positions);
                var c2 = moduleCenter(to, positions);
                edgeSettingsPopover.style.left = ((c1.x + c2.x) / 2 - 95) + 'px';
                edgeSettingsPopover.style.top = ((c1.y + c2.y) / 2 + 26) + 'px';

                edgeSettingsPopover.innerHTML =
                    '<label>Label<input type="text" data-edge-label value="' + escapeHtml(edge.label || '') + '"></label>' +
                    '<label>Edge type<select data-edge-type><option value="flex">Flex</option><option value="stiff">Stiff</option></select></label>';

                edgeSettingsPopover.querySelector('[data-edge-type]').value = edge.type === 'stiff' ? 'stiff' : 'flex';
                edgeSettingsPopover.querySelector('[data-edge-label]').addEventListener('change', function (event) {
                    workflowStore.updateEdge(edge.id, { label: event.target.value });
                });
                edgeSettingsPopover.querySelector('[data-edge-type]').addEventListener('change', function (event) {
                    workflowStore.updateEdge(edge.id, { type: event.target.value });
                });
            }

            function openWorkflowJson(workflow) {
                workflowJsonTitle.textContent = workflow.name + ' JSON';
                workflowJsonOutput.textContent = JSON.stringify(workflow, null, 2);
                workflowJsonModal.dataset.workflowId = workflow.id;
                workflowJsonModal.classList.add('active');
                workflowJsonModal.setAttribute('aria-hidden', 'false');
            }

            function openWorkflowDeleteModal(workflow) {
                workflowDeleteModal.dataset.workflowId = workflow.id;
                workflowDeleteMessage.textContent = 'Delete “' + workflow.name + '”? This removes the service from AppyHP workflows.';
                workflowDeleteModal.classList.add('active');
                workflowDeleteModal.setAttribute('aria-hidden', 'false');
            }

            function downloadWorkflowJson() {
                var workflowId = workflowJsonModal.dataset.workflowId;
                var workflow = workflowStore.getState().workflows.find(function (entry) {
                    return entry.id === workflowId;
                });
                if (!workflow) return;

                var filename = (workflow.name || 'workflow').trim().replace(/[^a-z0-9._-]+/gi, '-').replace(/^-+|-+$/g, '') || 'workflow';
                var blob = new Blob([JSON.stringify(workflow, null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = filename + '.json';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            }

            function importedWorkflowName(filename, workflows) {
                var base = filename.replace(/\.json$/i, '').trim() || 'Imported workflow';
                var candidate = base;
                var number = 2;
                while (workflows.some(function (workflow) { return workflow.name.toLowerCase() === candidate.toLowerCase(); })) {
                    candidate = base + ' ' + number;
                    number += 1;
                }
                return candidate;
            }

            function importWorkflowJson(contents, filename) {
                var imported = JSON.parse(contents);
                if (!imported || typeof imported !== 'object' || Array.isArray(imported)) {
                    throw new Error('The uploaded JSON must contain one workflow object.');
                }

                var state = workflowStore.getState();
                var ids = {};
                var workflow = normalizeWorkflow(imported, state.workflows.length);
                workflow.id = createId('wf');
                workflow.name = importedWorkflowName(filename, state.workflows);
                workflow.modules = workflow.modules.map(function (module) {
                    var oldId = module.id;
                    var newId = createId('mod');
                    ids[oldId] = newId;
                    return Object.assign({}, module, { id: newId });
                });
                workflow.edges = workflow.edges.map(function (edge) {
                    return Object.assign({}, edge, {
                        id: createId('edge'),
                        from: ids[edge.from] || edge.from,
                        to: ids[edge.to] || edge.to
                    });
                });
                workflow.meta = Object.assign({}, workflow.meta, { importedAt: new Date().toISOString() });

                workflowStore.importWorkflow(workflow);
            }

            function handleWorkflowUpload(event) {
                var input = event.target;
                var file = input.files && input.files[0];
                input.value = '';
                if (!file) return;

                var reader = new FileReader();
                reader.onload = function () {
                    try {
                        importWorkflowJson(String(reader.result || ''), file.name);
                    } catch (error) {
                        setWorkflowStatus('Could not import workflow: ' + error.message);
                    }
                };
                reader.onerror = function () {
                    setWorkflowStatus('Could not read the workflow file.');
                };
                reader.readAsText(file);
            }

            function closeWorkflowJson() {
                workflowJsonModal.classList.remove('active');
                workflowJsonModal.setAttribute('aria-hidden', 'true');
            }

            function renderHistoryControls() {
                var setupState = appyhpStore.getState();
                var workflowState = workflowStore.getState();
                var useWorkflowHistory = setupState.activePanel === 'workflows';

                undoButton.disabled = useWorkflowHistory ? !workflowState.canUndo : !setupState.canUndo;
                redoButton.disabled = useWorkflowHistory ? !workflowState.canRedo : !setupState.canRedo;
                undoButton.setAttribute('aria-label', useWorkflowHistory ? 'Undo workflow change' : 'Undo panel change');
                redoButton.setAttribute('aria-label', useWorkflowHistory ? 'Redo workflow change' : 'Redo panel change');
            }

            function renderSetupState(state) {
                setPanel(state.activePanel);
                autosaveInput.checked = state.autosave;
                manualSaveButton.disabled = state.saving || workflowStore.getState().saving;
                saveLabel.textContent = manualSaveButton.disabled ? 'Saving...' : 'Save';
                renderHistoryControls();
                updateManualSaveState();
            }

            function updateManualSaveState() {
                var setupDirty = appyhpStore.getState().dirty;
                var workflowDirty = workflowStore.getState().dirty;
                manualSaveButton.classList.toggle('dirty', setupDirty || fileDirty || workflowDirty);
            }

            function selectDirectory(path) {
                selectedDirectoryPath = path || '';

                Object.keys(directoryEntries).forEach(function (key) {
                    directoryEntries[key].classList.toggle('active', key === selectedDirectoryPath);
                });
            }

            function selectFile(path) {
                selectedFilePath = path || '';

                Object.keys(fileEntries).forEach(function (key) {
                    fileEntries[key].classList.toggle('active', key === selectedFilePath);
                });
            }

            function parentPathOf(path) {
                var parts = String(path || '').split('/').filter(Boolean);
                parts.pop();

                return parts.join('/');
            }

            function closeDirectoryContextMenu() {
                directoryContextMenu.classList.remove('active');
                directoryContextItem = null;
            }

            function openDirectoryContextMenu(event, item) {
                event.preventDefault();
                event.stopPropagation();

                directoryContextItem = item;
                directoryContextTitle.textContent = item.path || 'Project root';
                directoryContextCreate.hidden = item.type !== 'directory';
                directoryContextPaste.hidden = item.type !== 'directory';
                directoryContextPaste.disabled = item.type !== 'directory' || !directoryClipboardItem;
                directoryContextPaste.textContent = directoryClipboardItem
                    ? 'Paste ' + directoryClipboardItem.mode
                    : 'Paste';
                directoryContextName.value = '';
                directoryContextMenu.style.left = Math.min(event.clientX, window.innerWidth - 250) + 'px';
                directoryContextMenu.style.top = Math.min(event.clientY, window.innerHeight - 155) + 'px';
                directoryContextMenu.classList.add('active');

                if (item.type === 'directory') {
                    directoryContextName.focus();
                }
            }

            function createDirectoryContextItem(type) {
                if (!directoryContextItem || directoryContextItem.type !== 'directory') {
                    return;
                }

                var name = directoryContextName.value.trim();
                if (!name) {
                    setDirectoryStatus('Name required');
                    return;
                }

                var endpoint = type === 'folder' ? '/folder' : '/file';
                setDirectoryStatus('Creating');

                postJson(directoryUrl(endpoint), {
                    parent: directoryContextItem.path,
                    name: name
                }).then(function (payload) {
                    var parentPath = directoryContextItem.path;
                    closeDirectoryContextMenu();
                    return loadDirectory(parentPath).then(function () {
                        setDirectoryStatus('');

                        if (type === 'file') {
                            openFile(payload.path);
                        }
                    });
                }).catch(function (error) {
                    setDirectoryStatus(error.message);
                });
            }

            function setDirectoryClipboard(mode) {
                if (!directoryContextItem) {
                    return;
                }

                directoryClipboardItem = {
                    mode: mode,
                    path: directoryContextItem.path,
                    type: directoryContextItem.type
                };
                setDirectoryStatus((mode === 'cut' ? 'Cut ' : 'Copied ') + directoryContextItem.path);
                closeDirectoryContextMenu();
            }

            function pasteDirectoryClipboard() {
                if (!directoryContextItem || !directoryClipboardItem || directoryContextItem.type !== 'directory') {
                    return;
                }

                var targetParent = directoryContextItem.path;
                var sourceParent = parentPathOf(directoryClipboardItem.path);
                setDirectoryStatus('Pasting');

                postJson(directoryUrl('/transfer'), {
                    mode: directoryClipboardItem.mode,
                    source: directoryClipboardItem.path,
                    parent: targetParent
                }).then(function () {
                    if (directoryClipboardItem.mode === 'cut') {
                        directoryClipboardItem = null;
                    }

                    closeDirectoryContextMenu();
                    return Promise.all([
                        loadDirectory(targetParent),
                        sourceParent !== targetParent ? loadDirectory(sourceParent) : Promise.resolve()
                    ]).then(function () {
                        setDirectoryStatus('');
                    });
                }).catch(function (error) {
                    setDirectoryStatus(error.message);
                });
            }

            function deleteDirectoryContextItem() {
                if (!directoryContextItem || !directoryContextItem.path) return;
                var item = directoryContextItem;
                var message = item.type === 'directory'
                    ? 'Delete folder "' + item.path + '" and everything inside it?'
                    : 'Delete file "' + item.path + '"?';
                if (!window.confirm(message)) return;

                setDirectoryStatus('Deleting');
                postJson(directoryUrl('/item'), { path: item.path }, 'DELETE').then(function () {
                    var parentPath = parentPathOf(item.path);
                    if (selectedFilePath === item.path || (item.type === 'directory' && selectedFilePath.indexOf(item.path + '/') === 0)) {
                        selectedFilePath = '';
                        editorPath.textContent = '';
                        editorName.value = '';
                        editorName.disabled = true;
                        fileEditor.value = '';
                        fileEditor.disabled = true;
                        fileDirty = false;
                        updateCodeEditor();
                    }
                    closeDirectoryContextMenu();
                    return loadDirectory(parentPath).then(function () { setDirectoryStatus('Deleted'); });
                }).catch(function (error) {
                    setDirectoryStatus(error.message);
                });
            }

            function loadDirectories() {
                if (directoriesLoaded) {
                    return;
                }

                directoriesLoaded = true;
                directoryTree.innerHTML = '';
                directoryContainers = {};
                directoryToggles = {};
                directoryEntries = {};
                fileEntries = {};

                var rootList = document.createElement('ul');
                rootList.className = 'tree-list';
                directoryTree.appendChild(rootList);
                directoryContainers[''] = rootList;
                selectDirectory('');
                loadDirectory('');
            }

            function loadDirectory(path) {
                var container = directoryContainers[path || ''];

                if (!container) {
                    return Promise.resolve();
                }

                setDirectoryStatus('Loading');

                return requestJson(directoryUrl('/', { path: path || '' }))
                    .then(function (payload) {
                        renderDirectoryItems(path || '', payload.items || [], container);
                        setDirectoryStatus('');
                    })
                    .catch(function (error) {
                        setDirectoryStatus(error.message);
                    });
            }

            function renderDirectoryItems(parentPath, items, container) {
                container.innerHTML = '';

                items.forEach(function (item) {
                    var listItem = document.createElement('li');
                    var row = document.createElement('div');
                    var toggle = document.createElement('button');
                    var entry = document.createElement('button');

                    row.className = 'tree-row';
                    toggle.type = 'button';
                    toggle.className = 'tree-toggle';
                    entry.type = 'button';
                    entry.className = 'tree-entry';
                    entry.title = item.path;
                    setEntryContent(entry, item, false);

                    if (item.type === 'directory') {
                        var children = document.createElement('ul');
                        var expanded = false;

                        children.className = 'tree-children';
                        children.hidden = true;
                        toggle.textContent = '+';
                        directoryContainers[item.path] = children;
                        directoryToggles[item.path] = toggle;
                        directoryEntries[item.path] = entry;

                        function toggleDirectory() {
                            expanded = !expanded;
                            children.hidden = !expanded;
                            toggle.textContent = expanded ? '-' : '+';
                            setEntryContent(entry, item, expanded);
                            selectDirectory(item.path);

                            if (expanded && children.childElementCount === 0) {
                                loadDirectory(item.path);
                            }
                        }

                        toggle.addEventListener('click', toggleDirectory);
                        entry.addEventListener('click', toggleDirectory);
                        entry.addEventListener('contextmenu', function (event) {
                            openDirectoryContextMenu(event, item);
                        });
                        row.appendChild(toggle);
                        row.appendChild(entry);
                        listItem.appendChild(row);
                        listItem.appendChild(children);
                    } else {
                        toggle.textContent = '';
                        toggle.disabled = true;
                        fileEntries[item.path] = entry;

                        entry.addEventListener('click', function () {
                            openFile(item.path);
                        });
                        entry.addEventListener('contextmenu', function (event) {
                            openDirectoryContextMenu(event, item);
                        });

                        row.appendChild(toggle);
                        row.appendChild(entry);
                        listItem.appendChild(row);
                    }

                    container.appendChild(listItem);
                });
            }

            function openFile(path) {
                setEditorStatus('Loading');
                selectFile(path);

                requestJson(directoryUrl('/file', { path: path }))
                    .then(function (payload) {
                        selectedFilePath = payload.path;
                        selectedLanguage = detectLanguage(payload.path);
                        editorPath.textContent = payload.path;
                        editorName.value = payload.name || path.split('/').pop();
                        editorName.disabled = false;
                        fileEditor.value = payload.content || '';
                        fileEditor.disabled = false;
                        updateCodeEditor();
                        fileDirty = false;
                        updateManualSaveState();
                        setEditorStatus('');
                    })
                    .catch(function (error) {
                        setEditorStatus(error.message);
                    });
            }

            function saveCurrentFile() {
                if (!selectedFilePath) {
                    return Promise.resolve();
                }

                setEditorStatus('Saving');

                var nextName = editorName.value.trim();
                var rename = nextName && nextName !== selectedFilePath.split('/').pop()
                    ? postJson(directoryUrl('/rename'), { path: selectedFilePath, name: nextName })
                    : Promise.resolve({ path: selectedFilePath });

                return rename.then(function (renamed) {
                    var oldPath = selectedFilePath;
                    selectedFilePath = renamed.path;
                    editorPath.textContent = selectedFilePath;
                    editorName.value = selectedFilePath.split('/').pop();
                    if (oldPath !== selectedFilePath) {
                        selectFile(selectedFilePath);
                        loadDirectory(parentPathOf(oldPath));
                    }
                    return postJson(directoryUrl('/file'), {
                        path: selectedFilePath,
                        content: fileEditor.value
                    }, 'PUT');
                }).then(function () {
                    fileDirty = false;
                    updateManualSaveState();
                    setEditorStatus('Saved');
                }).catch(function (error) {
                    setEditorStatus(error.message);
                });
            }

            function scheduleFileAutosave() {
                if (fileAutosaveTimer) {
                    clearTimeout(fileAutosaveTimer);
                }

                fileAutosaveTimer = setTimeout(function () {
                    fileAutosaveTimer = null;
                    saveCurrentFile();
                }, 650);
            }

            function handleFileInput() {
                if (!selectedFilePath) {
                    return;
                }

                fileDirty = true;
                updateCodeEditor();
                updateManualSaveState();
                syncEditedFileToModules();

                if (appyhpStore.getState().autosave) {
                    scheduleFileAutosave();
                }
            }

            function syncEditedFileToModules() {
                var workflow = workflowStore.getActiveWorkflow();
                if (!workflow) return;
                workflow.modules.forEach(function (module) {
                    var config = module.config || {};
                    var path = [config.folder, config.filename].filter(Boolean).join('/');
                    if (path !== selectedFilePath || (config.ai && config.ai.code === fileEditor.value)) return;
                    workflowStore.updateModule(module.id, {
                        config: { ai: Object.assign({}, config.ai || {}, { code: fileEditor.value, path: path, editedAt: new Date().toISOString() }) }
                    }, { historyGroup: 'file:' + selectedFilePath });
                });
            }

            function closeStudioModal(modal) {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
            }

            function openFileInfo() {
                fileInfoModal.classList.add('active');
                fileInfoModal.setAttribute('aria-hidden', 'false');
                fileInfoAiResult.textContent = '';
                fileInfoAiButton.disabled = false;
                fileInfoAiButton.textContent = 'Ask AI';
                if (!selectedFilePath) {
                    fileInfoBody.textContent = 'Select a file from the Directories panel to view what it is used for.';
                    return;
                }
                var description = 'Text source file in the project. Its behavior is defined by the code shown in the editor.';
                var lower = selectedFilePath.toLowerCase();
                if (lower.includes('/controllers/')) description = 'Laravel controller: receives requests and coordinates application actions.';
                else if (lower.includes('/models/')) description = 'Laravel Eloquent model: represents persisted data and relationships.';
                else if (lower.includes('/routes/')) description = 'Laravel route file: maps URLs and HTTP methods to application handlers.';
                else if (lower.includes('/migrations/')) description = 'Laravel migration: describes a database schema change.';
                else if (lower.includes('/resources/views/')) description = 'View template: renders user-facing HTML or a page fragment.';
                fileInfoBody.textContent = description;
            }

            function askAiAboutFile() {
                if (!selectedFilePath) {
                    fileInfoAiResult.textContent = 'Select a file first.';
                    return;
                }
                fileInfoAiButton.disabled = true;
                fileInfoAiButton.textContent = 'Asking AI...';
                fileInfoAiResult.textContent = 'Reading the file and asking AI...';
                requestJson(aiUrl('explain-file'), {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ path: selectedFilePath })
                }).then(function (payload) {
                    fileInfoAiResult.textContent = payload.explanation || 'AI did not return an explanation.';
                }).catch(function (error) {
                    fileInfoAiResult.textContent = error.message;
                }).finally(function () {
                    fileInfoAiButton.disabled = false;
                    fileInfoAiButton.textContent = 'Ask AI';
                });
            }

            function openFileNotes() {
                fileNotesModal.classList.add('active');
                fileNotesModal.setAttribute('aria-hidden', 'false');
                if (!selectedFilePath) {
                    fileNotesInput.value = '';
                    fileNotesStatus.textContent = 'Select a file before saving notes.';
                    return;
                }
                fileNotesStatus.textContent = 'Loading...';
                requestJson(directoryUrl('/metadata', { path: selectedFilePath })).then(function (payload) {
                    fileNotesInput.value = payload.notes || '';
                    fileNotesStatus.textContent = '';
                    fileNotesInput.focus();
                }).catch(function (error) { fileNotesStatus.textContent = error.message; });
            }

            function handleEditorKeydown(event) {
                if (event.key !== 'Tab') {
                    return;
                }

                event.preventDefault();
                insertAtCursor('    ');
                handleFileInput();
            }

            function updateFullscreenIcon() {
                var isFullscreen = Boolean(document.fullscreenElement);
                fullscreenEnterIcon.hidden = isFullscreen;
                fullscreenExitIcon.hidden = !isFullscreen;
            }

            toggleButton.addEventListener('click', function () {
                setSidebarVisible(!sidebarVisible);
            });

            manualSaveButton.addEventListener('click', function () {
                var fileSave = fileDirty ? saveCurrentFile() : Promise.resolve();
                fileSave.then(function () {
                    return workflowStore.getState().dirty ? workflowStore.save() : Promise.resolve();
                }).then(function () {
                    return appyhpStore.save();
                }).catch(function (error) {
                    setWorkflowStatus(error.message);
                });
            });

            autosaveInput.addEventListener('change', function () {
                appyhpStore.setAutosave(autosaveInput.checked);
            });

            undoButton.addEventListener('click', function () {
                if (appyhpStore.getState().activePanel === 'workflows') {
                    workflowStore.undo();
                    return;
                }

                appyhpStore.undo();
            });
            redoButton.addEventListener('click', function () {
                if (appyhpStore.getState().activePanel === 'workflows') {
                    workflowStore.redo();
                    return;
                }

                appyhpStore.redo();
            });

            panelSwitcher.addEventListener('click', function () {
                appyhpStore.setActivePanel(
                    appyhpStore.getState().activePanel === 'workflows' ? 'directories' : 'workflows'
                );
            });

            workflowNewButton.addEventListener('click', workflowStore.createWorkflow);
            workflowUploadButton.addEventListener('click', function () { workflowUploadInput.click(); });
            workflowUploadInput.addEventListener('change', handleWorkflowUpload);
            shell.querySelector('[data-workflow-frontend]').addEventListener('change', function (event) {
                workflowStore.setFrontend(event.target.value);
                queueModuleGeneration();
            });
            workflowAutoButton.addEventListener('click', workflowStore.autoLayout);
            moduleScrollLeftButton.addEventListener('click', function () {
                scrollModulePalette(-1);
            });
            moduleScrollRightButton.addEventListener('click', function () {
                scrollModulePalette(1);
            });
            workflowJsonClose.addEventListener('click', closeWorkflowJson);
            workflowJsonDownload.addEventListener('click', downloadWorkflowJson);
            workflowJsonModal.addEventListener('click', function (event) {
                if (event.target === workflowJsonModal) {
                    closeWorkflowJson();
                }
            });
            zoomOutButton.addEventListener('click', function () {
                workflowStore.setZoom(workflowStore.getState().zoom - 0.1);
            });
            zoomInButton.addEventListener('click', function () {
                workflowStore.setZoom(workflowStore.getState().zoom + 0.1);
            });
            zoomResetButton.addEventListener('click', function () {
                workflowStore.setZoom(1);
            });
            workflowCanvasShell.addEventListener('mousedown', startCanvasPan);

            themeButton.addEventListener('click', function () {
                setTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
            });

            fullscreenButton.addEventListener('click', function () {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                    return;
                }

                document.documentElement.requestFullscreen();
            });

            document.addEventListener('fullscreenchange', updateFullscreenIcon);

            directoryContextCreateFile.addEventListener('click', function () {
                createDirectoryContextItem('file');
            });
            directoryContextCreateFolder.addEventListener('click', function () {
                createDirectoryContextItem('folder');
            });
            directoryContextCut.addEventListener('click', function () {
                setDirectoryClipboard('cut');
            });
            directoryContextCopy.addEventListener('click', function () {
                setDirectoryClipboard('copy');
            });
            directoryContextPaste.addEventListener('click', pasteDirectoryClipboard);
            directoryContextDelete.addEventListener('click', deleteDirectoryContextItem);
            directoryContextName.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    createDirectoryContextItem('file');
                }
            });
            document.addEventListener('click', function (event) {
                if (!directoryContextMenu.contains(event.target)) {
                    closeDirectoryContextMenu();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeDirectoryContextMenu();
                }
            });

            fileEditor.addEventListener('scroll', syncCodeEditorScroll);
            fileEditor.addEventListener('keydown', handleEditorKeydown);
            fileEditor.addEventListener('input', handleFileInput);
            editorName.addEventListener('input', function () {
                if (!selectedFilePath) return;
                fileDirty = true;
                updateManualSaveState();
                if (appyhpStore.getState().autosave) scheduleFileAutosave();
            });
            editorInfoButton.addEventListener('click', openFileInfo);
            fileInfoAiButton.addEventListener('click', askAiAboutFile);
            editorNotesButton.addEventListener('click', openFileNotes);
            document.querySelector('[data-workflow-settings-close]').addEventListener('click', function () { closeStudioModal(workflowSettingsModal); });
            document.querySelector('[data-workflow-settings-cancel]').addEventListener('click', function () { closeStudioModal(workflowSettingsModal); });
            document.querySelector('[data-workflow-settings-save]').addEventListener('click', workflowStore.saveWorkflowSettings);
            document.querySelector('[data-workflow-delete-close]').addEventListener('click', function () { closeStudioModal(workflowDeleteModal); });
            document.querySelector('[data-workflow-delete-cancel]').addEventListener('click', function () { closeStudioModal(workflowDeleteModal); });
            document.querySelector('[data-workflow-delete-confirm]').addEventListener('click', workflowStore.confirmDeleteWorkflow);
            document.querySelector('[data-file-info-close]').addEventListener('click', function () { closeStudioModal(fileInfoModal); });
            document.querySelector('[data-file-notes-close]').addEventListener('click', function () { closeStudioModal(fileNotesModal); });
            document.querySelector('[data-file-notes-save]').addEventListener('click', function () {
                if (!selectedFilePath) {
                    fileNotesStatus.textContent = 'Select a file before saving notes.';
                    return;
                }
                fileNotesStatus.textContent = 'Saving...';
                postJson(directoryUrl('/metadata'), { path: selectedFilePath, notes: fileNotesInput.value }, 'PUT')
                    .then(function () { fileNotesStatus.textContent = 'Notes saved.'; })
                    .catch(function (error) { fileNotesStatus.textContent = error.message; });
            });
            [workflowSettingsModal, workflowDeleteModal, fileInfoModal, fileNotesModal].forEach(function (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) closeStudioModal(modal);
                });
            });

            window.addEventListener('storage', function (event) {
                if (event.key === 'theme') {
                    setTheme(event.newValue || 'dark');
                }
            });

            setSidebarVisible(window.innerWidth > 760);
            setTheme(localStorage.getItem('theme') || 'dark');
            renderModulePalette();
            workflowStore.subscribe(renderWorkflowState);
            appyhpStore.subscribe(renderSetupState);
            loadAiSettings().then(function () { return workflowStore.load(); });
            appyhpStore.load();
            updateCodeEditor();
            updateFullscreenIcon();
        })();
    </script>
</body>
</html>
