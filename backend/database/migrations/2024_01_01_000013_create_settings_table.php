<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('type', 20)->default('string')->comment('string, boolean, integer, json');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('group');
        });

        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'restaurant_name', 'value' => 'Mon Restaurant', 'group' => 'general', 'type' => 'string', 'description' => 'Nom du restaurant', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'restaurant_address', 'value' => '123 Rue de la Gastronomie, 75001 Paris', 'group' => 'general', 'type' => 'string', 'description' => 'Adresse du restaurant', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'restaurant_phone', 'value' => '+33 1 23 45 67 89', 'group' => 'general', 'type' => 'string', 'description' => 'Téléphone du restaurant', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'restaurant_email', 'value' => 'contact@monrestaurant.fr', 'group' => 'general', 'type' => 'string', 'description' => 'Email du restaurant', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'tax_rate', 'value' => '10', 'group' => 'financial', 'type' => 'integer', 'description' => 'Taux de TVA (%)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency', 'value' => 'EUR', 'group' => 'financial', 'type' => 'string', 'description' => 'Devise', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'opening_time', 'value' => '11:00', 'group' => 'hours', 'type' => 'string', 'description' => 'Heure d\'ouverture', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'closing_time', 'value' => '23:00', 'group' => 'hours', 'type' => 'string', 'description' => 'Heure de fermeture', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
