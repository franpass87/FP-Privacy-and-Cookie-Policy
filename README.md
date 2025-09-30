# FP Privacy and Cookie Policy

Source for the WordPress plugin located in [`fp-privacy-cookie-policy/`](fp-privacy-cookie-policy/).

## Development workflow
- Install the plugin in a local WordPress environment (e.g. `wp-env`, Lando, or a vanilla wp-env install).
- Activate **FP Privacy and Cookie Policy** and visit **Privacy & Cookie → Settings** to configure banner copy, palette, and Consent Mode defaults.
- Use the live preview panel on the settings screen to validate copy and palette contrast while editing.
- When policies drift from detected services, the settings screen displays a stale snapshot notice that links to the **Tools** tab.

## Packaging
- Run `bin/package.sh` from the repository root to create a clean distributable ZIP under `dist/`.
- The script removes development artifacts and enforces the “no binaries/minified assets” rule.

## Documentation
Refer to the plugin's [README](fp-privacy-cookie-policy/README.md) and [readme.txt](fp-privacy-cookie-policy/readme.txt) for feature documentation and changelog details.
