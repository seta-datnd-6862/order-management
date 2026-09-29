<?php

namespace Tests\Feature;

use App\Models\CampaignOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderCampaign;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCampaignTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Shop',
            'email' => 'shop@example.test',
            'password' => bcrypt('secret'),
        ]);
    }

    private function payloadJson(array $overrides = []): string
    {
        return json_encode(array_replace_recursive([
            'version' => 1,
            'name' => 'Đơn 7/9 - 12/9/26',
            'orders' => [
                [
                    'order_date' => '2026-09-07',
                    'customer_name' => 'Ánh Ngọc',
                    'customer_note' => 'fb trang',
                    'is_marked_done' => true,
                    'deposit_amount' => 100,
                    'discount_amount' => 0,
                    'warnings' => ['chưa đặt 2 dép'],
                    'raw_line' => '- ánh ngọc ord 7/9/26: 1 sét quần loe 150 👌',
                    'items' => [
                        [
                            'product_name' => 'Sét quần loe',
                            'size' => '150',
                            'quantity' => 2,
                            'price' => null,
                            'raw_text' => '1 sét quần loe 150',
                        ],
                    ],
                ],
                [
                    'order_date' => null,
                    'customer_name' => 'Khách Mới Chưa Có',
                    'is_marked_done' => false,
                    'items' => [
                        [
                            'product_name' => 'Áo chưa từng nhập',
                            'size' => '32',
                            'quantity' => 1,
                        ],
                    ],
                ],
            ],
        ], $overrides), JSON_UNESCAPED_UNICODE);
    }

    public function test_import_tao_don_nhap_va_khop_san_khach_san_pham(): void
    {
        $customer = Customer::create(['user_id' => $this->user->id, 'name' => 'Ánh Ngọc (ord 4/1/26)']);
        $product = Product::create(['user_id' => $this->user->id, 'name' => 'Sét quần loe', 'default_price' => 250000]);

        $response = $this->actingAs($this->user)->post(route('campaigns.store'), [
            'raw_json' => $this->payloadJson(),
        ]);

        $campaign = OrderCampaign::firstOrFail();
        $response->assertRedirect(route('campaigns.show', $campaign));

        $this->assertSame('Đơn 7/9 - 12/9/26', $campaign->name);
        $this->assertSame(2, $campaign->total_count);

        $first = $campaign->campaignOrders()->where('sequence', 1)->firstOrFail();

        // Tên khách trong hệ thống có hậu tố "(ord ...)" vẫn phải khớp.
        $this->assertSame($customer->id, $first->customer_id);
        $this->assertSame('exact', $first->customer_match);
        $this->assertTrue($first->is_marked_done);
        $this->assertSame('2026-09-07', $first->order_date->toDateString());

        // Cọc ghi 100 (nghìn) được quy đổi thành 100.000đ.
        $this->assertEquals(100000, $first->deposit_amount);

        $item = $first->items()->firstOrFail();
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame('150', $item->size);
        $this->assertSame(2, $item->quantity);
        // Note không ghi giá → lấy giá mặc định của sản phẩm.
        $this->assertEquals(250000, $item->price);

        // Đơn thứ hai: chưa có khách, size ngoài whitelist → giữ size_raw, để trống size.
        $second = $campaign->campaignOrders()->where('sequence', 2)->firstOrFail();
        $this->assertNull($second->customer_id);
        $secondItem = $second->items()->firstOrFail();
        $this->assertNull($secondItem->product_id);
        $this->assertSame('32', $secondItem->size_raw);
        $this->assertNull($secondItem->size);
        $this->assertFalse($second->is_ready);
        $this->assertNotEmpty($campaign->import_warnings);
    }

    public function test_json_sai_cau_truc_bi_tu_choi(): void
    {
        $this->actingAs($this->user)
            ->post(route('campaigns.store'), ['raw_json' => '{"orders": [{"customer_name": "A"}]}'])
            ->assertSessionHasErrors('raw_json');

        $this->assertSame(0, OrderCampaign::count());
    }

    public function test_json_boc_trong_dau_backtick_van_import_duoc(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), [
            'raw_json' => "```json\n" . $this->payloadJson() . "\n```",
        ]);

        $this->assertSame(2, OrderCampaign::firstOrFail()->total_count);
    }

    public function test_preview_tra_ve_so_don_va_canh_bao(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('campaigns.preview'), ['raw_json' => $this->payloadJson()])
            ->assertOk()
            ->assertJson(['success' => true, 'order_count' => 2, 'item_count' => 2]);
    }

    public function test_tao_don_hang_that_tu_don_nhap_va_cap_nhat_tien_do(): void
    {
        Customer::create(['user_id' => $this->user->id, 'name' => 'Ánh Ngọc (ord 4/1/26)']);
        Product::create(['user_id' => $this->user->id, 'name' => 'Sét quần loe', 'default_price' => 250000]);

        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 1)->firstOrFail();
        $item = $draft->items()->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('campaigns.process', [$campaign, $draft]))
            ->assertOk()
            ->assertSee('Sét quần loe');

        $response = $this->actingAs($this->user)->put(route('campaigns.orders.update', [$campaign, $draft]), [
            'action' => 'create',
            'customer_id' => $draft->customer_id,
            'order_date' => '2026-09-07',
            'deposit_amount' => 100000,
            'discount_amount' => 0,
            'note' => 'ghi chú đơn',
            'items' => [
                [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'size' => '150',
                    'quantity' => 2,
                    'price' => 250000,
                ],
            ],
        ]);

        $draft->refresh();
        $this->assertSame(CampaignOrder::STATUS_CREATED, $draft->status);
        $this->assertNotNull($draft->order_id);

        $order = Order::findOrFail($draft->order_id);
        $this->assertEquals(500000, $order->total_amount);
        $this->assertEquals(100000, $order->deposit_amount);
        $this->assertSame('2026-09-07', $order->created_at->toDateString());
        $this->assertStringContainsString('fb trang', $order->note);
        $this->assertSame(1, $order->items()->count());

        // Chuyển tiếp sang đơn nháp còn chờ
        $next = $campaign->campaignOrders()->where('sequence', 2)->firstOrFail();
        $response->assertRedirect(route('campaigns.process', [$campaign, $next]));

        $campaign->refresh();
        $this->assertSame(1, $campaign->created_count);
        $this->assertSame(1, $campaign->pending_count);
        $this->assertSame(50, $campaign->progress_percent);
    }

    public function test_khong_tao_duoc_don_khi_thieu_khach_hoac_size(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 2)->firstOrFail();
        $item = $draft->items()->firstOrFail();

        $this->actingAs($this->user)
            ->put(route('campaigns.orders.update', [$campaign, $draft]), [
                'action' => 'create',
                'items' => [['id' => $item->id, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors(['customer_id', 'items.0.product_id', 'items.0.size', 'items.0.price']);

        $this->assertSame(0, Order::count());
        $this->assertSame(CampaignOrder::STATUS_PENDING, $draft->fresh()->status);
    }

    public function test_luu_nhap_cho_phep_thieu_du_lieu_va_giu_trang_thai_cho(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 2)->firstOrFail();
        $item = $draft->items()->firstOrFail();

        $this->actingAs($this->user)
            ->put(route('campaigns.orders.update', [$campaign, $draft]), [
                'action' => 'save',
                'items' => [['id' => $item->id, 'quantity' => 3, 'size' => '90']],
            ])
            ->assertRedirect(route('campaigns.process', [$campaign, $draft]));

        $item->refresh();
        $this->assertSame(3, $item->quantity);
        $this->assertSame('90', $item->size);
        $this->assertSame(CampaignOrder::STATUS_PENDING, $draft->fresh()->status);
        $this->assertSame(0, Order::count());
    }

    public function test_validation_loi_van_giu_dong_vua_them_tay(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 2)->firstOrFail();
        $item = $draft->items()->firstOrFail();
        $product = Product::create(['user_id' => $this->user->id, 'name' => 'Quần thêm tay', 'default_price' => 120000]);

        // Dòng 2 là dòng thêm tay (không có id) và đã điền đủ; dòng 1 thiếu size → create thất bại.
        $this->actingAs($this->user)
            ->from(route('campaigns.process', [$campaign, $draft]))
            ->put(route('campaigns.orders.update', [$campaign, $draft]), [
                'action' => 'create',
                'customer_id' => null,
                'items' => [
                    ['id' => $item->id, 'product_id' => null, 'quantity' => 1],
                    ['product_name_raw' => 'Dòng thêm tay', 'product_id' => $product->id, 'size' => '90', 'quantity' => 4, 'price' => 120000],
                ],
            ])
            ->assertSessionHasErrors();

        // Không có gì được ghi vào DB
        $this->assertSame(1, $draft->items()->count());

        // Form render lại phải còn dòng thêm tay với đúng dữ liệu đã nhập
        $this->actingAs($this->user)
            ->get(route('campaigns.process', [$campaign, $draft]))
            ->assertOk()
            ->assertSee('items[1][quantity]', false)
            ->assertSee('value="4"', false)
            ->assertSee('Quần thêm tay', false);
    }

    public function test_man_hinh_xu_ly_hien_anh_san_pham_da_khop(): void
    {
        Customer::create(['user_id' => $this->user->id, 'name' => 'Ánh Ngọc']);
        Product::create([
            'user_id' => $this->user->id,
            'name' => 'Sét quần loe',
            'default_price' => 250000,
            'image' => 'products/set-quan-loe.jpg',
        ]);

        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);
        $campaign = OrderCampaign::firstOrFail();

        // Dòng đã khớp sản phẩm có ảnh → render thumbnail
        $matched = $campaign->campaignOrders()->where('sequence', 1)->firstOrFail();
        $this->actingAs($this->user)
            ->get(route('campaigns.process', [$campaign, $matched]))
            ->assertOk()
            ->assertSee('product-image-preview', false)
            ->assertSee('storage/products/set-quan-loe.jpg', false)
            ->assertSee('new-product-image-preview', false);

        // Dòng chưa khớp sản phẩm → không có ảnh nào, chỉ placeholder
        $unmatched = $campaign->campaignOrders()->where('sequence', 2)->firstOrFail();
        $this->actingAs($this->user)
            ->get(route('campaigns.process', [$campaign, $unmatched]))
            ->assertOk()
            ->assertSee('product-image-preview', false)
            ->assertDontSee('storage/products/', false);
    }

    public function test_tao_san_pham_moi_tra_ve_duong_dan_anh(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $png = base64_encode(file_get_contents(__DIR__ . '/../../public/favicon.ico') ?: 'x');

        $response = $this->actingAs($this->user)->postJson(route('chatbot.create-product'), [
            'name' => 'Sản phẩm mới từ đợt nhập',
            'default_price' => 199000,
            'image_base64' => 'data:image/png;base64,' . $png,
        ]);

        $response->assertOk()->assertJsonStructure(['product' => ['id', 'name', 'default_price', 'image_url']]);
        $this->assertNotNull($response->json('product.image_url'));
    }

    public function test_tong_ket_hien_ro_tien_coc_va_giam_gia(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 1)->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('campaigns.process', [$campaign, $draft]))
            ->assertOk()
            ->assertSee('Tổng tiền hàng:', false)
            ->assertSee('id="summaryDeposit"', false)
            ->assertSee('Tiền cọc:', false)
            ->assertSee('id="summaryDiscount"', false)
            ->assertSee('Giảm giá:', false)
            ->assertSee('Còn phải thanh toán:', false)
            // Nhãn cũ gộp chung cọc và giảm giá, dễ gây nhầm
            ->assertDontSee('Còn lại sau cọc/giảm', false);
    }

    public function test_dong_them_tay_cung_tao_duoc_san_pham_moi(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 1)->firstOrFail();

        $html = $this->actingAs($this->user)
            ->get(route('campaigns.process', [$campaign, $draft]))
            ->assertOk()
            ->getContent();

        // Template dòng thêm tay nằm trong hàm addItem() phải có đủ panel tạo sản phẩm
        $start = strpos($html, 'function addItem()');
        $this->assertNotFalse($start, 'Không tìm thấy hàm addItem()');

        $addItemJs = substr($html, $start, (int) strpos($html, 'function removeItem(') - $start);

        foreach (['toggle-new-product', 'new-product-panel', 'new-product-name', 'new-product-price',
                  'new-product-image', 'new-product-image-preview', 'save-new-product', 'cancel-new-product'] as $needle) {
            $this->assertStringContainsString($needle, $addItemJs, "Dòng thêm tay thiếu \"{$needle}\"");
        }
    }

    public function test_bo_qua_roi_mo_lai_don_nhap(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 2)->firstOrFail();
        $item = $draft->items()->firstOrFail();

        $this->actingAs($this->user)->put(route('campaigns.orders.update', [$campaign, $draft]), [
            'action' => 'skip',
            'items' => [['id' => $item->id, 'quantity' => 1]],
        ]);

        $this->assertSame(CampaignOrder::STATUS_SKIPPED, $draft->fresh()->status);

        $this->actingAs($this->user)
            ->post(route('campaigns.orders.reopen', [$campaign, $draft]))
            ->assertRedirect(route('campaigns.process', [$campaign, $draft]));

        $this->assertSame(CampaignOrder::STATUS_PENDING, $draft->fresh()->status);
    }

    public function test_don_da_tao_thi_khong_sua_duoc_nua(): void
    {
        Customer::create(['user_id' => $this->user->id, 'name' => 'Ánh Ngọc (ord 4/1/26)']);
        Product::create(['user_id' => $this->user->id, 'name' => 'Sét quần loe', 'default_price' => 250000]);

        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 1)->firstOrFail();
        $item = $draft->items()->firstOrFail();

        $payload = [
            'action' => 'create',
            'customer_id' => $draft->customer_id,
            'items' => [['id' => $item->id, 'product_id' => $item->product_id, 'size' => '150', 'quantity' => 1, 'price' => 250000]],
        ];

        $this->actingAs($this->user)->put(route('campaigns.orders.update', [$campaign, $draft]), $payload);
        $this->assertSame(1, Order::count());

        // Màn hình xử lý chuyển sang chế độ chỉ đọc
        $this->actingAs($this->user)
            ->get(route('campaigns.process', [$campaign, $draft]))
            ->assertOk()
            ->assertSee('đã tạo thành đơn hàng thật', false);

        $this->actingAs($this->user)
            ->put(route('campaigns.orders.update', [$campaign, $draft]), $payload)
            ->assertSessionHas('error');

        $this->assertSame(1, Order::count());
    }

    public function test_rematch_tim_lai_khach_va_san_pham_vua_them(): void
    {
        $this->actingAs($this->user)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);

        $campaign = OrderCampaign::firstOrFail();
        $draft = $campaign->campaignOrders()->where('sequence', 1)->firstOrFail();
        $this->assertNull($draft->customer_id);

        $customer = Customer::create(['user_id' => $this->user->id, 'name' => 'Ánh Ngọc']);
        $product = Product::create(['user_id' => $this->user->id, 'name' => 'Sét quần loe', 'default_price' => 199000]);

        $this->actingAs($this->user)->post(route('campaigns.rematch', $campaign));

        $draft->refresh();
        $this->assertSame($customer->id, $draft->customer_id);
        $this->assertSame($product->id, $draft->items()->first()->product_id);
        $this->assertEquals(199000, $draft->items()->first()->price);
    }

    public function test_khong_xem_duoc_dot_nhap_cua_nguoi_khac(): void
    {
        $other = User::create([
            'name' => 'Khác',
            'email' => 'other@example.test',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($other)->post(route('campaigns.store'), ['raw_json' => $this->payloadJson()]);
        $campaign = OrderCampaign::firstOrFail();

        $this->actingAs($this->user)->get(route('campaigns.show', $campaign))->assertNotFound();
        $this->actingAs($this->user)->get(route('campaigns.index'))->assertOk()->assertDontSee('Đơn 7/9 - 12/9/26');
    }
}
