# Initial release

AppyHP is a local Laravel 13 development studio for visual architecture, native scaffolding, AI-assisted drafts, and project file editing.

- Map Laravel modules on a connected workflow canvas, with import/export and undo/redo.
- Scaffold through Laravel Artisan and use Composer namespaces for application classes.
- Generate and review source with OpenAI Responses, Anthropic Messages, or compatible providers.
- Browse and edit project files, save Markdown notes, and request AI explanations.
- Preserve drafts in application storage, detect conflicting saves, and protect corrupted workflow state.
- Keep Studio local by default, enforce authentication for remote access, and disable access in distribution mode, including cached routes.
- Read the complete module reference and diagnose setup with `php artisan appyhp:doctor`.

Requires PHP 8.3+ and Laravel 13. Install with `composer require alliswell/appyhp --dev`, set `APPY_MODE=dev`, and open `/appyhp/studio` in the host application.

For older installations using package-local `.appyhp` state, back it up and copy its JSON files into `storage/app/appyhp`, or explicitly retain the old absolute path with `APPYHP_RUNTIME_PATH`. Keep the host application's `APP_KEY` to retain access to saved encrypted AI keys.

The release workflow runs backend tests on PHP 8.3–8.5, dependency audits, and isolated desktop/mobile browser tests before publication. Live AI services require your own provider configuration and are not contacted by automated tests.
