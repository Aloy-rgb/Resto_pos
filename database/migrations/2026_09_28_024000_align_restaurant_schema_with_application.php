<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'categories', 'dining_tables', 'menu_items', 'orders', 'order_items', 'payments'] as $tableName) {
            if (! Schema::hasTable($tableName)) continue;

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'created_at')) $table->timestamp('created_at')->nullable();
                if (! Schema::hasColumn($tableName, 'updated_at')) $table->timestamp('updated_at')->nullable();
            });
        }

        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'password_hash') && ! Schema::hasColumn('users', 'password')) {
                Schema::table('users', fn (Blueprint $table) => $table->renameColumn('password_hash', 'password'));
            }

            Schema::table('users', function (Blueprint $table): void {
                if (! Schema::hasColumn('users', 'username')) $table->string('username')->nullable()->unique();
                if (! Schema::hasColumn('users', 'email')) $table->string('email')->nullable()->unique();
                if (! Schema::hasColumn('users', 'email_verified_at')) $table->timestamp('email_verified_at')->nullable();
                if (! Schema::hasColumn('users', 'salary')) $table->decimal('salary', 10, 2)->nullable();
                if (! Schema::hasColumn('users', 'schedule')) $table->text('schedule')->nullable();
                if (! Schema::hasColumn('users', 'performance')) $table->text('performance')->nullable();
                if (! Schema::hasColumn('users', 'remember_token')) $table->rememberToken();
            });

            DB::table('users')->whereNull('username')->update(['username' => DB::raw("CONCAT('user-', id)")]);
            DB::table('users')->whereNull('email')->update(['email' => DB::raw("CONCAT('user-', id, '@legacy.invalid')")]);
            DB::statement('ALTER TABLE users MODIFY username VARCHAR(255) NOT NULL, MODIFY email VARCHAR(255) NOT NULL');
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','cashier','chef','waiter') NOT NULL DEFAULT 'cashier'");
            DB::table('users')->where('role', 'waiter')->update(['role' => 'cashier']);
        }

        if (Schema::hasTable('menu_items') && ! Schema::hasColumn('menu_items', 'description')) {
            Schema::table('menu_items', fn (Blueprint $table) => $table->text('description')->nullable());
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'customer_name')) $table->string('customer_name')->default('Guest');
                if (! Schema::hasColumn('orders', 'payment_status')) $table->enum('payment_status', ['pending', 'paid'])->default('pending');
                if (! Schema::hasColumn('orders', 'invoice_number')) $table->string('invoice_number')->nullable()->unique();
                if (! Schema::hasColumn('orders', 'total')) $table->decimal('total', 10, 2)->default(0);
                if (! Schema::hasColumn('orders', 'paid_at')) $table->timestamp('paid_at')->nullable();
            });

            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','preparing','cooking','ready','served','paid','cancelled') NOT NULL DEFAULT 'pending'");
            DB::table('orders')->where('status', 'preparing')->update(['status' => 'cooking']);
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','cooking','ready','served','paid','cancelled') NOT NULL DEFAULT 'pending'");
            DB::statement('UPDATE orders SET total = COALESCE((SELECT SUM(order_items.quantity * order_items.unit_price) FROM order_items WHERE order_items.order_id = orders.id), 0)');

            if (Schema::hasTable('payments')) {
                DB::statement("UPDATE orders SET payment_status = 'paid', status = 'paid', paid_at = (SELECT MAX(payments.paid_at) FROM payments WHERE payments.order_id = orders.id) WHERE EXISTS (SELECT 1 FROM payments WHERE payments.order_id = orders.id)");
                DB::statement("UPDATE orders SET invoice_number = CONCAT('INV-', id) WHERE payment_status = 'paid' AND invoice_number IS NULL");
            }
        }
    }

    public function down(): void
    {
        // This migration backfills authentication and payment data; it is intentionally irreversible.
    }
};
