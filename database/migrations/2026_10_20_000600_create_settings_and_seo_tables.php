<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group', 60)->default('general')->index();
            $table->string('label', 180)->nullable();
            $table->text('help')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seo_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->default('filter');
            $table->string('title', 220);
            $table->string('slug', 220)->unique();
            $table->text('intro')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->json('filters')->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_landing_pages');
        Schema::dropIfExists('settings');
    }
};
