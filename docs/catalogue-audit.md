# Reference catalogue audit and coverage limitations

**Audit date:** 20 October 2026  
**Reference:** `https://ctcsolutions.co.ke/` (publicly accessible pages only)  
**Purpose:** establish catalogue scope, brands, category organisation and a repeatable discovery/import process. This is **not** a claim that the reference catalogue has been imported into Tajira.

## Method

The audit inspected, without bypassing any access control:

- `robots.txt`
- the public Yoast sitemap index at `/wp-sitemap.xml`
- `/product-sitemap.xml`
- `/product_cat-sitemap.xml`
- `/product_brand-sitemap.xml`
- the public `/shop/` category overview and pagination links
- selected public product and category pages for structure and naming

Requests were normal-looking, rate-limited and did not use authentication, CAPTCHA bypass or private endpoints.

## Discovery baseline

| Metric | Observed |
|---|---|
| Unique public `/product/` URLs in the product sitemap | **573** |
| Product brand slugs in the brand sitemap | **30** |
| Distinct brands after normalising V-Sol/Vsol | **29** |
| Product category URLs in the category sitemap | **48** |
| Sitemap includes out-of-stock products | Yes |
| Full product-page content fetched during the audit | Yes, in the operational fetch run (21 October 2026) |
| Fetch failures | 0 |
| Unique products created as drafts | 543 |
| Probable duplicate listings flagged | 30 |

The complete URL list captured from the public sitemap is retained at [`docs/catalogue/reference-product-urls.txt`](catalogue/reference-product-urls.txt). It is an audit artifact only. No default is set to publish anything from it.

## Brands observed

Reference brand set:

Tenda, Ubiquiti, MikroTik, TP-Link, D-Link, Dahua, Netlink, Panasonic, Huawei, V-SOL, Mercusys, ZKTeco.

Additional brands discovered in the public brand sitemap/catalogue:

Aico, APC, Casio, Excelltel, Fanvil, Grandlink, Grandstream, Hioso, IMOU, RDCOM, Ruijie, Scoop, Signal Fire, Starlink, Western Digital, Yealink.

Normalisation decisions:

- `V-Sol` and `Vsol` are one brand: **V-SOL**.
- `Mikrotik` is displayed as **MikroTik**.
- `Tp-Link` is displayed as **TP-Link**.
- `Zkteco` is displayed as **ZKTeco**.
- `IMOU` is treated as its own brand (related to Dahua) because the reference presents a distinct brand/catalogue.
- Generic terms such as “Fiber” are not treated as brands.
- “Brand” category branches (Mikrotik, D-link, Tp-link, Ubiquiti network devices) were **not** duplicated as categories. Brands are separate entities.

The Tajira seeder includes these brands so staged observations can be mapped. A brand existing in the seeder does not mean its products were imported or verified.

## Reference category organisation and corrections

The reference mixes functional categories, brand-named categories, and spelling variants. Tajira uses a corrected, brand-neutral nested taxonomy:

### Wireless Devices
Outdoor CPE; Wireless Access Points; Routers; Network Switches; Range Extenders; USB Wi-Fi Adapters; Point-to-Point Antennas.

### Structured Cabling
Ethernet Cables; Network Cabinets; Media Converters; PoE Injectors; Cabinet Shelves; Coaxial Cables; DAC Cables; Network Tools.

### Fibre Optic Solutions
Fibre Optic Cables; PLC Splitters; Fibre Enclosures; Fast Connectors; Fibre Patch Cords and Pigtails; Optical Distribution Frames; Optical Line Terminals; Splicing Machines; Fibre Test Equipment; Fibre Tool Kits.

### Security and Access Control
CCTV Cameras; Access Control; Network Video Recorders; Video Intercoms; Surveillance Storage; Power Supplies and UPS.

### PBX and IP Telephony
IP PBX Systems; IP Phones; Analog PBX; Analog Phones; VoIP Gateways.

Corrections applied:

| Reference issue | Tajira correction |
|---|---|
| “Fiber” / “Fibre” inconsistency | British spelling “Fibre” consistently |
| “Enclosurers” | “Fibre Enclosures” |
| “POE” / “PoE” inconsistency | “PoE” consistently |
| Brand pages used as categories (Mikrotik, D-Link, TP-Link, Ubiquiti) | Brands only; category assignments remain functional |
| PBX children named after brands (Yeastar, Yealink, Fanvil, Grandstream) | Neutral telephony categories; those names remain brands |
| Duplicate/overlapping “Access Control” locations | One Access Control category under Security |
| CCTV power supplies, UPS and surveillance HDDs scattered | Power Supplies and UPS; Surveillance Storage |
| NVR/DVR mixed into cameras | Dedicated Network Video Recorders category |
| Fibre tools/data equipment mixed | Dedicated splicing/test/tool categories and DAC cables |

