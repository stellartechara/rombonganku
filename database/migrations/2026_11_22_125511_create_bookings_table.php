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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_code', 12)->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->date('trip_date');
            $table->unsignedSmallInteger('days')->default(1);
            $table->unsignedSmallInteger('passenger_count');
            $table->string('status')->default('permintaan');
            $table->unsignedBigInteger('deal_price')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
