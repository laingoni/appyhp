# AppyHP

AppyHP is a free Laravel visual workflow builder for planning application architecture, describing module responsibilities, and turning those plans into real project files. Build workflows from routes, controllers, requests, models, tables, services, jobs, events, pages, and other Laravel building blocks, then connect them to show how your application fits together.

The integrated AI assistant uses the workflow graph, module prompts, project structure, connected source files, and current drafts to help generate complete code. AppyHP also includes a live Directories panel for browsing and editing project files, renaming and organizing files, asking AI to explain source code, and saving notes that preserve project-specific context.

AppyHP is designed for local development and documentation. It works with configured AI providers, supports manual Save and Auto-save publication, and keeps personal workflows, prompts, notes, settings, and sessions in an ignored runtime directory so a fresh Composer installation starts clean.

## Module file scaffolding

Saving a workflow creates missing module targets with Laravel's native Artisan generators. Controllers, middleware, requests, API resources, models, migrations, factories, seeders, policies, jobs, commands, events, listeners, notifications, mailables, Blade views, and Blade components therefore use the stubs supplied by the installed Laravel version. Auth, queue, cache, and filesystem modules use Laravel's configuration publisher.

Laravel has no native file generator for routes, services, repositories, queue lanes, or frontend Inertia pages. AppyHP supplies small starter scaffolds for those module types and uses `inertia:middleware` when the Inertia Laravel adapter is installed. Existing source files are never overwritten; zero-byte module files created by an older AppyHP version are upgraded to the appropriate scaffold on the next save. Files created manually in the Directories panel remain empty by design.

The module code editor loads the current target file and can save direct edits as well as AI-generated changes. Route modules treat the Route file selector as a switch: changing between `web.php`, `api.php`, `console.php`, and `channels.php` loads or creates the selected file without renaming or modifying the previously selected route file.

AI generation and manual code editing update the in-memory module draft first. The target file is written only when Save is used or Auto-save is enabled. The header Undo and Redo controls share one chronological history across setup, workflow/module, AI-draft, and Directories editor changes.

Opening a workflow module gives its editor the full workspace below the Studio header and temporarily closes the sidebar. The editor's Back control returns to the workflow canvas and restores whether the sidebar was open or closed before editing. The normal sidebar preference is persisted in AppyHP setup storage.

Generation context contains the user's module description, the current target source, the latest unsaved draft, project versions, and direction-aware source and contracts from connected workflow modules. When another project file is genuinely required, the AI can request its path in the module panel. Granting access adds a secure read-only snapshot to the continuation; denying access records the decision and generation continues without that file.

## Runtime storage

AppyHP keeps settings, workflows, directory metadata, and file-backed studio sessions in one package-local `.appyhp` directory. This folder is ignored by git, is not included in a fresh Composer checkout, and all required folders/files are created lazily when a feature first needs them.

For a host application or isolated local testing, point the package at another ignored directory:

```dotenv
APPYHP_RUNTIME_PATH=/absolute/path/to/your/project/.appyhp
```

The same value can be set with the `appyhp.runtime_path` config key. Do not commit this directory because it may contain encrypted AI credentials and user-specific studio state.
