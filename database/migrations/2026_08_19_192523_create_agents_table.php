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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->name();

            $table->string('headshot_url')->nullable();
            $table->string('bio')->nullable();

            $table->string('phone_home')->nullable();
            $table->string('phone_cell')->nullable();
            $table->string('phone_work')->nullable();
            $table->string('phone_fax')->nullable();

            $table->string('type')->unique();

            $table->address('personal', true);

            $table->boolean('is_active');

            $table->timestamp('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
