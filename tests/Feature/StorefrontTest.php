<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Quotation;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enablePickup();
    }

    public function test_home_category_and_brand_pages_render_initial_html(): void
    {
        $product = $this->product(['name' => 'Storefront Test Router', 'is_featured' => true]);
        $category = $product->primaryCategory;

        $this->get('/')->assertOk()->assertSee('Storefront Test Router');
        $this->get(route('categories.show', $category))->assertOk()->assertSee('Storefront Test Router');
        $this->get(route('brands.show', $product->brand))->assertOk()->assertSee('Storefront Test Router');
        $this->get(route('products.index'))->assertOk()->assertSee('Storefront Test Router');
    }

    public function test_guest_cart_accepts_purchasable_product_and_shows_quantity(): void
    {
        $product = $this->product(['name' => 'Cart Test Product']);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Cart Test Product')
            ->assertSee('value="2"', false);
    }

    public function test_quote_only_product_cannot_be_added_to_cart(): void
    {
        $product = $this->product(['price_minor' => null, 'price_on_request' => true]);

        $this->post(route('cart.add'), ['product_id' => $product->id])
            ->assertRedirect(route('quotes.create', ['product' => $product->id]));

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_guest_can_place_an_order_with_pickup_and_track_it(): void
    {
        $product = $this->product(['price_minor' => 100000, 'stock_quantity' => 2]);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $checkout = $this->get(route('checkout.index'))->assertOk();
        $token = session('checkout_token');
        $this->assertNotNull($token);

        $response = $this->post(route('checkout.store'), $this->checkoutData([
            'checkout_token' => $token,
            'payment_method' => 'manual',
        ]));

        $response->assertRedirect();
        $order = Order::query()->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame(1, $order->items()->count());

        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->number);
    }

    public function test_quote_request_creates_quotation_and_records_access(): void
    {
        $product = $this->product(['price_minor' => null, 'price_on_request' => true]);

        $response = $this->post(route('quotes.store'), [
            'name' => 'Project Buyer',
            'email' => 'buyer@example.test',
            'phone' => '0712345678',
            'message' => 'Please quote for a school network.',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ]);

        $quotation = Quotation::query()->latest()->first();

        $this->assertNotNull($quotation);
        $this->assertSame(4, $quotation->items()->first()->quantity);
        $response->assertRedirect(route('quotes.show', $quotation));
        $this->get(route('quotes.show', $quotation))->assertOk();
    }
}
