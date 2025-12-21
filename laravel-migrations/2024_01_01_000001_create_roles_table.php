<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['name' => 'Administrateur', 'code' => 'admin', 'description' => 'Accès complet au système', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gérant', 'code' => 'manager', 'description' => 'Gestion du restaurant', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Serveur', 'code' => 'waiter', 'description' => 'Prise de commandes et service', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cuisinier', 'code' => 'cook', 'description' => 'Préparation des plats', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Caissier', 'code' => 'cashier', 'description' => 'Gestion des paiements', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
