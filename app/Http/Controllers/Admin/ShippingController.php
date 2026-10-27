<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Services\AuditLogger;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShippingController extends Controller
{
    public function index()
    {
        $this->authorize('marketing.manage');
        $zones = ShippingZone::query()->with(['methods'])->orderBy('position')->get();
        $unassigned = ShippingMethod::query()->whereNull('shipping_zone_id')->orderBy('position')->get();

        return view('admin.shipping.index', compact('zones', 'unassigned'));
    }

    public function storeZone(Request $request, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');
        $zone = ShippingZone::query()->create($this->zoneData($request));
        $audit->log('shipping-zone.created', $zone, [], $zone->toArray());

        return back()->with('success', 'Shipping zone created.');
    }

    public function updateZone(Request $request, ShippingZone $zone, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');
        $old = $zone->toArray();
        $zone->update($this->zoneData($request, $zone));
        $audit->log('shipping-zone.updated', $zone, $old, $zone->fresh()->toArray());

        return back()->with('success', 'Shipping zone updated.');
    }

    public function destroyZone(ShippingZone $zone, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');

        if ($zone->methods()->exists()) {
            return back()->with('error', "Delete or reassign this zone's methods first.");
        }

        $audit->log('shipping-zone.deleted', $zone);
        $zone->delete();

        return back()->with('success', 'Shipping zone deleted.');
    }

    public function storeMethod(Request $request, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');
        $method = ShippingMethod::query()->create($this->methodData($request, null));
        $audit->log('shipping-method.created', $method, [], $method->toArray());

        return back()->with('success', 'Shipping method created.');
    }

    public function updateMethod(Request $request, ShippingMethod $method, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');
        $old = $method->toArray();
        $method->update($this->methodData($request, $method));
        $audit->log('shipping-method.updated', $method, $old, $method->fresh()->toArray());

        return back()->with('success', 'Shipping method updated.');
    }

    public function destroyMethod(ShippingMethod $method, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');
        $audit->log('shipping-method.deleted', $method);
        $method->delete();

        return back()->with('success', 'Shipping method deleted.');
    }

    private function zoneData(Request $request, ?ShippingZone $zone = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'counties_text' => ['nullable', 'string', 'max:3000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $counties = collect(preg_split('/\r\n|\r|\n|,/', (string) ($data['counties_text'] ?? '')))
            ->map(fn ($county) => trim($county))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'name' => $data['name'],
            'counties' => $counties === [] ? null : $counties,
            'position' => (int) ($data['position'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function methodData(Request $request, ?ShippingMethod $method): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:80', Rule::unique('shipping_methods', 'code')->ignore($method?->id)],
            'shipping_zone_id' => ['nullable', 'integer', 'exists:shipping_zones,id'],
            'type' => ['required', 'in:flat,free,pickup'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_order' => ['nullable', 'numeric', 'min:0'],
            'lead_time' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = $data['code'] ?: Str::slug($data['name'], '-');

        if (ShippingMethod::query()->where('code', $code)->when($method, fn ($q) => $q->whereKeyNot($method->id))->exists()) {
            $code .= '-'.Str::lower(Str::random(4));
        }

        return [
            'name' => $data['name'],
            'code' => $code,
            'shipping_zone_id' => $data['shipping_zone_id'] ?? null,
            'type' => $data['type'],
            'price_minor' => $data['type'] === 'free' || $data['type'] === 'pickup' ? 0 : (Money::parseToMinor($request->input('price')) ?? 0),
            'min_order_minor' => Money::parseToMinor($request->input('min_order')),
            'max_order_minor' => Money::parseToMinor($request->input('max_order')),
            'lead_time' => $data['lead_time'] ?? null,
            'position' => (int) ($data['position'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
