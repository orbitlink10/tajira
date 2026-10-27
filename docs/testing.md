# Testing and verification record

## Test environment

- Laravel 12.70.0, PHP 8.2.12
- MySQL-compatible MariaDB 10.4.32
- Dedicated test database: `tajira_test`
- Cache/session: array; queue: sync; mail: array
- Run date: 20 October 2026
- Command: `php artisan test`

**Actual result:** **44 tests passed, 205 assertions**, duration approximately 18 seconds.

## What is covered

### Commerce and money
- Integer minor-unit pricing; inclusive/exclusive tax.
- Percentage/fixed coupons, minimum subtotal and maximum discount caps.
- Cart discount allocation and mixed tax lines.
- Delivery eligibility by county, minimum order and server-side validation of the selected method.
- Guest cart add/update, quote-only products rejected from cart.

### Checkout and inventory
- Order placement in a transaction with price recalculation and stock reservation.
- Overselling rejection when stock is already reserved.
- Payment confirmation commits stock and decrements reservations.
- Idempotent manual/payment confirmation.
- Expired reservations release stock and cancel unpaid orders.
- Late payment after cancellation is recorded as `payment_exception` without committing stock.

### Payments
- M-Pesa callback amount/currency matching.
- Server-side STK status query before committing success (HTTP faked for tests).
- Duplicate callback idempotency via unique payment events.
- Amount mismatch does not mark the order paid.
- Pending/uncertain query path queues reconciliation (implementation covered; live provider not tested).

### Admin, permissions and publication
- Customer denied admin access.
- Catalogue/content/order role boundaries and server-side 403s.
- MFA challenge enforcement, valid TOTP, invalid TOTP.
- Product publish guardrails (price/quote, description, brand/category).
- Unpublishing removes public access.
- Slug changes create redirects and old paths resolve.
- Admin screen rendering smoke test across catalogue, sales, content, marketing, SEO, imports, settings, users, media, audit and collections.
- Admin product creation/update with category pivot, structured attribute value, status transition, slug redirect and storefront visibility.

### SEO and content
- Product initial HTML includes H1, canonical, JSON-LD Product/Offer and price.
- Quote-only products omit Offer JSON-LD.
- Sitemap excludes drafts and noindex products.
- Search noindex; robots disallows admin.
- Paginated category self-canonical.
- Private pages noindex and no-cache.
- Approved collection page is indexable and appears in its sitemap.
- Review moderation and rating recalculation; pending reviews not shown.
- Redirects/404 fallback.

### Catalogue import
- Product page parser extracts JSON-LD/table/price/brand/category/description observations.
- Approval creates a draft with no competitor description, price or invented specification.
- CSV dry run validates without creating products; second run creates drafts.
- Parser/approval tests do not require network access.

## Manual HTTP smoke checks (local)

Verified with `php artisan serve` and HTTP requests:

- `/` — 200
- `/products` — 200 after fixing filter-brand data
- Product detail (published quote-only) — 200
- `/categories/network-switches` — 200
- `/brands` — 200
- `/guides` — 200
- `/pages/delivery` — 200
- `/cart` — 200
- `/checkout` — 200 with a cart, redirect to cart when empty
- `/quote` — 200
- `/compare` — 200
- `/contact` — 200
- `/robots.txt` — 200, disallows admin
- `/sitemap.xml` and `/sitemaps/products.xml` — 200 XML
- `/admin/login` — 200
- Authenticated admin screens — covered by AdminSmokeTest (all 200)

The Vite production build succeeded. GD WebP encoding was verified (`imagewebp` available and wrote a valid file).

## What was not tested / blocked

These are honest gaps, not claims:

- **Live M-Pesa Daraja sandbox/production:** no Safaricom credentials were available. Tests used HTTP fakes to validate the callback/query logic. A real STK push, real callback signature/IP conditions, and production reconciliation still need sandbox and production verification.
- **Card payments:** no provider adapter exists; option disabled.
- **SMTP/email delivery:** mailer was array/log. Mailable views render in tests, but real inbox delivery, bounce handling and SPF/DKIM/DMARC are not verified.
- **Full 573-URL catalogue import:** the operational run was executed (573 fetched, 0 failures, 543 quotation-only catalogue records, 30 duplicate flags). Automated tests do not verify each imported record; manufacturer verification, commercial data and image rights remain pending.
- **Real-device Lighthouse/Core Web Vitals:** not measured. No performance scores are claimed.
- **Redis cache/queue/session:** this environment lacks the Redis extension. Production Redis behaviour is not verified here.
- **Multi-process concurrency and overselling under real load:** row locking logic is tested sequentially. A load test with concurrent processes against MySQL is pending.
- **Backup and restore:** documented but not executed against a production-sized database.
- **HTTPS/HSTS, CDN, WAF, real domain and Search Console:** not verified.
- **Image rights / product image accuracy:** placeholders are used; no manufacturer images were published.
- **Legal/tax review:** policy templates and tax settings are not legally approved; no eTIMS integration.
- **Browser matrix/accessibility audit:** keyboard/semantic patterns are implemented, but no external WCAG 2.2 AA audit or screen-reader matrix was completed.
- **Search autocomplete/rate-limit edge cases:** basic implementation exists; not exhaustively load tested.

## Acceptance commands

```bash
php artisan test
php artisan route:list
php artisan view:cache
npm run build
composer audit
php artisan about
```

Run `composer audit` and dependency updates on a schedule. Re-run the suite after any upgrade.

## Important test-database note

The suite uses MySQL, not SQLite, because the application targets MySQL/MariaDB semantics (locking, raw price expressions). Create `tajira_test` before running tests. Never point tests at a production database.
