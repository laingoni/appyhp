# Appyhp

## Runtime storage

Appyhp keeps settings, workflows, directory metadata, and file-backed studio sessions in one package-local `.appyhp` directory. This folder is ignored by git, is not included in a fresh Composer checkout, and all required folders/files are created lazily when a feature first needs them.

For a host application or isolated local testing, point the package at another ignored directory:

```dotenv
APPYHP_RUNTIME_PATH=/absolute/path/to/your/project/.appyhp
```

The same value can be set with the `appyhp.runtime_path` config key. Do not commit this directory because it may contain encrypted AI credentials and user-specific studio state.
