<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 60)->unique();
            $table->string('type', 30)->default('discovery');
            $table->string('source', 255)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->json('params')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_checkpoint_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('source_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('source_url', 700)->unique();
            $table->string('source_site', 160)->nullable();
            $table->string('external_id', 120)->nullable();
            $table->string('raw_name', 255)->nullable();
            $table->string('raw_brand', 120)->nullable();
            $table->string('raw_model', 160)->nullable();
            $table->string('raw_mpn', 160)->nullable();
            $table->string('raw_gtin', 64)->nullable();
            $table->unsignedBigInteger('raw_price_minor')->nullable();
            $table->string('raw_currency', 10)->nullable();
            $table->json('raw_categories')->nullable();
            $table->json('raw_attributes')->nullable();
            $table->longText('raw_description')->nullable();
            $table->json('raw_image_urls')->nullable();
            $table->timestamp('source_lastmod')->nullable();
            $table->timestamp('discovered_at')->nullable()->index();
            $table->timestamp('fetched_at')->nullable();
            $table->string('content_hash', 64)->nullable()->index();
            $table->string('proposed_sku', 80)->nullable();
            $table->string('status', 30)->default('discovered')->index();
            $table->string('disposition', 30)->nullable()->index();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('source_products')->nullOnDelete();
            $table->json('errors')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['raw_brand', 'status']);
            $table->index(['status', 'discovered_at']);
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->json('errors')->nullable();
            $table->foreignId('source_product_id')->nullable()->constrained('source_products')->nullOnDelete();
            $table->timestamps();
            $table->unique(['import_batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('source_products');
        Schema::dropIfExists('import_batches');
    }
};