The seeded starter products demonstrate the mapping. All 543 imported records were assigned a primary category; 30 probable duplicate listings remain staged for review.

## Operational population run

On 21 October 2026 the reference sitemap was staged and all 573 public product pages were fetched through the application’s pipeline.

| Result | Count |
|---|---:|
| URLs staged | 573 |
| Pages fetched | 573 |
| Fetch failures | 0 |
| Parsed records needing review | 543 |
| Probable duplicate listings | 30 |
| Quotation-only catalogue records created | 543 |
| Imported drafts with competitor description | 0 |
| Imported drafts with Tajira price | 0 |
| Imported drafts with image | 0 |
| Imported records with a primary category | 543 |
| Total products now in database | 555 |
| Published products | 545 (543 imported quotation-only + 2 verified quote-only) |
| Draft products | 10 starter drafts |\n| Products with manufacturer images | 2 (TP-Link TL-SG108, MikroTik hAP lite TC) |

The 30 flagged duplicates comprise exact/near-identical source listings and matches to existing starter products. They were **not** merged automatically and were **not** created as products. They remain in the staging dashboard for review.

All 543 imported records are published as **quotation-only, noindexed** catalogue entries so customers can request verified pricing. They have no copied competitor copy, no Tajira price, no stock and no invented specifications. Two verified manufacturer images are attached; the remaining records use a pending-image placeholder. Of the imported records, 280 have no brand because the source did not provide a reliable brand; no brand was invented.

## Import and reconciliation pipeline

```bash
# 1. Stage product URLs from the approved sitemap (no publication)
php artisan catalogue:discover --limit=573

# 2. Fetch and parse a checkpointed batch (rate-limited)
php artisan catalogue:fetch {batchId} --limit=25

# 3. Review metrics and every staging record
php artisan catalogue:reconcile
# or use /admin/imports

# 4. Owner CSV fallback with dry run
php artisan catalogue:import-csv path/to/products.csv --dry-run
```

The admin dashboard shows:

- total unique discovered source URLs
- imported products (draft created)
- duplicates flagged/merged
- pending verification
- blocked/rejected
- failed fetches
- unresolved source URLs
- coverage by brand and source category

Every `source_products` row has a status and disposition. A staged record can be:

| Status | Meaning |
|---|---|
| `discovered` | URL staged, page not fetched |
| `needs_review` | Parsed, awaiting a human decision |
| `duplicate` | Possible duplicate; linked to the candidate/product |
| `imported` | Approved as a **draft** Tajira product |
| `rejected` | Deliberately not used |
| `failed` | Fetch/parse error; retryable |

## What was not done / completeness limitations

- **All sitemap pages were fetched, but the imported drafts are not verified.** The 573 count is the public sitemap baseline, not proof of a complete, verified live catalogue.
- Sitemap counts can change. Some public products may be missing from the sitemap; a new audit would be required to detect additions or removals.
- 30 probable duplicate listings were left out. Some may be genuine variants and need a human decision.
- Reference prices and stock were treated strictly as observations. No Tajira selling prices or stock levels were derived from them.
- No competitor descriptions, images, reviews, policies or company details were copied into public content. Source observations remain internal staging data.
- No authentication, CAPTCHA, paywall or access control was bypassed.
- Manufacturer verification is required for every published specification. Unknown values remain unknown. Imported drafts therefore contain no structured specifications.
- Images require manufacturer authorisation or owner-supplied rights. Two verified manufacturer images were added with source/credit recorded. No reference-site images were downloaded or published; the remaining records use a pending-image placeholder.
- The audit did not verify manufacturer authenticity, warranty status or supplier availability.
- Owner-supplied CSV import is supported as a fallback if discovery is blocked or incomplete.

## Honest current catalogue state

The database currently contains:

- **555 products total**: 545 published (543 imported quotation-only records plus 2 verified quote-only starter products) and 10 unpublished starter drafts.
- **543 unique products imported from the reference catalogue**, published as quotation-only, noindexed records with no price, stock, description, invented specification or copied competitor content. Two have verified manufacturer images.
- **30 probable duplicate listings** remaining in staging, not created as products.
- **10 original starter draft records**, also unpublished.
- **2 published quote-only starter products** with specifications checked against manufacturer pages.
- All products have a primary category; 280 imported products have no brand because the source did not provide one reliably.

The catalogue is populated but **not verified, priced, stocked, imaged or commercially approved**. Use the reconciliation dashboard to resolve duplicates, then verify and publish products deliberately. Do not describe the catalogue as fully verified or production-ready until that work is complete.
