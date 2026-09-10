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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();

            $table->string('mls_id')->unique();
            $table->string('listing_agent_mls_id')->nullable();

            $table->address(nullable: true);

            $table->double('living_area_sq_ft')->nullable();
            $table->double('acres_lot')->nullable();

            $table->unsignedInteger('bedrooms')->nullable();
            $table->unsignedInteger('total_bathrooms')->nullable();
            $table->unsignedInteger('full_bathrooms')->nullable();
            $table->unsignedInteger('half_bathrooms')->nullable();

            $table->double('sale_price_original')->nullable();
            $table->double('sale_price')->nullable();
            $table->double('rent_price_original')->nullable();
            $table->double('rent_price')->nullable();
            $table->double('real_estate_tax')->nullable();
            $table->double('common_charges')->nullable();

            $table->string('status')->nullable();

            $table->text('public_remarks')->nullable();
            $table->text('private_remarks')->nullable();

            $table->string('type')->nullable();
            $table->string('year_built')->nullable();
            $table->string('neighborhood')->nullable();

            $table->boolean('feed_idx')->default(false);
            $table->boolean('feed_vow')->default(false);
            $table->boolean('feed_bo')->default(false);
            $table->boolean('feed_pt')->default(false);

            $table->geometry('coordinates', 'point', 4326)->nullable();

            $table->json('mls_data')->nullable();
            $table->timestamp('mls_fetched_at')->nullable();

            $table->json('geo_data')->nullable();
            $table->double('geo_accuracy')->nullable();
            $table->timestamp('geo_fetched_at')->nullable();

            $table->timestamp('listed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
