<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\ProductReview;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $orders = $user->orders()->latest()->limit(5)->get();
        $orderCount = $user->orders()->count();
        $wishlistCount = $user->wishlistItems()->count();
        $meta = $this->privateMeta('Your account', route('account.dashboard'));

        return view('storefront.account.dashboard', compact('user', 'orders', 'orderCount', 'wishlistCount', 'meta'));
    }

    public function orders(Request $request)
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(10);
        $meta = $this->privateMeta('Your orders', route('account.orders'));

        return view('storefront.account.orders', compact('orders', 'meta'));
    }

    public function addresses(Request $request)
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->latest()->get();
        $meta = $this->privateMeta('Your addresses', route('account.addresses'));

        return view('storefront.account.addresses', compact('addresses', 'meta'));
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'recipient' => ['required', 'string', 'max:140'],
            'phone' => ['required', 'string', 'max:30'],
            'county' => ['required', Rule::in(counties())],
            'town' => ['required', 'string', 'max:120'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        if ($request->boolean('is_default') || $user->addresses()->count() === 0) {
            $user->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $user->addresses()->create($data);

        return back()->with('success', 'Address saved.');
    }

    public function deleteAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return back()->with('success', 'Address removed.');
    }

    public function profile(Request $request)
    {
        $meta = $this->privateMeta('Profile settings', route('account.profile'));

        return view('storefront.account.profile', ['user' => $request->user(), 'meta' => $meta]);
    }

    public function updateProfile(Request $request, AuditLogger $audit)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $old = $user->only(['name', 'email', 'phone']);
        $user->update($data);
        $audit->log('account.profile.updated', $user, $old, $user->only(['name', 'email', 'phone']));

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $request->user()->update(['password' => $data['password']]);
        $audit->log('account.password.updated', $request->user());

        return back()->with('success', 'Password changed.');
    }

    public function reviews(Request $request)
    {
        $reviews = ProductReview::query()
            ->where('user_id', $request->user()->id)
            ->with('product:id,name,slug')
            ->latest()
            ->paginate(10);
        $meta = $this->privateMeta('Your reviews', route('account.reviews'));

        return view('storefront.account.reviews', compact('reviews', 'meta'));
    }

    public function privacy(Request $request)
    {
        $meta = $this->privateMeta('Privacy and your data', route('account.privacy'));

        return view('storefront.account.privacy', ['user' => $request->user(), 'meta' => $meta]);
    }

    public function export(Request $request)
    {
        $user = $request->user();

        $data = [
            'account' => $user->only(['name', 'email', 'phone', 'created_at']),
            'addresses' => $user->addresses()->get()->toArray(),
            'orders' => $user->orders()->with('items')->get()->map(fn ($order) => [
                'number' => $order->number,
                'status' => $order->status,
                'total_minor' => $order->total_minor,
                'currency' => $order->currency,
                'placed_at' => $order->placed_at,
                'items' => $order->items->map->only(['name', 'sku', 'quantity', 'line_total_minor'])->all(),
            ])->all(),
            'reviews' => $user->reviews()->get()->map->only(['rating', 'title', 'body', 'status', 'created_at'])->all(),
            'wishlist' => $user->wishlistItems()->with('product:id,name,slug')->get()->map(fn ($item) => $item->product?->name)->filter()->all(),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="tajira-account-'.$user->id.'.json"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function requestDeletion(Request $request, AuditLogger $audit)
    {
        $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $request->user()->forceFill(['deletion_requested_at' => now()])->save();
        $audit->log('account.deletion_requested', $request->user());

        return redirect()->route('account.privacy')
            ->with('success', 'Your deletion request has been recorded. Some order records may be retained where required for accounting or legal obligations; our team will confirm the outcome by email.');
    }

    private function privateMeta(string $title, string $canonical): array
    {
        return [
            'title' => $title.' | '.setting('business.name', 'Tajira'),
            'description' => 'Manage your Tajira account.',
            'canonical' => $canonical,
            'robots' => 'noindex,nofollow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => null,
        ];
    }
}
