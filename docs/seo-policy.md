# URL, indexation and structured-data policy

This is the written policy implemented in the application. It is intentionally conservative. It does not promise rankings or rich-result eligibility.

## Canonical URL patterns

| Entity | URL |
|---|---|
| Product | `/products/{product-slug}` |
| Category | `/categories/{category-slug}` |
| Brand | `/brands/{brand-slug}` |
| Guide/article | `/guides/{article-slug}` |
| CMS page | `/pages/{page-slug}` |
| Approved collection | `/collections/{collection-slug}` |
| Sitemap index | `/sitemap.xml` |

Product URLs are independent of category assignments. Changing categories never changes a product URL. A published slug change creates a 301 redirect suggestion/record from the old URL, updates chains that point at the old URL, and prevents self-redirects.

## Status and canonical rules

### Products
- Published, indexable products: `index,follow`, absolute self-canonical.
- Unpublished/draft products: 404 to the public; admin preview is `noindex,nofollow`.
- Published but `is_indexable=false` or robots containing `noindex`: `noindex,follow`; excluded from sitemaps/feeds; canonical is omitted rather than pointing elsewhere.
- Products with `price_on_request=true` or no approved price: no Offer JSON-LD. They remain indexable if approved (useful information and quotation path), unless explicitly noindexed.
- Critical product content is server-rendered in the initial HTML, including inside accessible `<details>` sections.

### Categories/brands
- Base category/brand pages are indexable when active and `is_indexable=true`.
- Filter/sort combinations are `noindex,follow` and canonicalise to the clean base page or self page (pagination retained).
- Only admin-approved collections are indexable filter-like pages. They require an original introduction (40+ characters) and must return a real product selection. Empty approved collections return 404.
- Category/brand canonical overrides are supported for controlled cases.

### Pagination
- Paginated category/brand/search/collection pages are self-canonical (`?page=n` is retained).
- Paginated pages are never canonicalised to page 1.
- Pagination links are real crawlable HTML links.
- Filter/sort parameters are preserved in pagination links but remain noindexed.

### Search
- `/search` is `noindex,follow`; it is not included in sitemaps.
- Autocomplete is a JSON endpoint, rate-limited and `no-store`.
- Search results do not create indexable filter combinations.

### Private and transactional pages
`/admin`, `/account`, `/cart`, `/checkout`, `/login`, `/register`, `/orders`, `/quote`, `/payments`, `/search` and other private routes are noindexed by metadata/header and disallowed in `robots.txt`. `robots.txt` is **not** used as authentication. Cart/checkout/account responses are private and session-controlled.

### Sorting and tracking parameters
- Sorting alone does not create indexable pages. The canonical target is the unsorted clean URL, and the page is noindexed.
- Tracking parameters are not treated as content and are not included in sitemaps or canonicals.
- Internal search query parameters are noindexed.

## Sitemaps

`/sitemap.xml` is an index of:

- pages
- categories
- brands
- products
- articles
- approved collections

Only published, indexable, successful canonical URLs are included. Products with robots noindex, drafts, inactive entities and unapproved filter URLs are excluded. `lastmod` comes from the entity's meaningful `updated_at`; `updated_at` is bumped on product/price/content save. Product image entries use only genuine uploaded images.

## Robots.txt

Generated dynamically:

- Default: allow public content, block private/administration/payment/feed paths and the filter/sort parameters listed above.
- Sitemap reference included.
- Staging/global noindex mode disallows all crawling.
- Staging can also require HTTP Basic Auth and sends `X-Robots-Tag: noindex, nofollow`.

## Structured data

Generated server-side from the same approved data displayed to users:

- `Organization` (name, URL, logo, verified contact/social where configured).
- `WebSite` with SearchAction.
- `LocalBusiness` only when an approved physical address and phone exist.
- `BreadcrumbList`.
- `Product` and `Offer` only with approved prices. Availability is derived from Tajira stock, not competitor stock. GTIN is included only if supplied and 8/12/13/14 digits. MPN/model/brand only if recorded.
- `ProductGroup`/variant relationships when a product has active variants.
- `AggregateRating` and reviews only from approved, genuine reviews; no placeholder or fabricated ratings.
- `Article` for editorial content.
- `FAQPage` only for product FAQs that are actually displayed.
- Return-policy markup only when an approved return window is configured.

No invented ratings, GTINs, prices, stock, certifications, merchant policies or shipping promises. No placeholder Offer is emitted for quote-only products. Validation against Google's current requirements is a deployment task; eligibility is not guaranteed.

## Product lifecycle

- **Temporarily out of stock:** remains published and accessible with accurate availability; related/alternative products are shown. If a product is quote-only, Offer data is omitted.
- **Discontinued with useful content:** may be retained with accurate status and alternatives, or redirected to a genuinely equivalent product.
- **Permanently removed with no equivalent:** an explicit 410 or a useful 404 page is used. Products are never bulk-redirected to the homepage.
- Soft-deleted products disappear from the public catalogue; admins should create a redirect where the URL had value.
- Archived products are not public; an admin should choose retain/redirect/410 deliberately.

## Editorial controls and internal linking

- Titles, descriptions, slugs, social image paths, canonical overrides and robots directives are editable in admin.
- Product publication is blocked until price/quote setting, description, brand and primary category exist. Variant products require variants.
- Product/admin preview is noindexed.
- Revision history is kept for content/product changes; audit logs record administrative actions.
- The SEO report shows missing/duplicate metadata, orphan products, missing images/alt text, missing descriptions/specifications, and broken internal links.
- Link auditing resolves internal links against published routes/content/redirects.
- Slug changes create redirects without chains; manual redirects are validated for loops.
- Content is separated from verification. AI-assisted drafts cannot auto-publish product facts; product publication remains a human action with verification notes.
- No thin city/location pages, keyword-stuffed paragraphs or competitor links are generated.

## Measurement

- Core Web Vitals and Lighthouse are diagnostics only. No real-user measurement is included in this build.
- Search Console verification is a configuration value; no indexing/ranking outcome is promised.
- GA4 purchase events are deduplicated per order in the browser and contain no personal data.
- Merchant feeds use only approved Tajira prices, stock, real images, URLs and policies.
