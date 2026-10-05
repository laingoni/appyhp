# AppyHP

AppyHP is a free Laravel visual workflow builder for planning application architecture, describing module responsibilities, and turning those plans into real project files. Build workflows from routes, controllers, requests, models, tables, services, jobs, events, pages, and other Laravel building blocks, then connect them to show how your application fits together.

The integrated AI assistant uses the workflow graph, module prompts, project structure, connected source files, and current drafts to help generate complete code. AppyHP also includes a live Directories panel for browsing and editing project files, renaming and organizing files, asking AI to explain source code, and saving notes that preserve project-specific context.

AppyHP is designed for local development and documentation. It works with configured AI providers, supports manual Save and Auto-save publication, and stores workflows, prompts, notes, settings, and sessions in the host application's private storage directory.

> AppyHP Studio can read, create, change, move, and delete project source files. It is a local development tool. Do not expose Studio on a public production URL.

## Open the AppyHP visual studio at:

http://127.0.0.1:8000/appyhp/studio

## Requirements

- PHP 8.3 or newer
- Laravel 13
- A writable runtime directory
- An AI provider key only when AI features are used

## Installation

```bash
composer require alliswell/appyhp --dev
php artisan vendor:publish --tag=appyhp-config
```

Enable Studio in the local environment:

```dotenv
APPY_MODE=dev
```

Run `php artisan appyhp:doctor`, then open `/appyhp/studio`. The host application needs an `APP_KEY` (use `php artisan key:generate` on a fresh application). Studio accepts loopback traffic only by default. If a trusted development proxy or container needs access, set a narrow IP or CIDR list:

```dotenv
APPYHP_ALLOWED_IPS=127.0.0.1,::1,172.18.0.0/16
```

For any non-loopback access, also require HTTP Basic authentication with a long, random token. The username is `appyhp` and the password is the configured token:

```dotenv
APPYHP_ACCESS_TOKEN=replace-with-a-long-random-secret
```

You may apply additional middleware registered by the host application with `APPYHP_MIDDLEWARE`; multiple names may be comma-separated. Clear and rebuild Laravel's configuration cache after changing these values.

## AI configuration

AI settings can be entered in Studio or provided by the environment:

```dotenv
APPY_AI_PROVIDER=openai
APPY_AI_BASE_URL=https://api.openai.com/v1
APPY_AI_MODEL=your-model-id
APPY_AI_KEY=your-secret-key
```

Supported protocols are OpenAI Responses, Anthropic Messages, and OpenAI-compatible Chat Completions. Stored keys are encrypted with the host application's `APP_KEY`; changing that key makes previously stored AI credentials unreadable.

Runtime state defaults to `storage/app/appyhp` and contains workflows, prompts, drafts, notes, encrypted AI settings, and Studio sessions. Override it with `APPYHP_RUNTIME_PATH=/absolute/private/path/to/appyhp-state` if needed. Keep it outside the public web root, back it up if those drafts matter, and restrict it to the web process account. State survives Composer updates.

## Using Studio

1. Add modules from the palette, choose their target files, and connect their responsibilities.
2. Describe the behavior in each module's prompt. AI is optional: you can scaffold and edit files manually.
3. Review generated drafts. Save stores the graph and publishes its file changes; Auto-save does this after a pause.
4. Review the resulting Git diff, register routes or middleware as needed, and run your application's tests.

Scaffolds follow Laravel's Artisan generators and Composer namespaces. They are starting points, not complete business logic. AppyHP does not automatically install dependencies, run migrations, or start queue workers. The [module reference](web/modules.html) covers every supported module and the remaining Laravel integration steps.

Saving checks file hashes and workflow revisions. If another editor or Studio tab changed the source, download your workflow JSON before reloading to resolve the conflict. Corrupted workflow storage is preserved for recovery. Deleting a workflow leaves its existing project files intact.

## Upgrading package-local storage

Older versions defaulted to `.appyhp` inside the installed package. Back up that directory, then copy its JSON state files to the host application's `storage/app/appyhp`, or set `APPYHP_RUNTIME_PATH` explicitly to keep the old location. No files are moved automatically. Retain `APP_KEY` to keep encrypted provider keys readable; if it changed, re-enter the key in AI settings.

## Production deployment

Set `APPY_MODE=dist` in production. This prevents the package from registering Studio and API routes. Do not use `APP_DEBUG=true` in production; when `APPY_MODE` is absent, route availability falls back to `APP_DEBUG` for backward compatibility.

Typical deployment checks:

```bash
composer install --no-dev --classmap-authoritative
php artisan optimize
php artisan route:list --path=appyhp
```

The final command should return no AppyHP routes in a production environment.

## Development and verification

```bash
composer install
composer validate --strict
composer audit --locked
composer test
```

Run browser tests in an isolated temporary Laravel application; AI responses are faked and no provider credits are used:

```bash
npm ci
npx playwright install chromium
npm run test:browser
```

To use an installed Chrome, set `APPYHP_CHROME=/absolute/path/to/chrome`. Screenshots are saved to the printed temporary artifact directory, or `APPYHP_BROWSER_ARTIFACTS` when set.

CI runs backend tests on PHP 8.3, 8.4 and 8.5, dependency audits, and desktop/mobile browser scenarios. Version tags run those checks before publishing a GitHub release. Personal runtime data, dependencies, and the local Composer lock file are excluded from package archives.

## Troubleshooting

- `php artisan appyhp:doctor` checks mode, storage access, application key and remote-access configuration without changing source files.
- **404:** enable `APPY_MODE=dev`, then clear stale caches with `php artisan optimize:clear`.
- **403 / 401:** check the allowed address list and HTTP Basic credentials.
- **419:** reload Studio to renew its session before submitting again.
- **AI fails:** test the provider from Settings and verify its model, protocol, key and quota. No live provider is required for the workflow editor.
- **Unwritable storage:** give the application process access to its private runtime directory. Do not make it publicly writable.

See [installation](web/install.html), [workflows](web/workflows.html), [AI](web/ai.html), and [files](web/files.html) for the full user guide.
