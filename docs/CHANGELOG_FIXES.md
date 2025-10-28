# Fix Changelog

| ID | File | Line | Severity | Fix summary | Commit |
| --- | --- | --- | --- | --- | --- |
| ISSUE-001 | fp-privacy-cookie-policy/src/Utils/Options.php | 18,701-752 | High | Preserve manual policy content by tracking managed pages and skipping overwrites. | fix(ux): protect manual policy edits (ISSUE-001) |
| ISSUE-002 | fp-privacy-cookie-policy/src/REST/Controller.php<br>fp-privacy-cookie-policy/assets/js/banner.js | 138-200<br>521-614 | High | Refresh consent REST calls with a new nonce when cached pages send expired tokens. | fix(rest): refresh consent nonce when expired (ISSUE-002) |
| ISSUE-004 | fp-privacy-cookie-policy/src/Consent/ExporterEraser.php | 58-273 | High | Require consent ID mappings before registering GDPR tools and provide actionable guidance for email requests. | fix(compliance): gate exporter on consent-id mapping (ISSUE-004) |
| ISSUE-003 | fp-privacy-cookie-policy/src/Utils/Options.php | 643-645 | Medium | Preserve the fp_privacy_options autoload flag when bumping consent revisions. | fix(perf): keep consent options non-autoloaded (ISSUE-003) |
