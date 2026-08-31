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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->name();

            $table->string('phone_home')->nullable();
            $table->string('phone_cell')->nullable();
            $table->string('phone_work')->nullable();
            $table->string('phone_fax')->nullable();

            $table->address('personal', true);
            $table->address('work', true);

            $table->date('birthday');

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
        Schema::dropIfExists('clients');
    }
};
