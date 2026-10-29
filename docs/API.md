# Published content API

GET /api/v1/pages?locale=en-AT
GET /api/v1/pages?locale=de-AT&path=/services/

Returns `version` and at most 100 `items`, ordered by path. Each item contains `path`, `type` (`page` or `article`), `locale`, `translationKey`, `title`, `description`, sanitized `html`, and ISO `updatedAt`.

Only published, non-trashed content is returned. No users, email addresses, booking details, authentication tokens or write endpoints are exposed. Content is intentionally public: CORS is browser policy, not authentication.

An exact `API_ALLOWED_ORIGIN` receives GET/OPTIONS CORS headers. Credentials are never allowed. Set the value to your frontend's full origin, e.g. `http://127.0.0.1:5173`. Avoid wildcard CORS. Native/server clients can request public content independently.

## Separate frontend

Start the CMS on port 8020. In a second terminal:

    php -S 127.0.0.1:5173 -t examples/frontend

Open http://127.0.0.1:5173. This deliberately different design loads the same CMS content without admin credentials. Its JavaScript uses textContent rather than injecting HTML. For React/Svelte HTML rendering use an audited sanitizer and an appropriate CSP, even when consuming the sanitized HTML field.

This API exposes HTML, not a portable layout-block schema. An AI-generated frontend can design its own layout using titles/descriptions/content; it cannot automatically convert GrapesJS layouts into framework components. Forms and booking stay on the CMS host; cross-origin booking writes are intentionally unsupported.

## Prompt for an AI coding tool

Build a bilingual practice frontend using the documented public Care CMS API. CMS_BASE is a public environment setting. Fetch /api/v1/pages?locale=en-AT or de-AT. Use translationKey to pair translations. Display only published content returned by the API, handle loading/empty/error states, use semantic accessible markup, and preserve page paths. Do not embed credentials, create write endpoints, or fetch account/calendar data. Link appointment buttons to CMS_BASE/booking/. Render text safely; if rendering html, sanitize it. Respect the declared CORS origin. Provide setup instructions and tests.

Do not execute arbitrary AI-generated code inside the CMS or give an AI tool production passwords.

## Starter sites

When a starter is active, the published API uses that site's content and normalised public aliases (for example /en/services/). Draft starter pages are never returned. Relative images in HTML belong to CMS_BASE; rewrite relative src URLs against that address in an independent frontend. The supplied separate frontend has its own design and fonts. CMS font selection and starter styles apply to the rendered CMS website/editor, not automatically to an independently styled React/Svelte frontend. If reusing HTML layouts, explicitly integrate the starter stylesheet and address cross-origin font hosting for your own frontend origin. No admin credentials are needed for public content.
