# Starter packs and fonts

## Workflow

Run `php bin/setup.php` once after this update to apply the additional starter/font migration. Existing accounts, pages and appointments are preserved.

Open Website → Starter packs. Four original layouts are included:

- Atelier: navy/brass group-practice layout with a split photographic masthead.
- Clarity: blue/white editorial general-practice layout, full-width hero and labelled information sections.
- Linden: centred sage/cream family-practice layout with serif typography, reception image and overlapping hours panel.
- Forma: an original ink/stone hybrid using asymmetric photography, ruled navigation links and editorial spacing.

The supplied reference screenshots informed composition, palette and density. They were not imported as templates, and no source-site branding, content or photographs are redistributed.

Choose Preview to inspect the full layout, or enter a website name and click Use starter. This creates ten unpublished pages: Home, About, Services, Contact and Calendar, in German and English. The visual editor edits the actual HTML; CSS and chosen fonts are loaded in the editor canvas. Existing pages are not overwritten. Up to 20 saved starter sites are supported in this local demo.

Open a saved starter site to edit or preview pages. Draft preview is authenticated and private; anonymous requests and the public content API cannot see unpublished pages. Publish starter site explicitly publishes the ten pages and switches the main site to their aliases. Existing pages and appointments are retained. Restore original demo deactivates the starter mapping; saved starter content remains. Publishing another saved starter switches the mapping. New content inherits the active starter's header/footer/style association. Edit Navigation menus to adjust menu labels and destinations; Calendar manages appointment availability.

The existing booking engine is reused. No extra booking database/account is created per starter. All saved starters in this single-practice demo share availability and bookings; this is not tenant isolation. Starter display-hours text initially matches demo defaults and is editable content; update that text yourself if calendar availability changes. It is intentionally labelled illustrative. Dummy addresses and generated photos must be replaced before real use.

## Fonts

Website → Fonts accepts local WOFF2 uploads (2 MB each, 20 files / 20 MB total). Choose heading and body fonts, or restore Starter default to preserve each layout's pairing. Built-ins: system sans-serif, Georgia, Geist Sans and Inconsolata. No arbitrary remote URL fetching or stylesheet imports.

Only administrators upload/select fonts. Uploads require CSRF and a license acknowledgement, validated extension, WOFF2 signature/header/size checks, hashed filenames, a quota lock and private storage under var/font-library. The public font handler emits font/woff2 and nosniff; it never executes uploaded data. Selection only accepts built-in IDs or registered content hashes. Structural validation does not replace a full OpenType sanitizer; use trustworthy licensed fonts, keep browser/font parsers updated, and consider OpenType Sanitizer before allowing uploads by untrusted administrators in a real product.

Uploaded fonts and settings are runtime state, excluded from the source ZIP and Git. Font CSS uses server-generated family names rather than user-supplied CSS. Custom font uploads are public website assets when used; do not upload confidential files. Built-in fonts retain their license notices.

## Refined defaults
Setup installs and activates a fresh Linden default once; existing sites and edits remain. Later setup runs preserve the selected site. New packs have five pages per language, including a calendar landing page linked to working booking. Existing starter records are retained as-is: create a new starter to obtain the revised inner pages and footer. The Footer tab edits certificate images, hours and links per page. Certificate SVGs are explicitly fictional placeholders, not real endorsements. Top-bar social icons point to the demo contact section until real profile links are supplied. All front-end body sizes scale from 14px mobile to 16px desktop; headings follow 18/20px minimum tiers. Admin and editor labels have a 14px floor.

Linden is also rendered on initial public core routes when no starter has been activated. Setup persists the editable Linden pages. Forma uses a viewport-height, full-bleed photographic hero; its deliberate background crop is distinct from the contained editorial images in other packs. The header shows only the alternate language and retains the current section.

## Practice default
Practice / Praxis Morgen is the initial default, inspired by the layout of the prior client project but rebuilt with fictional branding, original copy and generated team imagery. No client photographs, name, addresses, external booking credentials or database are included. Four alternative starters remain selectable. Setup creates an editable ten-page bilingual Practice site once, preserving previous content; first-load core routes and booking use its presentation before activation.

Typography update: locally bundled Inter is the project default. Desktop body copy is 18px, navigation 20px uppercase, H1 20px and H2 18px, with a 14px UI floor. Practice hero uses the unfiltered image, left copy and right contact rail. Desktop image is top-aligned and fills the width; viewport height can crop its lower edge rather than heads. Footer day/time columns use a fixed 16px gap and navigation arrows follow labels with 10px spacing.

## Image-led Practice revision
The default now follows the supplied composition across Home, About, Services and Contact: contained split photograph/pale-blue hero, image service cards, practitioner/team sections, detailed service panels and a dedicated reception contact page. Desktop navigation is 16px and stays beside the logo; a four-column demo/telephone/email/social utility strip replaces the old banner. The utility strip becomes two columns on mobile. Buttons use dark blue on white panels and white on blue panels, with a consistent inverse hover. New original reception/consultation WebP images are documented in GENERATED-ASSETS.md.
