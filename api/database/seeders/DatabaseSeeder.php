<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::updateOrCreate(['email' => 'owner@akp.local'], ['name' => 'AKP Owner', 'password' => Hash::make('AKPdemo123!'), 'role' => 'Owner', 'active' => true]);
        User::updateOrCreate(['email' => 'staff@akp.local'], ['name' => 'AKP Staff', 'password' => Hash::make('AKPdemo123!'), 'role' => 'Staff', 'active' => true]);
        DB::table('customers')->updateOrInsert(['email' => 'maria@akp.local'], ['name' => 'Maria Santos', 'phone' => '09171234567', 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()]);
        $customerId = DB::table('customers')->where('email', 'maria@akp.local')->value('id');
        $productId = DB::table('products')->where('name', 'Woven Abaca Bag')->value('id') ?: DB::table('products')->insertGetId(['name' => 'Woven Abaca Bag', 'category' => 'Bags', 'price' => 850, 'stock' => 12, 'reorder_level' => 5, 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()]);
        $orderId = DB::table('orders')->insertGetId(['customer_id' => $customerId, 'ordered_at' => now(), 'status' => 'Pending', 'total_amount' => 850, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('order_items')->insert(['order_id' => $orderId, 'product_id' => $productId, 'quantity' => 1, 'unit_price' => 850, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('payments')->insert(['order_id' => $orderId, 'method' => 'GCash', 'amount' => 850, 'status' => 'Pending Verification', 'created_at' => now(), 'updated_at' => now()]);
        $driverId = DB::table('delivery_personnel')->insertGetId(['name' => 'Juan Dela Cruz', 'email' => 'juan@akp.local', 'phone' => '09170000000', 'availability' => 'Available', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('deliveries')->insert(['order_id' => $orderId, 'delivery_personnel_id' => $driverId, 'address' => 'Quezon City, Metro Manila', 'status' => 'Pending', 'remarks' => 'Handle with care', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('returns')->insert(['order_id' => $orderId, 'customer_id' => $customerId, 'reason' => 'Sample return request', 'resolution' => 'Refund', 'amount' => 850, 'status' => 'Open', 'details' => 'Demo record for review workflow.', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('feedback')->insert(['customer_id' => $customerId, 'product_id' => $productId, 'order_id' => $orderId, 'rating' => 5, 'comment' => 'Beautiful locally made product.', 'status' => 'Visible', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('promotions')->insert(['name' => 'Native Heritage Week', 'discount_amount' => 100, 'valid_until' => now()->addDays(14)->toDateString(), 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('notifications')->insert(['title' => 'Welcome to AKP Admin', 'message' => 'Review pending orders and inventory movements.', 'audience' => 'All Staff', 'type' => 'Announcement', 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('audit_logs')->insert(['user_id' => $owner->id, 'role' => 'Owner', 'module' => 'System', 'action' => 'Seeded Chapter 4 demo records', 'ip_address' => '127.0.0.1', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('backups')->insert(['user_id' => $owner->id, 'type' => 'Database', 'size' => 0, 'status' => 'Successful', 'path' => 'demo/akp-seed.sql', 'created_at' => now(), 'updated_at' => now()]);
    }
}
