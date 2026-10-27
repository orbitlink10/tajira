# Performance notes and targets

## Targets

Real-user Core Web Vitals, measured at the 75th percentile on mobile:

- LCP ≤ 2.5 s
- INP ≤ 200 ms
- CLS ≤ 0.1

Repeatable mobile Lighthouse diagnostic target: 90+ performance on representative homepage, category, product, search, cart and checkout pages under documented conditions.

**No real-user or Lighthouse measurements were taken in this build.** These targets are design requirements, not claims of achieved scores. Lab scores are diagnostics, not proof of field Core Web Vitals.

## Implemented measures

### Server-rendered storefront
- Blade HTML contains product names, prices, stock, descriptions, specs, FAQs, breadcrumbs with real `<a>` links and JSON-LD.
- No JavaScript is required to browse categories/brands/products, read specifications, add to cart, or complete checkout forms. Alpine only enhances menus, gallery, autocomplete, shipping refresh and consent.
- No heavy slider, autoplay video, intrusive popup, or client-side product rendering.

### Assets
- Vite production build, minified CSS/JS.
- Tailwind CSS with a small custom theme; no UI framework bundle.
- System font stack; no external font requests.
- Icons are inline SVG (no icon font).
- Alpine is the main JS dependency.
- CDN-ready static assets under `public/build` and `public/images`.

Last local production build sizes (will vary slightly with content/classes):

- CSS: ~66 kB minified, ~13.7 kB gzip
- JS: ~83.9 kB minified, ~29.0 kB gzip

### Images
- Uploaded images are re-encoded to WebP, max 1800 px, quality 82.
- Product images have explicit width/height from the image record.
- Primary product image is `loading="eager"` with `fetchpriority="high"` and is not lazy-loaded.
- Below-the-fold images use native lazy loading and async decoding.
- Placeholder images are SVG and only used when no authorised image exists.
- OG default image is a generated 1200×630 PNG.
- No manufacturer images were downloaded/published.

### Database and queries
- Indexes on slugs, status/published_at, brand/status, primary category/status, attributes, orders, payments, reservations, imports.
- Eager loading in storefront product/category/brand/search paths.
- Pagination for catalogue/search/guides/admin lists.
- Integer money arithmetic, no float money.
- Search uses relevance scoring in MySQL (exact SKU/model prioritised) and structured attribute filters. A dedicated search engine is a documented future upgrade, not a requirement.
- Cache:
  - menu categories, active brands, footer pages, homepage categories/featured/sections
  - settings
  - sitemaps and merchant feed
  - cache invalidation via model observer/settings flush
- Personalised cart/checkout/account/payment pages are never publicly cached and are noindexed.

### Operations
- Queued order/quotation/payment emails, payment reconciliation, image-related processing work.
- Scheduler for reservation expiry, payment reconciliation and scheduled publication.
- Production instructions include OPcache, config/route/view caching, queue workers and a process manager.
- `/up` health endpoint is provided by Laravel.

## How to measure before launch

1. Seed/import a representative catalogue on staging. The current database has 555 products (545 published quote-only records, 10 drafts). The imported quote-only records still need verified content and approved manufacturer images.
2. Use Chrome DevTools mobile emulation with documented throttling (e.g. Slow 4G, 4× CPU) or Lighthouse CI.
3. Test at least: homepage, top category, a product with gallery/specs, search results, cart, checkout, order confirmation.
4. Record LCP element, INP interactions, CLS sources, JS/CSS transfer sizes, request count and server response times.
5. Run on real mid-range Android devices on Kenyan mobile networks where possible.
6. Compare field data in Search Console/CrUX after launch (needs traffic/consent).
7. Re-run after adding apps, analytics, chat widgets or large images. Third-party tags can dominate INP/LCP.

## Known risks to watch

- Large product image galleries and unoptimised OG/social images.
- Third-party analytics/advertising tags; keep consent-gated and evaluate performance cost.
- Search autocomplete request volume and database LIKE performance as the catalogue grows.
- Unindexed filter combinations or expensive facet queries on very large categories.
- Admin CSV/import fetches are rate-limited and intentionally slow; run them via queue/commands rather than web requests for large batches.
- Backups and image storage growth.
