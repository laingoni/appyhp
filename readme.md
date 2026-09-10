# AppyHP

AppyHP is a free Laravel visual workflow builder for planning application architecture, describing module responsibilities, and turning those plans into real project files. Build workflows from routes, controllers, requests, models, tables, services, jobs, events, pages, and other Laravel building blocks, then connect them to show how your application fits together.

The integrated AI assistant uses the workflow graph, module prompts, project structure, connected source files, and current drafts to help generate complete code. AppyHP also includes a live Directories panel for browsing and editing project files, renaming and organizing files, asking AI to explain source code, and saving notes that preserve project-specific context.

AppyHP is designed for local development and documentation. It works with configured AI providers, supports manual Save and Auto-save publication, and keeps personal workflows, prompts, notes, settings, and sessions in an ignored runtime directory so a fresh Composer installation starts clean.

## Runtime storage

AppyHP keeps settings, workflows, directory metadata, and file-backed studio sessions in one package-local `.appyhp` directory. This folder is ignored by git, is not included in a fresh Composer checkout, and all required folders/files are created lazily when a feature first needs them.

For a host application or isolated local testing, point the package at another ignored directory:

```dotenv
APPYHP_RUNTIME_PATH=/absolute/path/to/your/project/.appyhp
```

The same value can be set with the `appyhp.runtime_path` config key. Do not commit this directory because it may contain encrypted AI credentials and user-specific studio state.
