<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_trang_don_hien_doan_text_chi_tiet_de_copy(): void
    {
        $user = User::create([
            'name' => 'Shop',
            'email' => 'shop@example.test',
            'password' => bcrypt('secret'),
        ]);

        $customer = Customer::create(['user_id' => $user->id, 'name' => 'Ánh Ngọc']);
        $aoLen = Product::create(['user_id' => $user->id, 'name' => 'Ao len xam', 'default_price' => 250000]);
        $quanKe = Product::create(['user_id' => $user->id, 'name' => 'Quan ke nau', 'default_price' => 180000]);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'status' => Order::STATUS_NEW,
            'total_amount' => 610000,
            'deposit_amount' => 100000,
            'discount_amount' => 50000,
        ]);

        $order->items()->create(['product_id' => $aoLen->id, 'size' => '90', 'quantity' => 1, 'price' => 250000]);
        $order->items()->create(['product_id' => $quanKe->id, 'size' => '80', 'quantity' => 2, 'price' => 180000]);

        $response = $this->actingAs($user)->get(route('orders.show', $order))->assertOk();

        $html = $response->getContent();

        // Hiển thị: mỗi dòng tiền (7 sản phẩm... ở đây là 2 + 4 dòng tổng) phải căn phải
        // bằng tabular-nums để chữ số cùng bề rộng, mép phải mới thẳng hàng.
        $this->assertSame(
            $order->items->count() + 4,
            substr_count($html, 'tabular-nums'),
            'Mỗi dòng sản phẩm và mỗi dòng tổng phải có một ô tiền căn phải',
        );

        $response->assertSee('Ao len xam', false);
        $response->assertSee('size 90 · ×1', false);
        $response->assertSee('Tổng tiền hàng', false);
        $response->assertSee('Tiền cọc', false);
        $response->assertSee('Giảm giá', false);
        $response->assertSee('Còn phải thanh toán', false);

        // Bản text phẳng cho nút Copy
        preg_match('#<textarea id="orderDetailText".*?>(.*?)</textarea>#s', $html, $matches);
        $copyText = html_entity_decode($matches[1] ?? '', ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString('Ao len xam (size 90 · ×1): 250,000đ', $copyText);
        // Số lượng > 1 thì ghi kèm đơn giá để khách đối chiếu được
        $this->assertStringContainsString('Quan ke nau (size 80 · ×2 · 180,000đ/cái): 360,000đ', $copyText);
        $this->assertStringContainsString('Tổng tiền hàng: 610,000đ', $copyText);
        $this->assertStringContainsString('Tiền cọc: 100,000đ', $copyText);
        $this->assertStringContainsString('Giảm giá: 50,000đ', $copyText);
        $this->assertStringContainsString('Còn phải thanh toán: 460,000đ', $copyText);

        // Card tóm tắt không được sticky, nếu không các card dưới sẽ cuộn chui xuống dưới nó
        $response->assertDontSee('sticky top-24', false);

        // Tổng các dòng phải khớp tổng tiền hàng
        $this->assertEquals(
            610000,
            $order->items->sum(fn ($item) => $item->price * $item->quantity),
        );
    }
}
