<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin
        $adminUser = User::create([
            'username' => 'admin',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'admin@chefstable.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $adminUser->roles()->attach(Role::where('code', 'admin')->first());

        // 2. Manager
        $managerUser = User::create([
            'username' => 'manager',
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'email' => 'manager@chefstable.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $managerUser->roles()->attach(Role::where('code', 'manager')->first());

        // 3. Waiter (Serveur)
        $waiterUser = User::create([
            'username' => 'waiter',
            'first_name' => 'Paul',
            'last_name' => 'Verlaine',
            'email' => 'waiter@chefstable.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $waiterUser->roles()->attach(Role::where('code', 'waiter')->first());

        // 4. Cook (Cuisinier)
        $cookUser = User::create([
            'username' => 'cook',
            'first_name' => 'Auguste',
            'last_name' => 'Escoffier',
            'email' => 'cook@chefstable.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $cookUser->roles()->attach(Role::where('code', 'cook')->first());

        // 5. Cashier (Caissier)
        $cashierUser = User::create([
            'username' => 'cashier',
            'first_name' => 'Victor',
            'last_name' => 'Hugo',
            'email' => 'cashier@chefstable.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $cashierUser->roles()->attach(Role::where('code', 'cashier')->first());
    }
}
