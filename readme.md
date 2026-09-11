# AppyHP, 🚧beta

AppyHP is a free Laravel visual workflow builder for planning application architecture, describing module responsibilities, and turning those plans into real project files. Build workflows from routes, controllers, requests, models, tables, services, jobs, events, pages, and other Laravel building blocks, then connect them to show how your application fits together.

The integrated AI assistant uses the workflow graph, module prompts, project structure, connected source files, and current drafts to help generate complete code. AppyHP also includes a live Directories panel for browsing and editing project files, renaming and organizing files, asking AI to explain source code, and saving notes that preserve project-specific context.

AppyHP is designed for local development and documentation. It works with configured AI providers, supports manual Save and Auto-save publication, and keeps personal workflows, prompts, notes, settings, and sessions in an ignored runtime directory so a fresh Composer installation starts clean.

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
APPYHP_RUNTIME_PATH=/absolute/private/path/to/appyhp-state
```

Then open `/appyhp/studio`. Studio accepts loopback traffic only by default. If a trusted development proxy or container needs access, set a narrow IP or CIDR list:

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

Runtime state contains workflows, prompts, drafts, notes, encrypted AI settings, and Studio sessions. Keep `APPYHP_RUNTIME_PATH` outside the public web root, back it up only if those drafts matter, and restrict it to the web process account.

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

The repository CI runs validation, an advisory audit, and the complete test suite on supported PHP versions. Personal runtime data, Composer dependencies, and local lock files are ignored by Git.
