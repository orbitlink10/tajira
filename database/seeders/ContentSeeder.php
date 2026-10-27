<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->pages();
        $this->guides();
        $this->homepage();
        $this->shipping();
    }

    private function pages(): void
    {
        $pages = [
            [
                'title' => 'About Tajira',
                'slug' => 'about',
                'position' => 1,
                'excerpt' => 'Tajira supplies networking equipment to individuals, businesses, installers, institutions and ISPs in Kenya.',
                'body' => '<h2>What we do</h2><p>Tajira is a Kenyan business focused on networking equipment. The catalogue covers wireless devices, structured cabling, fibre optic solutions, security and access control, and PBX and IP telephony.</p><p>We serve individual buyers, businesses, installers, institutions and internet service providers. Product information is organised with categories, brands and structured specifications so buyers can compare equipment by the details that matter.</p><h2>How we work</h2><p>Products can be purchased online where an approved price and stock status are available, or requested by quotation for projects and bulk requirements. Prices are displayed in Kenyan shillings. Delivery options, charges and lead times are shown at checkout when configured.</p><p>Business contact details, legal information, policies and payment settings are configurable by Tajira and must be reviewed before launch.</p><p><a href="/contact">Contact us</a> or <a href="/quote">request a quotation</a>.</p>',
            ],
            [
                'title' => 'Delivery',
                'slug' => 'delivery',
                'position' => 2,
                'excerpt' => 'How Tajira delivery and pickup options are presented to customers.',
                'body' => '<p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>Policy template:</strong> Tajira must review and approve this delivery policy before launch. The text below describes how the storefront works rather than promising specific service levels.</p><h2>Delivery options</h2><p>Delivery zones, charges and lead times are configured by Tajira in the administration area. Available methods for your county are displayed at checkout before you place an order. The final order total, including delivery, is calculated and verified on the server.</p><h2>Pickup</h2><p>Where pickup is enabled, collection instructions are confirmed by our team after the order is accepted. Bring your order number and identification.</p><h2>Address details</h2><p>Provide a county, town or area, a delivery address or landmark, and a reachable phone number. Delivery notes can help with access instructions.</p><h2>Delays</h2><p>If a delivery is delayed or cannot be completed, our team will use the contact details on the order to discuss the next step. Do not send payment to any account that has not been confirmed through an official Tajira channel.</p><p>Questions? <a href="/contact">Contact us</a> with your order number.</p>',
            ],
            [
                'title' => 'Returns and Refunds',
                'slug' => 'returns-and-refunds',
                'position' => 3,
                'excerpt' => 'How return and refund requests are handled.',
                'body' => '<p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>Policy template:</strong> Tajira must replace this with its approved returns and refunds policy before launch. No specific return window is promised here.</p><h2>Before returning an item</h2><p>Contact Tajira with your order number, the product, and the reason for the return. Wait for written instructions before sending anything back. Unauthorised returns may be difficult to trace.</p><h2>Condition</h2><p>Where a return is accepted, items are generally expected to be unused and in their original packaging with included accessories and documentation. This can vary by product and supplier. Tajira will confirm the applicable conditions for your order.</p><h2>Faults and warranty</h2><p>If an item is faulty, include a clear description and any photographs or diagnostic information requested by our team. Warranty assessment may involve the manufacturer or authorised service provider. Do not attempt repairs that could void warranty cover.</p><h2>Refunds</h2><p>Approved refunds are processed using an appropriate method after the returned item has been inspected. Timing depends on the payment method and the verification step. Payment is never considered refunded merely because a browser page says so.</p><p>Start a return request through the <a href="/contact">contact page</a>.</p>',
            ],
            [
                'title' => 'Warranty',
                'slug' => 'warranty',
                'position' => 4,
                'excerpt' => 'Warranty cover depends on the manufacturer and the approved Tajira policy.',
                'body' => '<p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>Policy template:</strong> Tajira must confirm manufacturer warranty terms and its own warranty process before launch.</p><h2>Manufacturer warranty</h2><p>Many products carry a manufacturer warranty. The applicable duration, coverage and exclusions are determined by the manufacturer and the specific product. Tajira does not invent or extend warranty terms.</p><h2>Proof of purchase</h2><p>Keep your order confirmation and invoice. They may be required when making a warranty claim.</p><h2>What is not covered</h2><p>Warranty cover normally excludes damage caused by incorrect installation, power problems, liquid ingress, physical damage, unauthorised modification or use outside the manufacturer’s published conditions. Confirm the exact exclusions for your product.</p><h2>Making a claim</h2><p>Contact us with your order number, product serial number where available, and a description of the fault. We will explain the next step and whether the item needs to be assessed by the manufacturer or an authorised service partner.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'position' => 5,
                'excerpt' => 'How Tajira collects and uses personal data.',
                'body' => '<p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>Policy template:</strong> Tajira must have this policy reviewed for applicable data protection requirements before launch.</p><h2>Data we collect</h2><p>We collect the details needed to process orders, quotations and support requests, such as your name, email address, phone number, delivery details and order history. Account holders also have a password hash and saved addresses. Payment providers process payment credentials; Tajira does not ask for your M-Pesa PIN.</p><h2>Why we use it</h2><p>We use personal data to fulfil orders, respond to quotations and enquiries, provide support, prevent fraud, maintain business records and meet legal obligations. Optional analytics is only loaded according to the consent settings configured by Tajira.</p><h2>Sharing</h2><p>Data may be shared with service providers needed to operate the business, such as hosting, email, delivery and payment providers, subject to appropriate safeguards. We do not sell customer data.</p><h2>Retention and your choices</h2><p>Retention periods are configurable by Tajira and may be affected by accounting, tax, fraud-prevention and legal requirements. Signed-in customers can download their account data and request deletion from the privacy page in their account. Some transaction records may need to be retained.</p><h2>Security</h2><p>We use server-side validation, access controls, rate limits and secure session settings. No system can guarantee absolute security. Report a concern through the <a href="/contact">contact page</a>.</p><h2>Contact</h2><p>Contact Tajira using the details on the <a href="/contact">contact page</a> for privacy questions.</p>',
            ],
            [
                'title' => 'Terms and Conditions',
                'slug' => 'terms-and-conditions',
                'position' => 6,
                'excerpt' => 'Terms for using the Tajira website and placing orders.',
                'body' => '<p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>Terms template:</strong> Tajira must have these terms reviewed before launch. They describe storefront behaviour and do not replace legal advice.</p><h2>Product information</h2><p>We aim to describe products accurately using structured attributes and verified sources. Specifications can change between hardware revisions. If a specification is unknown, it is left unknown rather than guessed. Confirm critical details before purchase.</p><h2>Prices and availability</h2><p>Prices are shown in Kenyan shillings and are controlled by Tajira. Tax treatment is indicated in the catalogue and at checkout. Stock status reflects Tajira records, not a competitor’s stock. Products marked quotation-only do not display a selling price until Tajira provides one.</p><h2>Orders and payment</h2><p>An order is only treated as paid after server-side payment verification. A browser redirect or screenshot is not proof of payment. Stock is reserved temporarily and may be released if payment is not confirmed within the configured window.</p><h2>Delivery and returns</h2><p>Delivery options are shown at checkout when configured. Returns and warranty are handled under the approved Tajira policies.</p><h2>Acceptable use</h2><p>Do not attempt to disrupt the website, access administration areas without authorisation, scrape at abusive rates, or submit false reviews or payment claims.</p><h2>Liability and law</h2><p>Nothing in these terms excludes rights that cannot lawfully be excluded. The final governing-law and dispute-resolution wording must be approved by Tajira’s legal adviser.</p><p>Questions? <a href="/contact">Contact us</a>.</p>',
            ],
            [
                'title' => 'Frequently Asked Questions',
                'slug' => 'faq',
                'position' => 7,
                'excerpt' => 'Common questions about ordering, quotations and product information.',
                'body' => '<h2>Can I buy without an account?</h2><p>Yes. Guest checkout is available where a product has an approved online price. An account lets you save addresses, track orders and keep a wishlist.</p><h2>Why does a product say “Request price”?</h2><p>Some products are quotation-only. Send a quotation request with the model and quantity, and our team will confirm pricing and availability.</p><h2>How do I pay?</h2><p>Available payment methods are shown at checkout. They depend on what Tajira has enabled and configured. M-Pesa and bank-transfer options may be available; card payments require an approved provider adapter.</p><h2>How do I know payment was received?</h2><p>Your order page shows payment status. We only mark an order paid after server-side verification. If in doubt, contact us with the order number before paying again.</p><h2>Are the specifications verified?</h2><p>Structured specifications are added only when they can be supported. Unknown details are left blank. Always confirm critical compatibility details against the manufacturer datasheet for the exact hardware revision.</p><h2>Can I request a bulk quotation?</h2><p>Yes. Use the <a href="/quote">quotation form</a> to send several models and quantities, or describe your project.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->firstOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'excerpt' => $page['excerpt'],
                    'body' => $page['body'],
                    'template' => in_array($page['slug'], ['privacy-policy', 'terms-and-conditions', 'returns-and-refunds', 'warranty', 'delivery'], true) ? 'policy' : 'default',
                    'status' => 'published',
                    'published_at' => now(),
                    'is_indexable' => true,
                    'show_in_footer' => true,
                    'position' => $page['position'],
                    'meta_title' => $page['title'].' | '.config('tajira.business.name'),
                    'meta_description' => $page['excerpt'],
                ]
            );
        }
    }

    private function guides(): void
    {
        $categories = [
            ['Buying guides', 'buying-guides'],
            ['Setup guides', 'setup-guides'],
            ['Comparisons', 'comparisons'],
            ['Compatibility', 'compatibility'],
        ];

        foreach ($categories as $index => [$name, $slug]) {
            ArticleCategory::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'position' => $index]
            );
        }

        $buying = ArticleCategory::query()->where('slug', 'buying-guides')->value('id');
        $setup = ArticleCategory::query()->where('slug', 'setup-guides')->value('id');
        $comparison = ArticleCategory::query()->where('slug', 'comparisons')->value('id');

        $articles = [
            [
                'title' => 'How to choose a network switch for a small office',
                'slug' => 'choose-network-switch-small-office',
                'category' => $buying,
                'type' => 'buying',
                'excerpt' => 'A practical checklist for port count, speed, PoE and management needs before buying a switch.',
                'body' => '<p>A switch is often the least visible part of a small-office network, but it determines how many wired devices you can connect and how they perform. Start with requirements rather than brand names.</p><h2>1. Count ports with room to grow</h2><p>Count every wired device: computers, printers, access points, cameras, servers and uplinks. Add spare ports for growth and for temporary testing. If you expect PoE cameras or access points, count those ports too.</p><h2>2. Match the speed to the network</h2><p>10/100 Mbps ports can be adequate for light office traffic, but they can become a bottleneck for large file transfers, backups or multiple users. Gigabit ports are common for modern office equipment. Check that cabling and upstream devices also support the speed you choose.</p><h2>3. Decide whether you need PoE</h2><p>Power over Ethernet lets one cable carry both data and power to compatible access points, cameras and phones. Check the PoE standard required by the device and the switch’s total power budget, not only the number of PoE ports.</p><h2>4. Managed or unmanaged?</h2><p>Unmanaged switches are plug-and-play. Managed or smart switches add features such as VLANs, link aggregation, monitoring and traffic controls. Choose management features only if you have the skills and operational need to use them.</p><h2>5. Check physical and environmental details</h2><p>Confirm desktop or rack mounting, dimensions, fan noise, power input and operating temperature. A cabinet installation may need a different form factor from a desk installation.</p><h2>6. Verify the exact model</h2><p>Manufacturers often revise hardware. Confirm the model, revision and datasheet before purchase. Use the <a href="/categories/network-switches">network switches category</a> to compare structured attributes, or <a href="/quote">request a quotation</a> with your port, speed and PoE requirements.</p>',
            ],
            [
                'title' => 'Planning an outdoor wireless link: a practical checklist',
                'slug' => 'planning-outdoor-wireless-link',
                'category' => $buying,
                'type' => 'buying',
                'excerpt' => 'What to survey and confirm before buying outdoor CPE or point-to-point wireless equipment.',
                'body' => '<p>Outdoor wireless equipment is only part of a reliable link. The installation, line of sight, power and network design matter just as much as the radio specification.</p><h2>Start with a physical survey</h2><p>Confirm clear line of sight between the proposed mounting points. Trees, buildings and future construction can obstruct a link. Consider mounting height, access for installation, lightning exposure and cable routes.</p><h2>Record the distance and environment</h2><p>Document the approximate distance, the fresnel zone clearance and the local interference environment. A manufacturer’s advertised range is not a guarantee for your site.</p><h2>Match the radio and antenna</h2><p>Check frequency band, antenna gain and pattern, radio standard and Ethernet/PoE requirements. Directional links and coverage links have different design goals. Do not assume that a higher gain antenna is always better.</p><h2>Plan power and protection</h2><p>Confirm the PoE standard, injector or switch requirements, surge protection and grounding approach. Outdoor installations should follow local electrical safety practice and manufacturer guidance.</p><h2>Design the network end-to-end</h2><p>Check that the router, switch and mounting hardware can support the link. Plan IP addressing, management access, firmware updates and monitoring before deployment.</p><h2>Buy the verified model</h2><p>Models and revisions change. Confirm the exact part number and datasheet. Browse <a href="/categories/outdoor-cpe">outdoor CPE</a> or <a href="/categories/point-to-point-antennas">point-to-point antennas</a>, or send your site details through the <a href="/quote">quotation form</a>.</p>',
            ],
            [
                'title' => 'Fibre connectors explained: SC, LC, UPC and APC',
                'slug' => 'fibre-connectors-sc-lc-upc-apc',
                'category' => $comparison,
                'type' => 'comparison',
                'excerpt' => 'A plain-language explanation of common fibre connector names and polish types.',
                'body' => '<p>Fibre connector names combine a connector style with a polish or end-face type. Two common styles are SC and LC, and two common polish types are UPC and APC.</p><h2>SC and LC</h2><p>SC connectors use a square push-pull body. LC connectors are smaller and are often used in higher-density patching. The choice affects the adapters, patch panels and patch cords in your installation, so keep the connector style consistent or use the correct hybrid cords.</p><h2>UPC and APC</h2><p>UPC stands for ultra physical contact. APC stands for angled physical contact. APC connectors have an angled end face and are typically used where lower back reflection is important, such as certain PON and RF-over-glass applications. APC and UPC connectors should not be mated together because the end faces do not match.</p><h2>Single-mode and multi-mode</h2><p>Single-mode fibre is generally used for longer links and PON access networks. Multi-mode fibre is used with compatible optics over shorter distances. Connector colour can be a helpful clue, but colour alone is not a reliable substitute for reading the specification.</p><h2>Check the whole path</h2><p>Confirm the fibre type, connector style, polish, adapter type and polarity required by the active equipment. Match patch cords, pigtails, splitters, ODFs and enclosures before ordering.</p><p>Browse <a href="/categories/fibre-patch-cords-and-pigtails">fibre patch cords and pigtails</a> or <a href="/categories/fast-connectors">fast connectors</a>. For project quantities, use the <a href="/quote">quotation form</a>.</p>',
            ],
            [
                'title' => 'What to confirm before ordering networking equipment for a project',
                'slug' => 'confirm-before-ordering-project-equipment',
                'category' => $setup,
                'type' => 'guide',
                'excerpt' => 'A short pre-order checklist for installers, institutions and businesses.',
                'body' => '<p>Project orders fail for ordinary reasons: the wrong revision, missing accessories, incompatible power, or an unconfirmed delivery assumption. Use this checklist before sending a bill of materials.</p><h2>Confirm exact models and revisions</h2><p>Write down the manufacturer model and part number, not just a product family. Where a hardware revision matters, include it. Ask for the datasheet if the specification is critical.</p><h2>Check compatibility across the bill of materials</h2><p>Confirm PoE standards, SFP speeds, fibre connector types, rack sizes and power requirements across every item. A switch, camera and injector can each be correct in isolation but incompatible together.</p><h2>Separate quantities from spares</h2><p>List project quantities, installation spares and any client-spares requirement separately. Confirm which items include power adapters, mounting kits or licences.</p><h2>Confirm commercial terms separately</h2><p>Catalogue prices may change and quotation-only items have no online price. Delivery, warranty, lead time and payment terms must be confirmed by Tajira for the project.</p><h2>Record approvals</h2><p>Keep the approved quotation, datasheets and delivery details with the purchase order. If a substitution is proposed, confirm compatibility before accepting it.</p><p>Send your list through the <a href="/quote">quotation form</a> or <a href="/contact">contact us</a>.</p>',
            ],
        ];

        foreach ($articles as $article) {
            Article::query()->firstOrCreate(
                ['slug' => $article['slug']],
                [
                    'article_category_id' => $article['category'],
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'body' => $article['body'],
                    'type' => $article['type'],
                    'author_name' => 'Tajira Editorial',
                    'status' => 'published',
                    'published_at' => now(),
                    'reading_minutes' => max(3, (int) ceil(str_word_count(strip_tags($article['body'])) / 200)),
                    'is_indexable' => true,
                    'meta_title' => $article['title'].' | Tajira',
                    'meta_description' => $article['excerpt'],
                ]
            );
        }
    }

    private function homepage(): void
    {
        HomepageSection::query()->firstOrCreate(
            ['key' => 'hero'],
            [
                'title' => 'Networking equipment for the way Kenya connects.',
                'subtitle' => 'Routers, switches, wireless links, fibre, security and telephony — organised with structured specifications, clear pricing and quotation support for projects.',
                'is_active' => true,
                'position' => 0,
            ]
        );
    }

    private function shipping(): void
    {
        ShippingMethod::query()->firstOrCreate(
            ['code' => 'pickup-confirmed'],
            [
                'name' => 'Pickup — collection details confirmed',
                'shipping_zone_id' => null,
                'type' => 'pickup',
                'price_minor' => 0,
                'lead_time' => 'Collection details confirmed after order acceptance',
                'is_active' => true,
                'position' => 0,
            ]
        );
    }
}
