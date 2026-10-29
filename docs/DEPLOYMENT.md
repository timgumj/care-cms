# Deployment / publication checklist

This demo is SQLite-first and local-first. GitHub can host the source and the separate static example frontend; GitHub Pages cannot run the PHP CMS. For an online CMS demo use PHP hosting with a private writable data directory and HTTPS. Do not reuse the client website's database, SMTP, domain, deployment location or secrets.

Before GitHub publication run `python bin/package.py`, inspect the source archive, and run tests/CI. The folder has no client Git history. Do not `git add -f` ignored files. Never publish .env.local, .env.local.php, vendor/, var/, database files, mailboxes, logs or real uploaded content. Keep the source-only archive outside the project. Configure your Git author identity appropriately if privacy matters.

For production use fresh Composer installation with --no-dev --optimize-autoloader; APP_ENV=prod, APP_DEBUG=0; private APP_SECRET/SMTP/DEFAULT_URI/database settings; explicit production trusted_hosts; document root public/ only. Install assets, run migrations/backups through your deployment process, set var/ write permissions for PHP, and deny script execution in uploads. Migrations are currently tested on SQLite only. No administrator or shared password is preloaded.

Keep a publicly hosted portfolio demo read-only or regularly reset it and restrict privileged demo access. Arbitrary visitors must not receive an administrator account that can invite users, send emails, upload content or inspect others' bookings. Do not expose the local Mailpit inbox publicly. Use fictional content and mail recipients in any shared demo. Add abuse monitoring and retention cleanup before enabling public booking.

Review SECURITY.md and architecture limitations before any real client deployment. This is an engineering portfolio, not a ready-to-operate medical booking product.
