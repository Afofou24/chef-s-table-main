<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('restaurant_tables')->onDelete('cascade');
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->integer('guests_count');
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->integer('duration')->default(120)->comment('Durée en minutes');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('table_id');
            $table->index('reservation_date');
            $table->index('status');
            $table->index(['reservation_date', 'reservation_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
