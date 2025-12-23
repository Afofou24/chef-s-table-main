<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Models\StockItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Reservation;
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

        // 6. Generate Realistic Data
        // Categories and Menu Items
        $categories = Category::factory()->count(5)->create();
        foreach ($categories as $category) {
            MenuItem::factory()->count(8)->create([
                'category_id' => $category->id
            ]);
            
            StockItem::factory()->count(10)->create([
                'category' => $category->name
            ]);
        }

        // Tables
        $tables = RestaurantTable::factory()->count(15)->create();

        // Orders and Payments
        $waiters = User::role('waiter')->get();
        if ($waiters->isEmpty()) $waiters = User::all();
        
        $cashiers = User::role('cashier')->get();
        if ($cashiers->isEmpty()) $cashiers = User::all();

        foreach ($tables as $table) {
            // Create some past orders for each table
            Order::factory()->count(3)->create([
                'table_id' => $table->id,
                'status' => 'paid',
                'waiter_id' => $waiters->random()->id
            ])->each(function ($order) use ($cashiers) {
                $items = MenuItem::all()->random(rand(2, 5));
                $total = 0;
                foreach ($items as $menuItem) {
                    $qty = rand(1, 3);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $qty,
                        'unit_price' => $menuItem->price,
                        'status' => 'served'
                    ]);
                    $total += ($menuItem->price * $qty);
                }
                
                $order->subtotal = $total;
                $order->tax_amount = $total * 0.10;
                $order->total_amount = $total + $order->tax_amount;
                $order->save();

                Payment::factory()->create([
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'cashier_id' => $cashiers->random()->id,
                    'status' => 'completed'
                ]);
            });
        }

        // Create some reservations
        Reservation::factory()->count(10)->create();
    }
}
