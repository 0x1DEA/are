<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listing_media', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();
            $table->string('mls_listing_id');
            $table->string('source_url');
            $table->string('url')->nullable();

            $table->string('type')->nullable();
            $table->unsignedInteger('order')->nullable();

            $table->unsignedInteger('height');
            $table->unsignedInteger('width');

            $table->timestamp('mls_modified_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_media');
    }
};
