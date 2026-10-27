# Tajira

Tajira is a Laravel/MySQL ecommerce application for a Kenyan networking equipment retailer. It includes a server-rendered storefront, a custom administration area, structured catalogue data, checkout, stock reservations, Kenyan payment integrations (M-Pesa Daraja adapter and bank transfer), quotations, editorial tooling, technical SEO features, and a staged catalogue discovery/import pipeline.

This repository is a working implementation for review and deployment preparation. It is **not** a claim that the business, catalogue, policies or payment arrangements are production-approved. Owner-supplied business facts, credentials, legal review, image rights, and verification/commercial approval of the imported catalogue are still required.

## Verified environment

The application was built and tested in this environment:

| Component | Version |
|---|---|
| Laravel | 12.70.0 |
| PHP | 8.2.12 (CLI, Windows/XAMPP) |
| Database | MariaDB 10.4.32, MySQL-compatible |
| Node.js | 22.19.0 |
| Composer | 2.8.11 |
| Redis | Not available in the build environment; database cache/queue/session used locally |

`composer.json` requires PHP `^8.2` and Laravel `^12.0`. The production example environment is configured for Redis, which must be installed and verified on the target server.

## Quick start (local, Windows/XAMPP)

```powershell
# 1. Install dependencies
composer install
npm install

# 2. Create the MySQL database
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE tajira CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Copy and configure the environment
Copy-Item .env.local.example .env
php artisan key:generate

# 4. Build, migrate, seed and link storage
npm run build
php artisan migrate --seed
php artisan storage:link

# 5. Serve (run a queue worker in a second terminal for queued email/jobs)
php artisan serve
php artisan queue:work
```

Open `http://127.0.0.1:8000`. The administration area is at `/admin`.

### Local demo administrator accounts

Seeded only when `APP_ENV` is not `production` (or when `TAJIRA_SEED_DEMO_USERS=true`). The demo password defaults to `admin123`, overridable with `TAJIRA_SEED_ADMIN_PASSWORD`.

| Role | Email |
|---|---|
| Super Administrator | `admin@demo.com` |
| Catalogue Manager | `catalogue@tajira.test` |
| Content Editor | `content@tajira.test` |
| Order Manager | `orders@tajira.test` |

**Demo credentials are intentionally weak and must be changed immediately in any shared environment. Enable MFA.** For production, create administrators explicitly:

```bash
php artisan tajira:create-admin admin@example.com --name="Super Admin" --role=super_admin
```

## What is implemented

### Storefront
- Responsive, original Blade/Tailwind/Alpine storefront with accessible header, mega menu, mobile drawer, search autocomplete, product cards and breadcrumbs.
- Home, category, brand, product listing, search, product detail, compare, wishlist, cart, guest checkout, order tracking, invoices, account pages, quotation requests, contact, guides and CMS pages.
- Server-rendered product content and specifications; key purchasing information does not depend on JavaScript.
- Currency in KES, configured tax treatment, stock status, delivery and warranty placeholders.
- Genuine moderated reviews only; pending reviews are never shown or included in structured data.
- Quote-only products show “Request price” and omit Offer structured data.

### Catalogue and administration
- Brands, nested categories, products, variants, typed attributes/values, images, documents, related/accessory/compatible products, FAQs, reviews, stock movements and reservations.
- Custom admin dashboard with server-side role enforcement: Super Administrator, Catalogue Manager, Content Editor, Order Manager.
- Product publication guardrails, admin preview, slug redirect suggestions, content revisions, audit logs, media library, CSV import/export, bulk-safe reruns, and source verification notes.
- Admin MFA (TOTP) with setup, challenge, disable and emergency reset command.
- Searchable/filterable structured attributes; comparison attributes.

### Catalogue discovery and import
- Public sitemap discovery baseline: **573 unique `/product/` URLs** observed on 20 October 2026.
- Operational run completed: **573 pages fetched, 0 failures, 543 unique products added, 30 duplicate listings flagged**. The imported records are published as **quotation-only, noindexed catalogue entries** pending verification.
- Resumable discovery/fetch batches, rate limiting, host allowlisting, SSRF checks, redirect validation, content-size limits, checkpoints, per-row errors, and staging.
- Duplicate flagging using normalised brand/model/MPN and existing product identifiers.
- Reconciliation dashboard showing discovered, imported, duplicates, pending verification, failed, unresolved, and coverage by brand/category.
- Approval creates a **draft** product with no competitor description, no competitor price and no invented specs. Publication still requires verification and commercial approval.
- Owner CSV import with dry run and row-level validation; existing SKUs are never overwritten.
- Authorised manufacturer-image CSV importer records source URL, credit and rights note, downloads safely, and re-encodes to WebP. See `docs/data/manufacturer-images.csv` and `php artisan manufacturer-images:import ... --dry-run`.

### Checkout, inventory and payments
- Guest/account checkout with Kenyan counties, delivery/pickup, configurable zones and charges, coupons, tax, and order confirmation email.
- Server-side amount/currency validation and preview-only shipping calculations.
- M-Pesa Daraja STK push adapter with token caching, callback matching, status query verification, idempotent payment events, reconciliation job, and safe failure handling.
- Bank transfer with manual admin reconciliation; card gateway adapter intentionally disabled until an approved provider is implemented and tested.
- Database transactions and row locking for reservations; reservation expiry and restocking jobs.
- Late payments after cancellation/expiry are recorded as `payment_exception` for manual review instead of silently committing stock.
- Invoices/quotation PDFs. No eTIMS compliance is claimed; no eTIMS integration is present.

