<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->integer('preparation_time')->nullable()->comment('Temps en minutes');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('allergens')->nullable();
            $table->integer('calories')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('is_available');
            $table->index('is_featured');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
