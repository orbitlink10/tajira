<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\AuditLogger;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index()
    {
        $this->authorize('marketing.manage');
        $coupons = Coupon::query()->latest()->paginate(30);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $this->authorize('marketing.manage');

        return view('admin.coupons.form', ['coupon' => new Coupon(['type' => 'percentage', 'is_active' => true])]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');
        $coupon = Coupon::query()->create($this->validated($request, null));
        $audit->log('coupon.created', $coupon, [], $coupon->toArray());

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        $this->authorize('marketing.manage');

        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');
        $old = $coupon->toArray();
        $coupon->update($this->validated($request, $coupon));
        $audit->log('coupon.updated', $coupon, $old, $coupon->fresh()->toArray());

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon, AuditLogger $audit)
    {
        $this->authorize('marketing.manage');

        if ($coupon->redemptions()->exists()) {
            return back()->with('error', 'This coupon has been redeemed. Deactivate it instead of deleting it.');
        }

        $audit->log('coupon.deleted', $coupon);
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    private function validated(Request $request, ?Coupon $coupon): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['type'] === 'percentage' && $data['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Percentage coupons cannot exceed 100%.']);
        }

        return [
            'code' => Str::upper(trim($data['code'])),
            'type' => $data['type'],
            'value' => $data['type'] === 'percentage' ? (int) round((float) $data['value'] * 100) : (Money::parseToMinor($request->input('value')) ?? 0),
            'min_subtotal_minor' => Money::parseToMinor($request->input('min_subtotal')),
            'max_discount_minor' => Money::parseToMinor($request->input('max_discount')),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'per_customer_limit' => $data['per_customer_limit'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
