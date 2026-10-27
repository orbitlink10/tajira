<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('source_url', 700)->nullable()->after('mime');
            $table->string('credit', 255)->nullable()->after('source_url');
            $table->string('rights_note', 500)->nullable()->after('credit');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'credit', 'rights_note']);
        });
    }
};
