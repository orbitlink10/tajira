<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['Ethernet Port Count', 'ethernet_port_count', 'number', 'ports', 'Switching', 0],
            ['Ethernet Speed', 'ethernet_speed', 'select', null, 'Switching', 1, ['10/100 Mbps', 'Gigabit', '2.5 Gigabit', '10 Gigabit', 'Mixed']],
            ['Switch Management', 'switch_management', 'select', null, 'Switching', 2, ['Unmanaged', 'Smart managed', 'Fully managed']],
            ['PoE Standard', 'poe_standard', 'select', null, 'Switching', 3, ['802.3af (PoE)', '802.3at (PoE+)', '802.3bt (PoE++)', 'Passive PoE', 'None']],
            ['PoE Power Budget', 'poe_power_budget', 'number', 'W', 'Switching', 4],
            ['SFP Ports', 'sfp_ports', 'number', 'ports', 'Switching', 5],
            ['SFP+ Ports', 'sfp_plus_ports', 'number', 'ports', 'Switching', 6],
            ['Wireless Standard', 'wireless_standard', 'select', null, 'Wireless', 7, ['Wi-Fi 4 (802.11n)', 'Wi-Fi 5 (802.11ac)', 'Wi-Fi 6 (802.11ax)', 'Wi-Fi 6E', 'Wi-Fi 7 (802.11be)']],
            ['Frequency Band', 'frequency_band', 'select', null, 'Wireless', 8, ['2.4 GHz', '5 GHz', '6 GHz', 'Dual-band', 'Tri-band']],
            ['Indoor or Outdoor', 'indoor_outdoor', 'select', null, 'Wireless', 9, ['Indoor', 'Outdoor']],
            ['Fibre Type', 'fibre_type', 'select', null, 'Fibre', 10, ['Single-mode', 'Multi-mode', 'OS2', 'OM3', 'OM4']],
            ['Connector Type', 'connector_type', 'select', null, 'Fibre', 11, ['SC/UPC', 'SC/APC', 'LC/UPC', 'LC/APC', 'LC to SC', 'RJ45']],
            ['Cable Category', 'cable_category', 'select', null, 'Cabling', 12, ['Cat5e', 'Cat6', 'Cat6A', 'Cat7', 'Cat8']],
            ['Cable Length', 'cable_length', 'number', 'm', 'Cabling', 13],
            ['Rack Size', 'rack_size', 'select', null, 'Cabling', 14, ['4U', '6U', '9U', '12U', '15U', '18U', '22U', '27U', '32U', '42U', 'Wall-mount', 'Desktop']],
            ['Camera Resolution', 'camera_resolution', 'number', 'MP', 'Security', 15],
            ['Camera Type', 'camera_type', 'select', null, 'Security', 16, ['Bullet', 'Dome', 'Turret', 'PTZ', 'Cube', 'Solar']],
            ['Recorder Channels', 'recorder_channels', 'number', 'ch', 'Security', 17],
            ['Telephony Ports', 'telephony_ports', 'number', 'ports', 'Telephony', 18],
        ];

        $position = 0;

        foreach ($definitions as $definition) {
            [$name, $code, $type, $unit, $group, $sort, $values] = array_pad($definition, 7, null);

            $attribute = Attribute::query()->updateOrCreate(
                ['code' => Str::upper($code)],
                [
                    'name' => $name,
                    'slug' => Str::slug(str_replace('_', '-', $code)),
                    'type' => $type,
                    'unit' => $unit,
                    'group' => $group,
                    'position' => $position++,
                    'is_filterable' => true,
                    'is_comparable' => true,
                    'is_searchable' => $type !== 'number',
                ]
            );

            if ($type !== 'select' || ! is_array($values)) {
                continue;
            }

            foreach ($values as $index => $value) {
                $attribute->values()->updateOrCreate(
                    ['slug' => Str::slug($value)],
                    [
                        'value' => $value,
                        'numeric_value' => $this->numericValue($value),
                        'unit' => $attribute->unit,
                        'position' => $index,
                    ]
                );
            }
        }
    }

    private function numericValue(string $value): ?float
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*Gigabit/i', $value, $matches)) {
            return (float) $matches[1] * 1000;
        }

        if (stripos($value, 'Gigabit') !== false) {
            return 1000;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*Mbps/i', $value, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/Wi-Fi\s*(\d)/i', $value, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/([246])U$/', $value, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }
}
