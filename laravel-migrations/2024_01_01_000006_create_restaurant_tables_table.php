<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('number', 10)->unique();
            $table->integer('capacity');
            $table->string('location', 50)->nullable()->comment('interieur, terrasse, prive');
            $table->enum('status', ['available', 'occupied', 'reserved', 'unavailable'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('capacity');
            $table->index('location');
        });

        // Insert default tables
        $tables = [];
        for ($i = 1; $i <= 15; $i++) {
            $tables[] = [
                'number' => 'T' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'capacity' => $i <= 5 ? 2 : ($i <= 10 ? 4 : 6),
                'location' => $i <= 10 ? 'interieur' : 'terrasse',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('restaurant_tables')->insert($tables);
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
