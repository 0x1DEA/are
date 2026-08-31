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

            $table->double('sales_price')->nullable();
            $table->double('real_estate_tax')->nullable();
            $table->double('common_charges')->nullable();

            $table->string('type')->nullable();
            $table->string('year_built')->nullable();
            $table->string('neighborhood')->nullable();

            $table->geometry('coordinates', 'point', 4326)->nullable();

            $table->json('data')->nullable();

            $table->json('geo_data')->nullable();
            $table->timestamp('geo_fetched_at')->nullable();

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
