<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->json('counties')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->string('name', 120);
            $table->string('code', 80)->unique();
            $table->string('type', 20)->default('flat');
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->unsignedBigInteger('min_order_minor')->nullable();
            $table->unsignedBigInteger('max_order_minor')->nullable();
            $table->string('lead_time', 120)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 20)->default('percentage');
            $table->unsignedBigInteger('value')->default(0);
            $table->unsignedBigInteger('min_subtotal_minor')->nullable();
            $table->unsignedBigInteger('max_discount_minor')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->json('applies_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80)->nullable();
            $table->string('recipient', 140);
            $table->string('phone', 30);
            $table->string('county', 80);
            $table->string('town', 120);
            $table->string('address_line', 255);
            $table->string('postal_code', 30)->nullable();
            $table->string('delivery_notes', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('payment_status', 30)->default('pending')->index();
            $table->string('currency', 3)->default('KES');
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('shipping_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->string('coupon_code', 40)->nullable();
            $table->string('customer_name', 140);
            $table->string('customer_email', 180)->index();
            $table->string('customer_phone', 30);
            $table->string('company', 180)->nullable();
            $table->string('county', 80);
            $table->string('town', 120);
            $table->string('address_line', 255);
            $table->string('postal_code', 30)->nullable();
            $table->string('delivery_notes', 500)->nullable();
            $table->string('delivery_type', 20)->default('delivery');
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
            $table->string('shipping_method_name', 120)->nullable();
            $table->string('shipping_lead_time', 120)->nullable();
            $table->boolean('tax_inclusive')->default(true);
            $table->unsignedInteger('tax_rate_bp')->default(0);
            $table->string('payment_method', 30)->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('tracking_number', 120)->nullable();
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('stock_committed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['customer_email', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('name', 220);
            $table->string('sku', 80);
            $table->string('brand_name', 120)->nullable();
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_subtotal_minor');
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('line_total_minor');
            $table->boolean('tax_inclusive')->default(true);
            $table->json('attributes')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->index('order_id');
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('method', 30);
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('KES');
            $table->string('provider_reference', 120)->nullable()->index();
            $table->string('merchant_reference', 120)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('provider', 30);
            $table->string('event_id', 180)->nullable()->unique();
            $table->string('type', 60);
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->index(['product_id', 'status']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 180)->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['coupon_id', 'order_id']);
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 140);
            $table->string('email', 180)->index();
            $table->string('phone', 30);
            $table->string('company', 180)->nullable();
            $table->string('county', 80)->nullable();
            $table->string('town', 120)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->unsignedBigInteger('total_minor')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('name', 220);
            $table->string('sku', 80)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_minor')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');
    }
};
