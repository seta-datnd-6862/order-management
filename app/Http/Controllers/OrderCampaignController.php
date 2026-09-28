<?php

namespace App\Http\Controllers;

use App\Models\CampaignOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderCampaign;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CampaignImportService;
use App\Services\CampaignPromptBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderCampaignController extends Controller
{
    public function __construct(private CampaignImportService $importService)
    {
    }

    public function index()
    {
        $campaigns = OrderCampaign::where('user_id', Auth::id())
            ->withCount([
                'campaignOrders as total_orders',
                'campaignOrders as created_orders' => fn ($q) => $q->where('status', CampaignOrder::STATUS_CREATED),
                'campaignOrders as skipped_orders' => fn ($q) => $q->where('status', CampaignOrder::STATUS_SKIPPED),
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaigns.create', [
            'prompt' => CampaignPromptBuilder::prompt(),
        ]);
    }

    /**
     * Kiểm tra JSON trước khi import (gọi bằng AJAX từ form tạo đợt nhập).
     */
    public function preview(Request $request)
    {
        $request->validate(['raw_json' => 'required|string']);

        try {
            $preview = $this->importService->preview($request->input('raw_json'), Auth::id());
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()['raw_json'][0] ?? 'JSON không hợp lệ.',
            ], 422);
        }

        return response()->json(['success' => true] + $preview);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'raw_json' => 'required|string',
            'source_note' => 'nullable|string',
        ]);

        $campaign = $this->importService->import(
            $validated['raw_json'],
            Auth::id(),
            $validated['name'] ?? null,
            $validated['source_note'] ?? null,
        );

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Đã nhập ' . $campaign->total_count . ' đơn nháp. Bắt đầu tạo từng đơn nhé!');
    }

    public function show(Request $request, OrderCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);

        $query = $campaign->campaignOrders()->with(['items.product', 'customer', 'order']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('marked')) {
            $query->where('is_marked_done', $request->marked === 'done');
        }

        $campaignOrders = $query->get();
        $nextPending = $campaign->nextPending();

        return view('campaigns.show', [
            'campaign' => $campaign,
            'campaignOrders' => $campaignOrders,
            'nextPending' => $nextPending,
            'statuses' => CampaignOrder::getStatuses(),
        ]);
    }

    public function destroy(OrderCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);

        $campaign->delete();

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Đã xoá đợt nhập. Các đơn hàng đã tạo vẫn được giữ nguyên.');
    }

    /**
     * Khớp lại khách / sản phẩm cho các đơn còn chờ.
     */
    public function rematch(OrderCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);

        $touched = $this->importService->rematch($campaign);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', $touched > 0
                ? "Đã khớp lại {$touched} mục."
                : 'Không tìm thêm được khách/sản phẩm nào khớp.');
    }

    /**
     * Mở đơn nháp đang chờ đầu tiên.
     */
    public function start(OrderCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);

        $next = $campaign->nextPending();

        if (!$next) {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('info', 'Đợt nhập này đã xử lý xong tất cả đơn.');
        }

        return redirect()->route('campaigns.process', [$campaign, $next]);
    }

    /**
     * Màn hình xử lý một đơn nháp.
     */
    public function process(OrderCampaign $campaign, CampaignOrder $campaignOrder)
    {
        $this->authorizeCampaign($campaign);
        $this->authorizeCampaignOrder($campaign, $campaignOrder);

        $campaignOrder->load(['items.product', 'customer', 'order']);

        return view('campaigns.process', [
            'rows' => $this->buildRows($campaignOrder),
            'campaign' => $campaign,
            'campaignOrder' => $campaignOrder,
            'customers' => Customer::where('user_id', Auth::id())->orderBy('name')->get(),
            'products' => Product::where('user_id', Auth::id())->orderBy('name')->get(),
            'sizes' => OrderItem::getSizes(),
            'prevOrder' => $campaign->campaignOrders()
                ->where('sequence', '<', $campaignOrder->sequence)
                ->orderByDesc('sequence')
                ->first(),
            'nextOrder' => $campaign->campaignOrders()
                ->where('sequence', '>', $campaignOrder->sequence)
                ->orderBy('sequence')
                ->first(),
        ]);
    }

    /**
     * Lưu nháp / bỏ qua / tạo đơn thật. Phân biệt bằng input "action".
     */
    public function update(Request $request, OrderCampaign $campaign, CampaignOrder $campaignOrder)
    {
        $this->authorizeCampaign($campaign);
        $this->authorizeCampaignOrder($campaign, $campaignOrder);

        $action = $request->input('action', 'save');

        if (!in_array($action, ['save', 'skip', 'create'], true)) {
            return back()->with('error', 'Hành động không hợp lệ.');
        }

        if ($campaignOrder->status === CampaignOrder::STATUS_CREATED) {
            return redirect()
                ->route('campaigns.process', [$campaign, $campaignOrder])
                ->with('error', 'Đơn nháp này đã tạo đơn hàng thật, không sửa được nữa.');
        }

        $validated = $this->validateDraft($request, strict: $action === 'create');

        DB::beginTransaction();
        try {
            $this->syncDraft($campaignOrder, $validated);

            if ($action === 'create') {
                $order = $this->createRealOrder($campaignOrder, $validated);

                $campaignOrder->update([
                    'status' => CampaignOrder::STATUS_CREATED,
                    'order_id' => $order->id,
                    'processed_at' => now(),
                ]);
            } elseif ($action === 'skip') {
                $campaignOrder->update([
                    'status' => CampaignOrder::STATUS_SKIPPED,
                    'processed_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }

        $campaign->refresh()->refreshStatus();

        if ($action === 'save') {
            return redirect()
                ->route('campaigns.process', [$campaign, $campaignOrder])
                ->with('success', 'Đã lưu nháp đơn #' . $campaignOrder->sequence . '.');
        }

        $message = $action === 'create'
            ? 'Đã tạo đơn hàng #' . $campaignOrder->fresh()->order_id . ' cho ' . $campaignOrder->customer_name_raw . '.'
            : 'Đã bỏ qua đơn của ' . $campaignOrder->customer_name_raw . '.';

        $next = $campaign->nextPending($campaignOrder->sequence);

        if (!$next) {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('success', $message . ' Đợt nhập đã xử lý xong tất cả đơn!');
        }

        return redirect()
            ->route('campaigns.process', [$campaign, $next])
            ->with('success', $message);
    }

    /**
     * Đưa đơn đã bỏ qua trở lại trạng thái chờ.
     */
    public function reopen(OrderCampaign $campaign, CampaignOrder $campaignOrder)
    {
        $this->authorizeCampaign($campaign);
        $this->authorizeCampaignOrder($campaign, $campaignOrder);

        if ($campaignOrder->status !== CampaignOrder::STATUS_SKIPPED) {
            return back()->with('error', 'Chỉ mở lại được đơn đã bỏ qua.');
        }

        $campaignOrder->update([
            'status' => CampaignOrder::STATUS_PENDING,
            'processed_at' => null,
        ]);

        $campaign->refresh()->refreshStatus();

        return redirect()
            ->route('campaigns.process', [$campaign, $campaignOrder])
            ->with('success', 'Đã mở lại đơn của ' . $campaignOrder->customer_name_raw . '.');
    }

    /**
     * Danh sách dòng sản phẩm để render form.
     *
     * Khi validation thất bại, dựng lại từ old input thay vì từ DB, để không mất
     * những dòng người dùng vừa thêm tay và giữ đúng những dòng họ đã xoá.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(CampaignOrder $campaignOrder): array
    {
        $dbItems = $campaignOrder->items->keyBy('id');
        $oldItems = old('items');

        if (!is_array($oldItems) || $oldItems === []) {
            return $campaignOrder->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name_raw' => $item->product_name_raw,
                'product_match' => $item->product_match,
                'product_suggestions' => $item->product_suggestions ?? [],
                'size' => $item->size,
                'size_raw' => $item->size_raw,
                'quantity' => $item->quantity,
                'price' => $item->price !== null ? (int) $item->price : null,
                'note' => $item->note,
                'raw_text' => $item->raw_text,
            ])->values()->all();
        }

        return collect($oldItems)->map(function ($row) use ($dbItems) {
            $source = !empty($row['id']) ? $dbItems->get((int) $row['id']) : null;

            return [
                'id' => $source?->id,
                'product_id' => $row['product_id'] ?? null,
                'product_name_raw' => $row['product_name_raw'] ?? $source?->product_name_raw ?? 'Dòng thêm tay',
                'product_match' => $source?->product_match ?? CampaignOrder::MATCH_NONE,
                'product_suggestions' => $source?->product_suggestions ?? [],
                'size' => $row['size'] ?? null,
                'size_raw' => $source?->size_raw,
                'quantity' => (int) ($row['quantity'] ?? 1),
                'price' => ($row['price'] ?? null) !== null && $row['price'] !== '' ? (int) $row['price'] : null,
                'note' => $row['note'] ?? null,
                'raw_text' => $source?->raw_text,
            ];
        })->values()->all();
    }

    /**
     * @throws ValidationException
     */
    private function validateDraft(Request $request, bool $strict): array
    {
        $customerRule = $strict
            ? 'required|exists:customers,id'
            : 'nullable|exists:customers,id';

        $productRule = $strict
            ? 'required|exists:products,id'
            : 'nullable|exists:products,id';

        $sizeRule = $strict
            ? 'required|in:' . implode(',', OrderItem::getSizes())
            : 'nullable|in:' . implode(',', OrderItem::getSizes());

        return $request->validate([
            'customer_id' => $customerRule,
            'order_date' => 'nullable|date',
            'deposit_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.product_id' => $productRule,
            'items.*.product_name_raw' => 'nullable|string|max:255',
            'items.*.size' => $sizeRule,
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => $strict ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'items.*.note' => 'nullable|string',
        ], [
            'customer_id.required' => 'Hãy chọn khách hàng (hoặc tạo mới) trước khi tạo đơn.',
            'items.*.product_id.required' => 'Còn dòng chưa chọn sản phẩm.',
            'items.*.size.required' => 'Còn dòng chưa chọn size.',
            'items.*.price.required' => 'Còn dòng chưa có giá.',
        ]);
    }

    /**
     * Ghi lại trạng thái đơn nháp sau khi người dùng chỉnh sửa trên màn hình xử lý.
     */
    private function syncDraft(CampaignOrder $campaignOrder, array $validated): void
    {
        $campaignOrder->update([
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_match' => ($validated['customer_id'] ?? null) ? CampaignOrder::MATCH_EXACT : CampaignOrder::MATCH_NONE,
            'order_date' => $validated['order_date'] ?? null,
            'deposit_amount' => $validated['deposit_amount'] ?? 0,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'note' => $validated['note'] ?? null,
        ]);

        $keptIds = [];

        foreach (array_values($validated['items']) as $index => $itemData) {
            $attributes = [
                'sequence' => $index + 1,
                'product_id' => $itemData['product_id'] ?? null,
                'product_match' => ($itemData['product_id'] ?? null) ? CampaignOrder::MATCH_EXACT : CampaignOrder::MATCH_NONE,
                'size' => $itemData['size'] ?? null,
                'quantity' => (int) $itemData['quantity'],
                'price' => $itemData['price'] ?? null,
                'note' => $itemData['note'] ?? null,
            ];

            $existing = !empty($itemData['id'])
                ? $campaignOrder->items()->find($itemData['id'])
                : null;

            if ($existing) {
                $existing->update($attributes);
                $keptIds[] = $existing->id;
                continue;
            }

            $created = $campaignOrder->items()->create($attributes + [
                'product_name_raw' => $itemData['product_name_raw'] ?? 'Dòng thêm tay',
            ]);
            $keptIds[] = $created->id;
        }

        $campaignOrder->items()->whereNotIn('id', $keptIds)->delete();
        $campaignOrder->load('items');
    }

    private function createRealOrder(CampaignOrder $campaignOrder, array $validated): Order
    {
        $totalAmount = 0;

        foreach ($validated['items'] as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }

        $noteParts = array_filter([
            $validated['note'] ?? null,
            $campaignOrder->customer_note_raw ? 'Khách: ' . $campaignOrder->customer_note_raw : null,
        ]);

        $order = Order::create([
            'user_id' => Auth::id(),
            'customer_id' => $validated['customer_id'],
            'status' => Order::STATUS_NEW,
            'total_amount' => $totalAmount,
            'deposit_amount' => $validated['deposit_amount'] ?? 0,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'note' => $noteParts ? implode(' | ', $noteParts) : null,
            'created_at' => !empty($validated['order_date'])
                ? Carbon::parse($validated['order_date'])
                : now(),
        ]);

        foreach ($validated['items'] as $itemData) {
            $order->items()->create([
                'product_id' => $itemData['product_id'],
                'size' => $itemData['size'],
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
                'note' => $itemData['note'] ?? null,
            ]);
        }

        return $order;
    }

    private function authorizeCampaign(OrderCampaign $campaign): void
    {
        if ($campaign->user_id !== Auth::id()) {
            throw new NotFoundHttpException();
        }
    }

    private function authorizeCampaignOrder(OrderCampaign $campaign, CampaignOrder $campaignOrder): void
    {
        if ($campaignOrder->order_campaign_id !== $campaign->id) {
            throw new NotFoundHttpException();
        }
    }
}
