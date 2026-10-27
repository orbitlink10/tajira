<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->text('description')->nullable();
            $table->text('intro')->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_override')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 140);
            $table->string('slug', 180)->unique();
            $table->string('short_name', 80)->nullable();
            $table->text('description')->nullable();
            $table->text('buying_advice')->nullable();
            $table->string('icon', 80)->nullable();
            $table->string('image_path')->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_override')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('show_in_menu')->default(true);
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['parent_id', 'position']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('primary_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('sku', 80)->unique();
            $table->string('name', 220);
            $table->string('slug', 240)->unique();
            $table->string('model', 160)->nullable()->index();
            $table->string('mpn', 160)->nullable()->index();
            $table->string('gtin', 32)->nullable()->index();
            $table->string('gtin_type', 20)->nullable();
            $table->string('product_type', 20)->default('simple')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable();

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('key_features')->nullable();
            $table->json('applications')->nullable();
            $table->text('compatibility')->nullable();
            $table->json('package_contents')->nullable();
            $table->text('setup_overview')->nullable();

            $table->unsignedBigInteger('price_minor')->nullable();
            $table->unsignedBigInteger('sale_price_minor')->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->boolean('price_on_request')->default(false);
            $table->boolean('tax_inclusive')->nullable();

            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->string('stock_status', 30)->default('out_of_stock')->index();
            $table->boolean('allow_oversell')->default(false);

            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedInteger('length_mm')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();

            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_indexable')->default(true);
            $table->string('canonical_override')->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('robots', 60)->nullable();

            $table->string('warranty_text')->nullable();
            $table->string('returns_text')->nullable();
            $table->text('disclaimer')->nullable();
            $table->text('source_notes')->nullable();
            $table->text('search_terms')->nullable();

            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index(['brand_id', 'status']);
            $table->index(['primary_category_id', 'status']);
            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'status']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'category_id']);
            $table->index(['category_id', 'is_primary']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 80)->unique();
            $table->string('name', 180);
            $table->string('mpn', 160)->nullable();
            $table->string('gtin', 32)->nullable();
            $table->unsignedBigInteger('price_minor')->nullable();
            $table->unsignedBigInteger('sale_price_minor')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->json('attributes')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('code', 80)->unique();
            $table->string('type', 20)->default('select');
            $table->string('unit', 30)->nullable();
            $table->string('group', 80)->nullable();
            $table->string('description', 320)->nullable();
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_comparable')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value', 160);
            $table->string('slug', 180);
            $table->decimal('numeric_value', 16, 4)->nullable();
            $table->string('unit', 30)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'slug']);
            $table->index(['attribute_id', 'numeric_value']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained('attribute_values')->nullOnDelete();
            $table->string('value_text', 255)->nullable();
            $table->decimal('value_number', 16, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->string('unit', 30)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'attribute_id']);
            $table->index(['attribute_id', 'attribute_value_id']);
            $table->index(['attribute_id', 'value_number']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt', 220);
            $table->string('title', 220)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->string('mime', 60)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'position']);
        });

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('title', 180);
            $table->string('type', 30)->default('datasheet');
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamps();
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 30)->default('related');
            $table->unsignedInteger('position')->default(0);
            $table->unique(['product_id', 'related_product_id', 'type']);
            $table->index(['related_product_id', 'type']);
        });

        Schema::create('product_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('question', 300);
            $table->text('answer');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('alias', 180);
            $table->string('normalized', 180)->index();
            $table->timestamps();
            $table->unique(['product_id', 'normalized']);
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 120);
            $table->string('author_email', 180)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 180)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('verified_purchase')->default(false);
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'status', 'created_at']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('type', 30);
            $table->integer('quantity');
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_aliases');
        Schema::dropIfExists('product_faqs');
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('product_documents');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
