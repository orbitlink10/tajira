<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->verifiedTpLink();
        $this->verifiedMikroTik();
        $this->draftRecords();
    }

    private function verifiedTpLink(): void
    {
        $product = Product::query()->firstOrCreate(
            ['sku' => 'TPLINK-TL-SG108'],
            [
                'name' => 'TP-Link TL-SG108 8-Port Gigabit Desktop Switch',
                'slug' => 'tp-link-tl-sg108-8-port-gigabit-desktop-switch',
                'brand_id' => $this->brand('TP-Link'),
                'primary_category_id' => $this->category('network-switches'),
                'model' => 'TL-SG108',
                'mpn' => 'TL-SG108',
                'product_type' => 'simple',
                'status' => Product::STATUS_PUBLISHED,
                'published_at' => now(),
                'is_featured' => true,
                'is_indexable' => true,
                'price_on_request' => true,
                'stock_quantity' => 0,
                'short_description' => 'An eight-port gigabit desktop switch for expanding a wired home or small-office network. Plug-and-play, fanless and housed in a metal case.',
                'description' => '<p>The TP-Link TL-SG108 adds eight auto-negotiating gigabit Ethernet ports to a small network. It is aimed at users who need more wired connections for computers, printers, access points, servers or other network devices without configuring switch management features.</p><p>The switch is fanless and uses a metal housing. It supports standard Ethernet flow control and includes QoS and IGMP snooping functions. The manufacturer lists a desktop or wall-mount design and an external 5 VDC power adapter.</p><p>This is a quotation-only catalogue record. Tajira pricing, stock and lead time are confirmed separately.</p>',
                'key_features' => [
                    'Eight 10/100/1000 Mbps RJ45 ports with auto-negotiation and Auto-MDI/MDIX',
                    'Plug-and-play operation with no configuration required',
                    'Fanless metal housing for quiet desktop or wall-mounted use',
                    '802.3x flow control, 802.1p/DSCP QoS and IGMP snooping',
                    'Manufacturer-listed dimensions of 158 × 101 × 25 mm',
                ],
                'applications' => [
                    'Adding wired ports in a small office or home network',
                    'Connecting computers, printers, access points and other Ethernet devices',
                    'Expanding a network where unmanaged plug-and-play operation is sufficient',
                ],
                'compatibility' => '<p>Designed for standard 10/100/1000 Mbps Ethernet devices. Use Cat5e or better cabling for gigabit links. This is an unmanaged switch, so it does not provide VLAN, link aggregation or SNMP management features.</p>',
                'package_contents' => [
                    'TL-SG108 switch',
                    'Power adapter',
                    'Installation guide',
                ],
                'setup_overview' => '<p>Connect the supplied power adapter, then connect network devices with Ethernet cables. No configuration is required for basic switching. Refer to the manufacturer installation guide for mounting and safety guidance.</p>',
                'warranty_text' => 'Warranty cover is determined by the manufacturer and the approved Tajira warranty policy.',
                'returns_text' => 'Returns are subject to the approved Tajira returns policy. Contact us before returning an item.',
                'source_notes' => 'Specifications checked against the TP-Link TL-SG108 V9 product page (https://www.tp-link.com/us/home-networking/soho-switch/tl-sg108/) on 2026-10-20. Hardware revisions can differ; confirm the exact revision datasheet before purchase.',
                'meta_title' => 'TP-Link TL-SG108 8-Port Gigabit Switch in Kenya | Tajira',
                'meta_description' => 'TP-Link TL-SG108 8-port gigabit desktop switch: plug-and-play, fanless metal housing. Request price and availability from Tajira in Kenya.',
            ]
        );

        $this->setCategories($product, ['network-switches', 'structured-cabling']);
        $this->setSpec($product, 'ETHERNET_PORT_COUNT', 8, 'ports');
        $this->setSelectSpec($product, 'ETHERNET_SPEED', 'Gigabit');
        $this->setSelectSpec($product, 'SWITCH_MANAGEMENT', 'Unmanaged');
        $this->setSelectSpec($product, 'POE_STANDARD', 'None');
        $this->setSelectSpec($product, 'RACK_SIZE', 'Desktop');
        $this->setSpec($product, 'SFP_PORTS', 0, 'ports');
        $this->setSpec($product, 'SFP_PLUS_PORTS', 0, 'ports');
    }

    private function verifiedMikroTik(): void
    {
        $product = Product::query()->firstOrCreate(
            ['sku' => 'MIKROTIK-RB941-2ND-TC'],
            [
                'name' => 'MikroTik hAP lite TC RB941-2nD-TC',
                'slug' => 'mikrotik-hap-lite-tc-rb941-2nd-tc',
                'brand_id' => $this->brand('MikroTik'),
                'primary_category_id' => $this->category('routers'),
                'model' => 'RB941-2nD-TC',
                'mpn' => 'RB941-2nD-TC',
                'product_type' => 'simple',
                'status' => Product::STATUS_PUBLISHED,
                'published_at' => now(),
                'is_featured' => true,
                'is_indexable' => true,
                'price_on_request' => true,
                'stock_quantity' => 0,
                'short_description' => 'A compact MikroTik RouterOS access point and router with four Fast Ethernet ports and 2.4 GHz Wi-Fi, suited to homes and small offices.',
                'description' => '<p>The MikroTik hAP lite TC is a small home or office access point and router running RouterOS. The manufacturer lists a 650 MHz CPU, 32 MB RAM, dual-chain 2.4 GHz wireless and four 10/100 Ethernet ports.</p><p>It supports WPS button pairing and can be switched to cAP mode for central management with CAPsMAN, according to MikroTik. Because the Ethernet ports are Fast Ethernet rather than gigabit, it is not intended for networks that require gigabit wired throughput.</p><p>This is a quotation-only catalogue record. Tajira pricing, stock and lead time are confirmed separately.</p>',
                'key_features' => [
                    'Four 10/100 Ethernet ports',
                    'Dual-chain 2.4 GHz 802.11b/g/n wireless (Wi-Fi 4)',
                    'MikroTik RouterOS with a Level 4 licence',
                    '650 MHz CPU and 32 MB RAM per manufacturer specification',
                    'Passive cooling and 5 V MicroUSB power input',
                ],
                'applications' => [
                    'Basic home or small-office wireless and wired connectivity',
                    'RouterOS labs, branch networking and managed wireless deployments',
                    'Low-power indoor installations where Fast Ethernet is sufficient',
                ],
                'compatibility' => '<p>2.4 GHz client devices can connect using the supported 802.11b/g/n standards. The four Ethernet ports are 10/100 Mbps, not gigabit. RouterOS configuration knowledge is useful for advanced routing, firewall and CAPsMAN features. Confirm the current RouterOS release and licence terms with MikroTik documentation.</p>',
                'package_contents' => [
                    'hAP lite TC device',
                    '5 V 1 A power adapter',
                ],
                'setup_overview' => '<p>The device ships with RouterOS installed. Use the manufacturer quick guide to connect power and network, then access the default configuration interface. Advanced configurations should follow MikroTik documentation or be completed by a qualified network professional.</p>',
                'warranty_text' => 'Warranty cover is determined by the manufacturer and the approved Tajira warranty policy.',
                'returns_text' => 'Returns are subject to the approved Tajira returns policy. Contact us before returning an item.',
                'source_notes' => 'Specifications checked against the MikroTik hAP lite TC product page (https://mikrotik.com/product/RB941-2nD-TC) on 2026-10-20. Confirm the current hardware revision and RouterOS release before deployment.',
                'meta_title' => 'MikroTik hAP lite TC RB941-2nD-TC in Kenya | Tajira',
                'meta_description' => 'MikroTik hAP lite TC RB941-2nD-TC: four Fast Ethernet ports, 2.4 GHz Wi-Fi and RouterOS. Request price and availability from Tajira in Kenya.',
            ]
        );

        $this->setCategories($product, ['routers', 'wireless-access-points']);
        $this->setSpec($product, 'ETHERNET_PORT_COUNT', 4, 'ports');
        $this->setSelectSpec($product, 'ETHERNET_SPEED', '10/100 Mbps');
        $this->setSelectSpec($product, 'WIRELESS_STANDARD', 'Wi-Fi 4 (802.11n)');
        $this->setSelectSpec($product, 'FREQUENCY_BAND', '2.4 GHz');
        $this->setSelectSpec($product, 'INDOOR_OUTDOOR', 'Indoor');
    }

    private function draftRecords(): void
    {
        $records = [
            ['TENDA-F3', 'Tenda F3 N300 Wireless Router', 'tenda-f3-n300-wireless-router', 'Tenda', 'routers', 'F3', ['routers']],
            ['UBIQUITI-LOCO-M5', 'Ubiquiti airMAX NanoStation Loco M5', 'ubiquiti-airmax-nanostation-loco-m5', 'Ubiquiti', 'outdoor-cpe', 'LocoM5', ['outdoor-cpe', 'point-to-point-antennas']],
            ['DAHUA-IPC-HFW1230S1-S5', 'Dahua DH-IPC-HFW1230S1-S5 2MP Bullet IP Camera', 'dahua-dh-ipc-hfw1230s1-s5-2mp-bullet-ip-camera', 'Dahua', 'cctv-cameras', 'DH-IPC-HFW1230S1-S5', ['cctv-cameras']],
            ['DLINK-DES-1024D', 'D-Link DES-1024D 24-Port Desktop Switch', 'd-link-des-1024d-24-port-desktop-switch', 'D-Link', 'network-switches', 'DES-1024D', ['network-switches']],
            ['TPLINK-TL-SF1008D', 'TP-Link TL-SF1008D 8-Port Desktop Switch', 'tp-link-tl-sf1008d-8-port-desktop-switch', 'TP-Link', 'network-switches', 'TL-SF1008D', ['network-switches']],
            ['MIKROTIK-RB260GS', 'MikroTik RB260GS SOHO Switch', 'mikrotik-rb260gs-soho-switch', 'MikroTik', 'network-switches', 'RB260GS', ['network-switches']],
            ['ZKTECO-F18', 'ZKTeco F18 Biometric Access Control Terminal', 'zkteco-f18-biometric-access-control-terminal', 'ZKTeco', 'access-control', 'F18', ['access-control']],
            ['YEALINK-SIP-T31P', 'Yealink SIP-T31P IP Phone', 'yealink-sip-t31p-ip-phone', 'Yealink', 'ip-phones', 'SIP-T31P', ['ip-phones']],
            ['GRANDSTREAM-GXP1625', 'Grandstream GXP1625 IP Phone', 'grandstream-gxp1625-ip-phone', 'Grandstream', 'ip-phones', 'GXP1625', ['ip-phones']],
            ['DAHUA-NVR4108HS-8P', 'Dahua NVR4108HS-8P 8-Channel PoE NVR', 'dahua-nvr4108hs-8p-8-channel-poe-nvr', 'Dahua', 'network-video-recorders', 'NVR4108HS-8P', ['network-video-recorders']],
        ];

        foreach ($records as [$sku, $name, $slug, $brandName, $categorySlug, $model, $categorySlugs]) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'brand_id' => $this->brand($brandName),
                    'primary_category_id' => $this->category($categorySlug),
                    'model' => $model,
                    'mpn' => $model,
                    'product_type' => 'simple',
                    'status' => Product::STATUS_DRAFT,
                    'price_on_request' => true,
                    'stock_quantity' => 0,
                    'is_indexable' => true,
                    'short_description' => 'Draft starter record. Confirm the exact model, manufacturer specifications, authorised images, Tajira pricing, availability and warranty before publication.',
                    'source_notes' => 'Starter record based on the public reference catalogue audit. It is deliberately unpublished and has no Tajira price, stock or verified specification. Verify against manufacturer documentation before approval.',
                ]
            );

            $this->setCategories($product, $categorySlugs);
        }
    }

    private function brand(string $name): ?int
    {
        return Brand::query()->where('name', $name)->value('id');
    }

    private function category(string $slug): ?int
    {
        return Category::query()->where('slug', $slug)->value('id');
    }

    private function setCategories(Product $product, array $slugs): void
    {
        $primarySlug = $slugs[0] ?? null;
        $payload = [];

        foreach ($slugs as $index => $slug) {
            $category = Category::query()->where('slug', $slug)->first();

            if (! $category) {
                continue;
            }

            $payload[$category->id] = ['is_primary' => $slug === $primarySlug, 'position' => $index];

            if ($slug === $primarySlug) {
                $product->forceFill(['primary_category_id' => $category->id])->save();
            }
        }

        if ($payload !== []) {
            $product->categories()->syncWithoutDetaching($payload);
        }
    }

    private function setSelectSpec(Product $product, string $attributeCode, string $value): void
    {
        $attribute = Attribute::query()->where('code', $attributeCode)->first();
        $attributeValue = $attribute?->values()->where('value', $value)->first();

        if (! $attribute || ! $attributeValue) {
            return;
        }

        $product->attributeValues()->updateOrCreate(
            ['attribute_id' => $attribute->id],
            [
                'attribute_value_id' => $attributeValue->id,
                'value_number' => $attributeValue->numeric_value,
                'unit' => $attributeValue->unit ?: $attribute->unit,
            ]
        );
    }

    private function setSpec(Product $product, string $attributeCode, float $number, ?string $unit): void
    {
        $attribute = Attribute::query()->where('code', $attributeCode)->first();

        if (! $attribute) {
            return;
        }

        $product->attributeValues()->updateOrCreate(
            ['attribute_id' => $attribute->id],
            [
                'value_number' => $number,
                'unit' => $unit ?: $attribute->unit,
            ]
        );
    }
}
