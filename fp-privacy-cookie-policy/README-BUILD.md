# Build & Release Guide

## Prerequisites

- PHP 8.2 or compatible runtime with the `php` CLI available.
- Composer 2.x.
- Bash shell with common Unix utilities (`rsync`, `zip`).

## Local build workflow

Bump the plugin version (patch by default) and generate a distributable archive:

```bash
bash build.sh --bump=patch
```

Set an explicit version instead of bumping:

```bash
bash build.sh --set-version=1.2.3
```

Provide a custom archive name:

```bash
bash build.sh --set-version=1.2.3 --zip-name=fp-privacy-cookie-policy-1.2.3
```

The script prints the final version, the ZIP file path inside `build/`, and the top-level files included in the archive.

## GitHub Action release

Push a Git tag that starts with `v` (for example `v1.2.3`). The `Build plugin ZIP` workflow will:

1. Install Composer dependencies without development packages.
2. Create a cleaned copy of the plugin under `build/`.
3. Generate a ZIP archive and upload it as a build artifact named `plugin-zip`.

Download the artifact from the workflow run and upload it to your WordPress site or marketplace of choice.
