<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\QuotationReceived;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\CartService;
use App\Services\OrderNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    public function create(Request $request, CartService $cart)
    {
        $product = null;

        if ($request->filled('product')) {
            $product = Product::query()->published()->with(['brand:id,name', 'primaryImage'])->find($request->integer('product'));
        }

        $items = $cart->items()->map(fn ($item) => [
            'product' => $item['product'],
            'variant' => $item['variant'],
            'quantity' => $item['quantity'],
        ])->all();

        if ($product && ! collect($items)->contains(fn ($item) => $item['product']->id === $product->id)) {
            $items[] = ['product' => $product, 'variant' => null, 'quantity' => 1];
        }

        $meta = $this->privateMeta('Request a quotation', route('quotes.create'));

        return view('storefront.quotes.create', compact('items', 'product', 'meta'));
    }

    public function store(Request $request, OrderNumberService $numbers)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:180'],
            'county' => ['nullable', Rule::in(counties())],
            'town' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:3000'],
            'items' => ['nullable', 'array', 'max:40'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        $items = $data['items'] ?? [];

        if ($items === [] && blank($data['message'] ?? null)) {
            return back()->withErrors(['message' => 'Add at least one product or describe what you need.'])->withInput();
        }

        $quotation = Quotation::query()->create([
            'number' => $numbers->quotation(),
            'user_id' => $request->user()?->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'company' => $data['company'] ?? null,
            'county' => $data['county'] ?? null,
            'town' => $data['town'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'new',
            'ip_hash' => hash('sha256', (string) $request->ip().'|'.config('app.key')),
        ]);

        $total = 0;
        $allPriced = true;

        foreach ($items as $item) {
            $product = Product::query()->published()->find($item['product_id']);

            if (! $product) {
                continue;
            }

            $unit = $product->effectivePriceMinor();
            $quantity = max(1, (int) $item['quantity']);

            QuotationItem::query()->create([
                'quotation_id' => $quotation->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price_minor' => $unit,
            ]);

            if ($unit === null) {
                $allPriced = false;
            } else {
                $total += $unit * $quantity;
            }
        }

        if ($allPriced && $quotation->items()->exists()) {
            $quotation->update(['total_minor' => $total]);
        }

        $quotation->load('items');
        Mail::to($quotation->email)->queue(new QuotationReceived($quotation));
        $support = setting('business.support_email') ?: setting('business.email');

        if ($support) {
            Mail::to($support)->queue(new QuotationReceived($quotation));
        }

        $request->session()->put('quote_access.'.$quotation->number, true);

        return redirect()->route('quotes.show', $quotation->number)
            ->with('success', 'Your quotation request has been received. Our team will respond with pricing and availability.');
    }

    public function show(Request $request, Quotation $quotation)
    {
        $user = $request->user();

        abort_unless(
            ($user && ($user->isAdmin() || $quotation->user_id === $user->id))
            || $request->session()->get('quote_access.'.$quotation->number) === true,
            403
        );

        $quotation->load('items.product');
        $meta = $this->privateMeta('Quotation '.$quotation->number, route('quotes.show', $quotation->number));

        return view('storefront.quotes.show', compact('quotation', 'meta'));
    }

    public function pdf(Request $request, Quotation $quotation)
    {
        $user = $request->user();

        abort_unless(
            ($user && ($user->isAdmin() || $quotation->user_id === $user->id))
            || $request->session()->get('quote_access.'.$quotation->number) === true,
            403
        );

        $quotation->load('items');

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'business' => [
                'name' => setting('business.name', 'Tajira'),
                'address' => setting('business.address_line'),
                'city' => setting('business.city'),
                'phone' => setting('business.phone'),
                'email' => setting('business.email'),
                'tax_pin' => setting('business.tax_pin'),
                'domain' => setting('business.domain'),
            ],
        ])->setPaper('a4');

        return $pdf->download('Tajira-quotation-'.$quotation->number.'.pdf');
    }

    private function privateMeta(string $title, string $canonical): array
    {
        return [
            'title' => $title.' | '.setting('business.name', 'Tajira'),
            'description' => 'Request a tailored quotation from Tajira.',
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
