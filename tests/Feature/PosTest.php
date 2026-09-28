<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_create_an_order_and_chef_can_complete_the_workflow(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $chef = User::factory()->create(['role' => 'chef']);
        $menuItem = MenuItem::create([
            'name' => 'House Pasta',
            'category' => 'Mains',
            'price' => 12.50,
            'is_available' => true,
        ]);

        $this->actingAs($cashier)->post(route('pos.orders.store'), [
            'customer_name' => 'Ava Lee',
            'table_number' => 7,
            'items' => [$menuItem->id => ['menu_item_id' => $menuItem->id, 'quantity' => 2]],
        ])->assertRedirect();

        $order = Order::first();
        $this->assertSame('pending', $order->status);
        $this->assertSame('25.00', $order->total);

        foreach (['cooking', 'ready', 'served'] as $status) {
            $this->actingAs($chef)->patch(route('pos.orders.status', $order), ['status' => $status])->assertRedirect();
        }

        $this->actingAs($cashier)->post(route('pos.orders.pay', $order), ['payment_method' => 'card'])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_method' => 'card',
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
        ]);
    }

    public function test_cashier_cannot_change_kitchen_status(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $order = Order::create(['customer_name' => 'Ava Lee', 'table_number' => 7, 'total' => 10, 'status' => 'pending']);

        $this->actingAs($cashier)
            ->patch(route('pos.orders.status', $order), ['status' => 'cooking'])
            ->assertForbidden();
    }

    public function test_manager_cannot_change_kitchen_status(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $order = Order::create(['customer_name' => 'Ava Lee', 'table_number' => 7, 'total' => 10, 'status' => 'pending']);

        $this->actingAs($manager)
            ->patch(route('pos.orders.status', $order), ['status' => 'cooking'])
            ->assertForbidden();
    }

    public function test_manager_sees_sales_oversight_without_operational_pos_interface(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Daily sales oversight')
            ->assertSee('Payment mix')
            ->assertSee('Latest paid transactions')
            ->assertDontSee('Start an order')
            ->assertDontSee('Orders in service');
    }

    public function test_cashier_can_change_an_active_order(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $original = MenuItem::create(['name' => 'Soup', 'category' => 'Starter', 'price' => 8, 'is_available' => true]);
        $replacement = MenuItem::create(['name' => 'Salad', 'category' => 'Starter', 'price' => 6, 'is_available' => true]);
        $order = Order::create(['customer_name' => 'Ava Lee', 'table_number' => 7, 'total' => 8, 'status' => 'cooking']);
        $order->items()->create(['menu_item_id' => $original->id, 'quantity' => 1, 'unit_price' => 8, 'line_total' => 8]);

        $this->actingAs($cashier)->put(route('pos.orders.update', $order), [
            'customer_name' => 'Ava Lee',
            'table_number' => 9,
            'items' => [$replacement->id => ['menu_item_id' => $replacement->id, 'quantity' => 2]],
        ])->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'table_number' => 9, 'status' => 'pending', 'total' => 12]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'menu_item_id' => $replacement->id, 'quantity' => 2]);
    }

    public function test_cashier_can_cancel_an_active_order_but_not_a_paid_order(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $order = Order::create(['customer_name' => 'Ava Lee', 'table_number' => 7, 'total' => 10, 'status' => 'pending']);

        $this->actingAs($cashier)->post(route('pos.orders.cancel', $order))->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);

        $paidOrder = Order::create(['customer_name' => 'Ben Lee', 'table_number' => 8, 'total' => 10, 'status' => 'paid', 'payment_status' => 'paid']);
        $this->actingAs($cashier)->post(route('pos.orders.cancel', $paidOrder))->assertStatus(422);
    }

    public function test_cashier_cannot_create_an_order_at_an_occupied_table(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $menuItem = MenuItem::create(['name' => 'Soup', 'category' => 'Starter', 'price' => 8, 'is_available' => true]);
        Order::create(['customer_name' => 'Existing Guest', 'table_number' => 4, 'total' => 8, 'status' => 'cooking']);

        $this->actingAs($cashier)->post(route('pos.orders.store'), [
            'customer_name' => 'New Guest',
            'table_number' => 4,
            'items' => [$menuItem->id => ['menu_item_id' => $menuItem->id, 'quantity' => 1]],
        ])->assertSessionHasErrors(['table_number' => 'Table occupied']);
    }

    public function test_paid_order_releases_its_table_for_a_new_order(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $menuItem = MenuItem::create(['name' => 'Soup', 'category' => 'Starter', 'price' => 8, 'is_available' => true]);
        $paidOrder = Order::create(['customer_name' => 'Existing Guest', 'table_number' => 4, 'total' => 8, 'status' => 'paid', 'payment_status' => 'paid']);

        $this->actingAs($cashier)->post(route('pos.orders.store'), [
            'customer_name' => 'New Guest',
            'table_number' => 4,
            'items' => [$menuItem->id => ['menu_item_id' => $menuItem->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['table_number' => 4, 'customer_name' => 'New Guest', 'status' => 'pending']);
    }
}