### SEO, content and feeds
- Stable `/products/{slug}`, `/categories/{slug}`, `/brands/{slug}`, `/guides/{slug}` URLs.
- Canonical URLs, robots directives, Open Graph/Twitter metadata, XML sitemap index and child sitemaps, breadcrumbs, redirects without chains, useful 404/410 pages.
- Filter/sort/search indexation policy enforced; paginated pages self-canonical; approved collection pages only.
- Server-rendered JSON-LD for Organization, WebSite, BreadcrumbList, Product/Offer, ProductGroup variants, Article and genuine reviews. Offer data is omitted when no approved price exists.
- GA4 consent-aware loader and ecommerce events (deduplicated purchase), Merchant Center feed restricted to approved prices and real images, Search Console verification setting.
- Editorial revisions, sanitised HTML, source notes, link audit and SEO issue reports.
- Search Console/GA4/Merchant IDs are configuration placeholders; no ranking or rich-result eligibility is promised.

## Seeded content and catalogue honesty

The seeder creates:
- The category/brand taxonomy and structured attribute definitions.
- **Two published, quotation-only products** with specifications checked against manufacturer pages:
  - TP-Link TL-SG108 (source recorded in the product's internal notes).
  - MikroTik hAP lite TC RB941-2nD-TC (source recorded).
- Ten additional **draft** starter records with no approved price, no competitor copy, and no verified specifications. They are not publicly visible.
- Original starter guides and policy templates. Policy pages visibly state that Tajira must review and approve them before launch.
- A pickup method only. Delivery charges are intentionally not invented; an administrator must configure approved zones, charges and lead times.

The reference catalogue audit found 573 public product URLs. All 573 pages were fetched; 543 unique products were added as **quotation-only, noindexed catalogue entries**, while 30 probable duplicate listings were left out pending review. This brings the database to **555 products**: 545 published (543 imported quotation-only records plus 2 verified quote-only starter products) and 10 unpublished starter drafts. No imported record has a competitor description, image, price, stock level or invented specification. Two verified manufacturer images have been added (TP-Link TL-SG108 and MikroTik hAP lite TC); all other imported records use a pending-image placeholder until an approved manufacturer image URL is supplied. Manufacturer verification, image rights, Tajira pricing, stock, category review and deliberate publication remain required. See [docs/catalogue-audit.md](docs/catalogue-audit.md).

## Testing

```bash
php artisan test
```

The suite runs against a dedicated MySQL database (`tajira_test`) configured in `phpunit.xml`. Create it first:

```sql
CREATE DATABASE tajira_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Current result: **44 tests passing (205 assertions)**, covering pricing/tax, checkout, stock reservations and expiry, overselling, payment callbacks/idempotency/late payment, admin permissions/MFA, publication workflow, redirects, SEO/canonical/sitemaps, imports/CSV dry runs, reviews, shipping/coupons, storefront flow and admin screen rendering. See [docs/testing.md](docs/testing.md) for the exact verification record and what was not tested.

## Documentation

- [Catalogue audit and limitations](docs/catalogue-audit.md)
- [Architecture and database diagram](docs/architecture.md)
- [URL and indexation policy](docs/seo-policy.md)
- [Deployment, backup and maintenance](docs/deployment.md)
- [Administrator and content workflow guide](docs/admin-guide.md)
- [Testing and verification record](docs/testing.md)
- [Performance notes and targets](docs/performance.md)
- [Completion checklist](docs/completion-checklist.md)

## Known limitations / required before production

1. Replace all placeholder business facts: legal name, phone, WhatsApp, email, address, domain, tax details, social links and policies. Never treat `.env.example` placeholders as real facts.
2. Have legal counsel review privacy, terms, returns, warranty and delivery policies.
3. Configure and test M-Pesa Daraja in sandbox then production with real credentials and callback URL; test callback IP allowlisting and reconciliation.
4. Decide whether to enable bank transfer; enter approved bank details.
5. Configure delivery zones/charges/lead times and approved pickup instructions.
6. Verify every product against manufacturer documentation, secure image rights, set Tajira prices/stock/warranty, then publish deliberately.
7. Review and verify the 543 imported quotation-only records, resolve the 30 duplicate flags, add approved manufacturer images/specifications/prices/stock, then change them from noindexed quote-only records to fully published listings. The catalogue is populated but not verified or commercially approved.
8. Install Redis for production cache/queue/session, run queue workers and the scheduler under a process manager.
9. Run staging access protection, HTTPS/HSTS, backup/restore tests, dependency audits and real-device Lighthouse/Core Web Vitals checks on the target hosting.
10. No eTIMS or tax compliance claim is made. No card payment provider is implemented.
11. Structured data is generated from approved data, but rich-result eligibility and Google rankings are not guaranteed.

## Repository layout

```
app/
  Console/Commands/       Operational and import commands
  Http/Controllers/       Storefront, Admin, payment callback
  Http/Middleware/        Admin, MFA, redirects, staging, security headers
  Jobs/                   Payment reconciliation
  Mail/                   Transactional mailables
  Models/                 Catalogue, commerce, content, import, audit
  Observers/              Cache/sitemap invalidation
  Services/               Pricing, cart, inventory, checkout, payments, SEO, imports
config/tajira.php         Business, tax, payments, SEO, import configuration
config/permissions.php    Role/ability matrix
database/migrations/      Full schema
database/seeders/         Starter taxonomy/content (see honesty note)
docs/                     Audit, architecture, SEO, deployment, admin, testing
resources/views/          Blade storefront and admin UI
routes/web.php            Storefront routes
routes/admin.php          Administration routes
tests/                    Feature and unit tests
```
#   t a j i r a  
 