# Administrator and content workflow guide

## Roles

| Ability | Super Administrator | Catalogue Manager | Content Editor | Order Manager |
|---|---:|---:|---:|---:|
| Dashboard | ✓ | ✓ | ✓ | ✓ |
| Catalogue view/manage | ✓ | ✓ | — | — |
| Imports/media | ✓ | ✓ | media only | — |
| Orders/quotations | ✓ | — | — | ✓ |
| Reviews | ✓ | — | ✓ | — |
| Content/pages/articles/navigation/homepage | ✓ | — | ✓ | — |
| SEO reports/redirects/collections | ✓ | ✓ | ✓ | — |
| Coupons/shipping | ✓ | ✓ | — | ✓ |
| Settings/users/audit | ✓ | — | — | — |

Permissions are enforced server-side with Gate checks. Hiding a menu item is never the only protection.

## First sign-in

1. Use the seeded local account or the account created with `tajira:create-admin`.
2. Open **Security** in the top bar and set up TOTP MFA (authenticator app).
3. Change the password if it was seeded.
4. Set `TAJIRA_REQUIRE_ADMIN_MFA=true` on production once all administrators have enrolled.
5. Register a verified email/backup process for account recovery. Emergency MFA reset requires server access: `php artisan tajira:disable-admin-mfa email@example.com`.

## Catalogue workflow

### Create or edit a product

1. **Products → New product**.
2. Enter the descriptive name, brand, exact model/MPN, primary category and other categories.
3. Add a short buyer-focused summary and original description. Do not paste competitor copy.
4. Add verified structured specifications only. Unknown values must remain empty.
5. Set a price or mark **Quotation-only**. Confirm tax treatment.
6. Set stock, low-stock threshold and shipping dimensions/weight. Do not use stock edits to fake sales.
7. Upload authorised images with meaningful alt text. Images are converted to WebP and bounded in size.
8. Link compatible accessories/related products; add FAQs only when genuinely useful.
9. Preview (`Preview storefront`). Preview is noindexed.
10. Publish. The guardrails require a price or quotation-only flag, a description, a brand and a primary category. Variant products require at least one variant.
11. Unpublish/archive when needed. If a published slug changes, a 301 redirect is recorded from the old URL.

### Variants

Use **Variants** on the product edit screen. Each variant has its own SKU, optional price override, stock and option lines (`key: value`). “Default variant” is used for display. Deactivate variants referenced by orders rather than deleting them.

### Images and documents

- JPEG/PNG/WebP images only; max 8 MB; re-encoded to WebP.
- Alt text is required.
- PDF documents are allowed with an approved title/type.
- Do not upload images you do not have the right to use.

For manufacturer image batches, use the audited CSV importer:

```bash
php artisan manufacturer-images:import docs/data/manufacturer-images.csv --dry-run
php artisan manufacturer-images:import docs/data/manufacturer-images.csv
```

The CSV requires `sku`, `alt`, `credit` and `rights_note`, plus either `manufacturer_url` (to discover the Open Graph/JSON-LD image) or `image_url`. Source URL, credit and rights note are stored with the image. Two verified manufacturer images are currently attached (TP-Link TL-SG108 and MikroTik hAP lite TC); all other imported records use the pending-image placeholder until approved manufacturer image URLs are supplied.

### Verification notes

Record manufacturer datasheet URLs, revision information and checks in the internal source notes. Never invent GTINs, certifications, package contents, warranty periods or performance claims. Content editing and factual verification are separate steps.

## Catalogue import and reconciliation

Open **Catalogue imports**.

1. **Stage sitemap URLs**: reads the approved source product sitemap and stores URLs. It does not publish anything.
2. **Load the batch** and choose **Fetch next 25/100**. The fetch is rate-limited, checkpointed and host-restricted. Failures are stored per row.
3. Review each source record: name, brand/model/MPN, observed price (reference only), categories, attributes, source URL and errors.
4. **Approve as draft** creates a draft product with:
   - no competitor description
   - no competitor price
   - no invented specifications
   - a source note for verification
5. Complete the draft manually, verify the manufacturer documentation and set Tajira prices/stock before publishing.
6. Reject records that are duplicates, unsuitable or unverifiable. Duplicate detection flags likely matches; review before merging.
7. CSV fallback: **Products → CSV import**. Run a dry run first. Existing SKUs are never overwritten; new rows become drafts.

