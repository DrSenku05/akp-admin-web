<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('Staff')->after('email');
            }
            if (!Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true)->after('role');
            }
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('email', 160)->unique();
            $table->string('phone', 40)->nullable();
            $table->string('status', 30)->default('Active');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->dateTime('ordered_at');
            $table->string('status', 30)->default('Pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
            $table->unique(['order_id', 'product_id']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('status', 40)->default('Pending Verification');
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_personnel', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('email', 160)->nullable()->unique();
            $table->string('phone', 40)->nullable();
            $table->string('availability', 30)->default('Available');
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_personnel_id')->constrained('delivery_personnel')->restrictOnDelete();
            $table->string('address', 255);
            $table->string('status', 30)->default('Pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->integer('quantity');
            $table->unsignedInteger('stock_after');
            $table->string('reason', 240);
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 160);
            $table->string('resolution', 40);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 30)->default('Open');
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 30)->default('Visible');
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->decimal('discount_amount', 12, 2);
            $table->date('valid_until');
            $table->string('status', 30)->default('Active');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->text('message');
            $table->string('audience', 40)->default('All Customers');
            $table->string('type', 40)->default('Announcement');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 30)->nullable();
            $table->string('module', 80);
            $table->string('action', 160);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('backups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status', 30)->default('Successful');
            $table->string('path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['backups', 'audit_logs', 'notifications', 'promotions', 'feedback', 'returns', 'inventory_movements', 'deliveries', 'delivery_personnel', 'payments', 'order_items', 'orders', 'customers', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'active']);
        });
    }
};
