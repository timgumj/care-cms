# Security and responsible use

Use fictional data only. This repository ships no accounts, default passwords, database snapshots, private email credentials, deployment keys or client images. `bin/setup.php` generates a unique local APP_SECRET; `.env.local`, vendor/, var/, databases and uploads are ignored. Administrator creation uses a hidden password prompt, or loopback-only development setup.

Inherited controls: password hashing, login throttling, CSRF, email verification with expiring single-use codes, expiring hashed reset links, role checks, session idle/absolute timeouts, account/session revocation, raster upload validation/re-encoding/quotas, HTML sanitization, recoverable content trash and security headers. A non-obvious admin path is not an authentication control; this demo uses /manage.

New boundaries: public API returns published non-trashed content only; explicit serializers; read-only CORS; booking data admin-only; CSRF on booking/settings/cancellation; rate limits; atomic overlap validation; hashed cancellation tokens. No secret belongs in frontend code.

Email is delivered to a local Mailpit inbox by default. This is a development tool, not a production security boundary. EMAIL_2FA_ENABLED=1 exercises MFA locally. Production enforces email MFA and TLS-capable SMTP; configure real credentials privately. Do not expose Mailpit or development setup/debug mode publicly.

Before deployment: APP_ENV=prod, APP_DEBUG=0, unique 32+ byte APP_SECRET, HTTPS, verified DEFAULT_URI, trusted_hosts matching your own domain, secure SMTP, writable var outside document root, document root public/ only, uploaded files non-executable, explicit API origin, backups and a retention/deletion policy. Configure shared rate-limit/locking storage before horizontal scaling. Demo SQLite is not a multi-server datastore.

Admin/editor script CSP remains report-only because the visual editor needs further compatibility hardening. Public pages use an enforced baseline. Public anti-spam is basic; invitations, booking confirmation and email abuse need further production controls. No independent penetration test has been performed.

Report suspected vulnerabilities privately to the repository maintainer through a private security advisory if enabled. Do not include passwords, tokens, personal details or live exploit data in public issues.

## Starter and font boundaries

Starter creation/publication and typography settings require ROLE_ADMIN and CSRF. Starter content is generated from a fixed local catalog; no uploaded templates, arbitrary PHP, downloaded ZIP packs or remote font URLs are executed. Pages begin unpublished; only a confirmed publication activates aliases. Authenticated editors can preview starter drafts, while anonymous/API queries require publication. Existing content is preserved and switching is reversible. Font uploads are administrator-only with structural WOFF2 validation, size/count quotas, hashed private storage and explicit public font responses; full OpenType sanitization is not implemented. See docs/STARTER-PACKS.md.
