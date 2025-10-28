# FP Privacy & Cookie Policy – QA Checklist

Use this checklist to verify the 0.1.1 release across single and multisite environments.

## Activation & Provisioning
- [ ] Activate on a single site; confirm consent table creation, options population, and policy pages per active language.
- [ ] Network activate on multisite; verify each site registers tables, options, scheduled cleanup, and generated policy pages.
- [ ] Create a new site via `wpmu_new_blog`; ensure automatic provisioning runs.

## Banner & Modal
- [ ] Test floating and bar layouts (top/bottom) across languages (e.g., it_IT, en_US).
- [ ] Verify Accept All, Reject All, and Preferences buttons (including focus management and keyboard interactions).
- [ ] Confirm preview mode renders the banner for administrators without setting cookies or logging events.
- [ ] Validate consent revision bump triggers re-display for visitors with prior consent.

## Consent Logging & Analytics
- [ ] Trigger accept/reject/granular events; ensure entries appear in **Privacy & Cookie → Consent log** with hashed IPs and JSON states.
- [ ] Use filters (text/event/date) and expand JSON details; confirm pagination works.
- [ ] Execute CSV export from the admin UI and via WP-CLI (`wp fp-privacy export --file=...`).
- [ ] Confirm daily cron (`fp_privacy_cleanup`) purges records older than the configured retention.

## dataLayer & Consent Mode
- [ ] Inspect network/devtools to ensure `gtag('consent','default', ...)` fires on load with default signals.
- [ ] Change preferences; confirm `gtag('consent','update', ...)`, `dataLayer.push({ event: 'fp_consent_update', ... })`, and `fp-consent-change` CustomEvent payloads.

## Policy Generator
- [ ] Run detector-based regeneration after enabling/disabling sample integrations (e.g., GA4 script, Meta Pixel, YouTube embed) and review diff output.
- [ ] Validate generated privacy/cookie policies include controller info, categories, services table, consent mode mapping, and rights sections.
- [ ] Confirm `fp_privacy_policy_content` / `fp_cookie_policy_content` filters modify the rendered HTML as expected.

## Admin Experience
- [ ] Adjust palette controls; verify live preview updates and AA contrast checks surface warnings when thresholds fail.
- [ ] Import/export settings via Tools (JSON) and observe success/error notices.
- [ ] Review Quick Guide content for shortcodes, blocks, hooks, and legal disclaimer.

## Shortcodes & Blocks
- [ ] Insert each shortcode (`[fp_privacy_policy]`, `[fp_cookie_policy]`, `[fp_cookie_preferences]`, `[fp_cookie_banner]`) on a page; verify output and conditional asset loading.
- [ ] Add the four Gutenberg blocks and confirm editor previews plus front-end rendering.

## Integrations & Extensibility
- [ ] Test REST API endpoints (`/fp-privacy/v1/consent`, `/fp-privacy/v1/consent/summary`) with proper nonces/capabilities.
- [ ] Execute each WP-CLI command (`status`, `recreate`, `cleanup`, `export`, `settings-export`, `settings-import`, `detect`, `regenerate`).
- [ ] Validate exporter/eraser callbacks integrate with WordPress personal data tools.

## Accessibility & Performance
- [ ] Navigate banner/modal entirely via keyboard (Tab/Shift+Tab, Escape to close).
- [ ] Inspect inline CSS variables to confirm palettes load only when banner assets enqueue.
- [ ] Ensure no console errors or blocking scripts arise from banner or admin assets.

Document findings per environment and attach logs/exports where relevant.
