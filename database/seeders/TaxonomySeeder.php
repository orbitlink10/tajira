<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Wireless Devices' => [
                'Outdoor CPE' => 'Outdoor customer-premises equipment and wireless bridges.',
                'Wireless Access Points' => 'Access points for homes, offices, campuses and public areas.',
                'Routers' => 'Routers for homes, branches and network edges.',
                'Network Switches' => 'Unmanaged, smart and managed switches.',
                'Range Extenders' => 'Wi-Fi range extenders and repeaters.',
                'USB Wi-Fi Adapters' => 'USB wireless adapters for client devices.',
                'Point-to-Point Antennas' => 'Directional antennas and point-to-point wireless links.',
                'Mobile MiFi' => 'Portable mobile Wi-Fi routers and MiFi devices.',
                'IoT Devices' => 'LoRaWAN, LTE and other connected network sensors and devices.',
                'USB Network Adapters' => 'USB Ethernet and network adapters.',
                'Satellite Internet' => 'Satellite internet kits and related equipment.',
            ],
            'Structured Cabling' => [
                'Ethernet Cables' => 'Copper network cables for indoor and outdoor runs.',
                'Network Cabinets' => 'Wall-mounted and floor-standing equipment cabinets.',
                'Media Converters' => 'Copper-to-fibre media converters.',
                'PoE Injectors' => 'Power-over-Ethernet injectors and adapters.',
                'Cabinet Shelves' => 'Shelves and rack accessories for network cabinets.',
                'Coaxial Cables' => 'Coaxial cable for CCTV and signal distribution.',
                'DAC Cables' => 'Direct-attach copper cables for compatible equipment.',
                'Network Tools' => 'Crimping, testing and installation tools.',
                'Power Adapters' => 'Power adapters and power accessories for network equipment.',
                'Alarm and Communication Cables' => 'Alarm, communication and control cable.',
            ],
            'Fibre Optic Solutions' => [
                'Fibre Optic Cables' => 'Indoor, outdoor, drop and distribution fibre cables.',
                'PLC Splitters' => 'Passive optical splitters for PON networks.',
                'Fibre Enclosures' => 'Fibre enclosures, FAT boxes and joint closures.',
                'Fast Connectors' => 'Field-installable fibre connectors.',
                'Fibre Patch Cords and Pigtails' => 'Patch cords and pigtails for fibre patching.',
                'Optical Distribution Frames' => 'ODFs and fibre termination panels.',
                'Optical Line Terminals' => 'OLTs, ONUs and GPON access equipment.',
                'Splicing Machines' => 'Fusion splicers and splicing accessories.',
                'Fibre Test Equipment' => 'Optical power meters, OTDRs and fibre identifiers.',
                'Fibre Tool Kits' => 'Cleavers, strippers and fibre installation kits.',
            ],
            'Security and Access Control' => [
                'CCTV Cameras' => 'IP, analogue and Wi-Fi surveillance cameras.',
                'Access Control' => 'Fingerprint, card and biometric access control.',
                'Network Video Recorders' => 'NVRs, DVRs and hybrid recorders.',
                'Video Intercoms' => 'Door stations, indoor monitors and video intercom kits.',
                'Surveillance Storage' => 'Surveillance-grade hard drives and storage.',
                'Power Supplies and UPS' => 'Power supplies and UPS units for security systems.',
            ],
            'PBX and IP Telephony' => [
                'IP PBX Systems' => 'IP PBX appliances and software-based telephony platforms.',
                'IP Phones' => 'Desk and conference IP phones.',
                'Analog PBX' => 'Analogue PABX systems and expansion modules.',
                'Analog Phones' => 'Analogue telephones and accessories.',
                'VoIP Gateways' => 'FXS, FXO and GSM gateways for telephony integration.',
            ],
        ];

        $position = 0;

        foreach ($tree as $parentName => $children) {
            $parent = Category::query()->updateOrCreate(
                ['slug' => Str::slug($parentName)],
                [
                    'name' => $parentName,
                    'short_name' => $parentName,
                    'description' => 'Browse '.Str::lower($parentName).' from Tajira.',
                    'is_active' => true,
                    'is_indexable' => true,
                    'show_in_menu' => true,
                    'position' => $position++,
                ]
            );

            $childPosition = 0;

            foreach ($children as $childName => $description) {
                Category::query()->updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'short_name' => $childName,
                        'description' => $description,
                        'is_active' => true,
                        'is_indexable' => true,
                        'show_in_menu' => true,
                        'position' => $childPosition++,
                    ]
                );
            }
        }

        $brands = [
            'Tenda', 'Ubiquiti', 'MikroTik', 'TP-Link', 'D-Link', 'Dahua', 'Netlink',
            'Panasonic', 'Huawei', 'V-SOL', 'Mercusys', 'ZKTeco', 'Yeastar', 'Yealink',
            'Fanvil', 'Grandstream', 'Excelltel', 'Grandlink', 'IMOU', 'Ruijie', 'Hioso',
            'Signal Fire', 'APC', 'Western Digital', 'Aico', 'Casio', 'RDCOM',
            'Scoop', 'Starlink',
        ];

        foreach (array_unique($brands) as $index => $brandName) {
            Brand::query()->updateOrCreate(
                ['slug' => Str::slug($brandName)],
                [
                    'name' => $brandName,
                    'is_active' => true,
                    'is_indexable' => true,
                    'position' => $index,
                ]
            );
        }
    }
}
