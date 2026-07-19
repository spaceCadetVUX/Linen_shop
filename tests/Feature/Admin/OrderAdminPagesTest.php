<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderInquiryChannel;
use App\Enums\OrderInquiryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderInquiry;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_list_and_view_pages_load(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $order = Order::create([
            'user_id' => $admin->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 100000,
            'shipping_address' => [
                'full_name' => 'Test',
                'phone' => '0123456789',
                'address_line' => '123 Test St',
                'city' => 'Hanoi',
                'province' => 'Hanoi',
            ],
            'payment_method' => 'cod',
            'payment_status' => PaymentStatus::Unpaid,
            'note' => null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Test product',
            'product_sku' => 'SKU1',
            'quantity' => 1,
            'unit_price' => 100000,
        ]);

        $this->actingAs($admin)->get('/admin/orders')->assertOk();
        $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
    }

    public function test_order_inquiries_list_page_loads(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        OrderInquiry::create([
            'user_id' => null,
            'session_id' => 'sess-1',
            'name' => 'Test Customer',
            'phone' => '0123456789',
            'email' => 'test@example.com',
            'message' => '1x Test product',
            'channel' => OrderInquiryChannel::Zalo,
            'status' => OrderInquiryStatus::New,
        ]);

        $this->actingAs($admin)->get('/admin/order-inquiries')->assertOk();
    }
}
