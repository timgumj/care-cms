# Verification record

Local checks (29 October 2026):

- All seven migrations applied to a new in-memory SQLite database, including account security and booking tables.
- Booking validation, overlapping-slot rejection, blocked dates, time-zone conversion, hashed cancellation and cancellation replay checks passed.
- Two independent PHP processes competed for the same SQLite slot: exactly one booking committed.
- Actual API controller/ORM queries exclude drafts and trashed records, reject invalid locales, sanitize HTML and restrict CORS headers.
- Account invitation/activation, editor/admin boundaries, session revocation, email MFA expiry/replay/guess/send limits, password reset and HTML sanitization checks passed with fake mailers. No real emails were sent.
- New frontend/booking/calendar Twig syntax parsed; navigation rendered with fictional content.
- PHP syntax, JavaScript syntax and Composer manifest/lock validation checked.
- Source/distribution scans check excluded client names/domains and runtime/private artifacts. This is not a comprehensive independent secret-detection audit.

Windows execution limitation: this attached tool environment reports is_readable/is_writable=false for ordinary files/directories even when actual file operations work. Full Symfony CLI bootstrap and browser end-to-end checks could not run here; application/vendor security checks have not been bypassed. Run `php bin/setup.php` and `php tests/http.php` from a normal terminal with the dev server running, or use the included Linux CI workflow. CI is prepared, not yet run on GitHub. Visual review and real SMTP delivery remain to be completed.

Dependency audit: Symfony Mime was updated from 7.4.19 to 7.4.22 for CVE-2026-45070, and its mbstring polyfill dependency to 1.43.3. Re-run `composer audit --locked` before publishing/deploying; advisories can change.

## Management UI redesign (29 October 2026)

Dashboard and shared frontend toolbar were rendered with fictional Twig fixtures and visually inspected at desktop and 390px mobile widths. Screenshot artifacts are outside the source distribution. The actual anonymous frontend loaded correctly from the local Symfony server; authenticated browser interaction was not available in the inspection session. Toolbar tests check anonymous exclusion, single rendering, page-specific edit destinations, role-specific links and account-label escaping. The actual Symfony authorization listener covers the new content redirect controller and calendar. No authentication checks were bypassed for visual review.

## Starter/font update

Eight migrations applied to an isolated SQLite database. All four designs rendered in German/English, with eight drafts each. Activation, switching, restoration, active API aliases, new-article theme inheritance and original-content preservation were checked. WOFF2 import uses a genuine bundled font as a positive fixture; invalid headers, arbitrary font IDs, unsafe labels, duplicate imports and path traversal were checked. Separate real authorization-listener tests deny editors access to starter selection/publication and font settings. All four desktop designs, the gallery and representative 390px mobile navigation were visually inspected using temporary fictional fixtures. Generated preview files are removed from the source distribution.

## Repository preparation
Current service/template suite passes with eight migrations, five bilingual starters and ten generated pages per starter. About inner gutters, shared archive/single-article theme, and appointment-last navigation were checked against the real anonymous local pages. A release ZIP excludes private configuration, databases, dependencies and runtime uploads. A fresh installer/CI check is required before calling the package a production-ready distribution.