Reconciliation metrics are on the import dashboard. Do not describe the catalogue as fully imported until the discovery baseline and every row have a documented disposition.

## Orders and payments

### Order states

`pending_payment → paid → processing → shipped → completed`, with `cancelled` and `refunded` as exceptional states. Payment status is separate and only becomes `paid` after verified server-side confirmation.

### Daily operations

1. Check **Orders** and pending payment counts.
2. Confirm payment only in **Payments** if the provider result is definitive. Never mark paid from a screenshot or an unverified phone message.
3. For bank transfers, open the order, verify the credit in the bank, then use **Record manual payment** with the reference and a verification note.
4. Move paid orders through processing/shipped/completed and add tracking numbers.
5. Cancel unpaid orders to release reservations. Cancelling a paid order is a commercial decision; restocking is optional and refunds are processed separately.
6. If the admin screen shows a **Payment exception**, a payment arrived after cancellation/reservation expiry. Stock was not committed. Verify the transaction and refund or re-fulfil manually.
7. Download the invoice/proforma PDF. Unpaid orders are labelled proforma; no eTIMS compliance is claimed.

### Reconciliation

The scheduler queries pending M-Pesa payments. Check failed jobs and payment records. Never delete payment events; they are the audit trail for idempotency.

## Quotations

- New requests appear under **Quotations**, including the requested items, customer details and message.
- Review the request, confirm availability and pricing, then set status, quoted total, validity date and notes.
- Optionally email the update to the customer. Download the PDF record.
- Quotations are not orders and do not reserve stock.

## Content and editorial workflow

### Pages
- Create original pages/policies. Rich HTML is sanitised on save.
- Assign status, publication date, indexability and footer visibility.
- Preview drafts; slug changes generate redirects.

### Articles and guides
- Assign a category/type, author, hero image, body and related products.
- Use real HTML internal links; link audit reports broken internal targets.
- AI-assisted drafts must be reviewed. Do not auto-publish factual product claims.
- Revisions are retained; use audit logs for accountability.

### Navigation and homepage
- Navigation items are controlled links for header/footer/utility. Category menus come from active categories.
- Homepage hero/category/featured/guide/quote blocks are configurable; featured products still come from published catalogue data.
- Do not add autoplay media, popups, fake scarcity or unsupported trust claims.

## SEO tools

- **SEO reports**: missing meta descriptions, missing images/alt text, orphan products, missing specs, duplicate titles, broken internal links, link-audit runner.
- **Redirects**: create 301/302/410, no loops/self-redirects. Slug changes create automatic suggestions.
- **Collections**: admin-approved brand/category/filter landing pages with original introductions and a real product selection. Only these filter-like URLs are indexable.
- **Sitemaps**: indexable canonical content only. Validate after significant changes.
- **Search Console/GA4/Merchant feed**: configure IDs/settings, verify consent, and validate feeds. No ranking/rich-result guarantee.

## Settings, coupons and shipping

### Business settings
Update approved contact/company/tax/bank/analytics placeholders. Credentials remain in environment variables, not this screen. Do not claim eTIMS or tax compliance without a verified integration.

### Shipping
Create zones with county coverage (blank counties = all), then methods (flat/free/pickup), prices, minimum/maximum order values and lead times. Start with no invented delivery charges. Pickup instructions should state how collection is confirmed.

### Coupons
Configure percentage or fixed value, dates, minimum subtotal, maximum discount, usage and per-customer limits. Values are stored in basis points/minor units. Existing redemptions prevent deletion; deactivate instead.

## Media, users and audit

- Media uploads are WebP-optimised; deletion warns about references.
- Create least-privilege administrators. You cannot demote/deactivate your own account; at least one active super administrator is enforced.
- The audit log records administrative events with redacted sensitive values.
- Customer deletion requests: review the account's privacy request. Use `customers:erase` only after retention obligations are checked. Orders may be retained in anonymised form.

## Privacy and data

- Collect only the fields needed for orders/support.
- Analytics is consent-based when enabled; no names/emails/phones are sent in analytics events.
- Customers can export their account data and request deletion from their account privacy page.
- Backups and logs must be included in retention and deletion decisions.
