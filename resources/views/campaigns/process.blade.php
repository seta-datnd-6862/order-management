@extends('layouts.app')

@section('title', 'Xử lý đơn #' . $campaignOrder->sequence)

@section('content')
@php
    $total = $campaign->total_count;
    $percent = $campaign->progress_percent;
    $isCreated = $campaignOrder->status === \App\Models\CampaignOrder::STATUS_CREATED;
    $productImages = $products->pluck('image_url', 'id');
    $productsPayload = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'price' => (int) $p->default_price,
        'image' => $p->image_url,
    ])->values();
@endphp

{{-- Thanh tiến độ --}}
<div class="bg-white rounded-lg shadow p-4 mb-6 sticky top-16 z-40">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('campaigns.show', $campaign) }}" class="text-gray-500 hover:text-gray-700 shrink-0">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="min-w-0">
                <p class="font-semibold text-gray-800 truncate">{{ $campaign->name }}</p>
                <p class="text-xs text-gray-500">
                    Đơn <strong>#{{ $campaignOrder->sequence }}</strong>/{{ $total }} ·
                    <span class="text-green-600">{{ $campaign->created_count }} đã tạo</span> ·
                    <span class="text-yellow-600">{{ $campaign->pending_count }} còn chờ</span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1 shrink-0">
            @if($prevOrder)
            <a href="{{ route('campaigns.process', [$campaign, $prevOrder]) }}"
               class="px-3 py-1.5 border rounded-lg text-gray-600 hover:bg-gray-50 text-sm" title="Đơn trước">
                <i class="fas fa-chevron-left"></i>
            </a>
            @endif
            @if($nextOrder)
            <a href="{{ route('campaigns.process', [$campaign, $nextOrder]) }}"
               class="px-3 py-1.5 border rounded-lg text-gray-600 hover:bg-gray-50 text-sm" title="Đơn sau">
                <i class="fas fa-chevron-right"></i>
            </a>
            @endif
        </div>
    </div>

    <div class="w-full bg-gray-200 rounded-full h-2">
        <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $percent }}%"></div>
    </div>
</div>

@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
    <p class="font-medium text-red-800 mb-1"><i class="fas fa-exclamation-circle mr-2"></i>Chưa tạo được đơn:</p>
    <ul class="text-sm text-red-700 list-disc list-inside">
        @foreach($errors->unique() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($isCreated)
<div class="bg-green-50 border border-green-200 rounded-lg p-5 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <p class="font-semibold text-green-800">
            <i class="fas fa-check-circle mr-2"></i>Đơn nháp này đã tạo thành đơn hàng thật
        </p>
        <p class="text-sm text-green-700 mt-1">Khách: {{ $campaignOrder->customer?->name ?? $campaignOrder->customer_name_raw }}</p>
    </div>
    <div class="flex gap-2">
        @if($campaignOrder->order)
        <a href="{{ route('orders.show', $campaignOrder->order) }}"
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm whitespace-nowrap">
            <i class="fas fa-receipt mr-1"></i>Xem đơn #{{ $campaignOrder->order_id }}
        </a>
        @endif
        <a href="{{ route('campaigns.start', $campaign) }}"
           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm whitespace-nowrap">
            <i class="fas fa-play mr-1"></i>Đơn tiếp theo
        </a>
    </div>
</div>
@endif

<div id="processForm">
<form action="{{ route('campaigns.orders.update', [$campaign, $campaignOrder]) }}" method="POST" id="campaignOrderForm">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- Nguyên văn note --}}
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                    <i class="fas fa-quote-left mr-1"></i>Nguyên văn trong take note
                </p>
                <p class="text-sm text-slate-800 break-words">{{ $campaignOrder->raw_line ?: '(không có)' }}</p>
            </div>

            @if($campaignOrder->warnings)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <p class="font-medium text-amber-800 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Cần kiểm tra
                </p>
                <ul class="text-sm text-amber-800 space-y-1 list-disc list-inside">
                    @foreach($campaignOrder->warnings as $warning)
                    <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Khách hàng --}}
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">
                        <i class="fas fa-user mr-2 text-indigo-600"></i>Khách hàng
                    </h2>
                    @if($campaignOrder->customer_match === 'exact')
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Khớp chính xác</span>
                    @elseif($campaignOrder->customer_match === 'high')
                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Khớp gần đúng — kiểm tra lại</span>
                    @elseif($campaignOrder->customer_match === 'low')
                    <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">Chỉ có gợi ý</span>
                    @else
                    <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">Chưa tìm thấy</span>
                    @endif
                </div>

                <p class="text-sm text-gray-500 mb-3">
                    Note ghi: <strong class="text-gray-800">{{ $campaignOrder->customer_name_raw }}</strong>
                    @if($campaignOrder->customer_note_raw)
                    <span class="text-gray-400">· {{ $campaignOrder->customer_note_raw }}</span>
                    @endif
                </p>

                <select name="customer_id" id="customerSelect" class="ts-select" {{ $isCreated ? 'disabled' : '' }}
                        data-placeholder="-- Chọn khách hàng --"
                        data-no-results="Không tìm thấy khách hàng">
                    <option value="">-- Chọn khách hàng --</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}"
                            {{ (int) old('customer_id', $campaignOrder->customer_id) === $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}{{ $customer->phone ? ' - '.$customer->phone : '' }}
                    </option>
                    @endforeach
                </select>
                @error('customer_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror

                @if(!$isCreated && $campaignOrder->customer_suggestions)
                <div class="mt-3">
                    <p class="text-xs text-gray-500 mb-1">Gợi ý khớp tên:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($campaignOrder->customer_suggestions as $suggestion)
                        <button type="button" class="customer-suggestion px-3 py-1 text-sm border border-indigo-200 text-indigo-700 bg-indigo-50 rounded-full hover:bg-indigo-100"
                                data-id="{{ $suggestion['id'] }}">
                            {{ $suggestion['name'] }}
                            <span class="text-indigo-400 text-xs">{{ (int) $suggestion['score'] }}%</span>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(!$isCreated)
                <div class="mt-4 pt-4 border-t">
                    <button type="button" id="toggleNewCustomer" class="text-sm text-indigo-600 hover:text-indigo-800">
                        <i class="fas fa-plus-circle mr-1"></i>Tạo khách hàng mới
                    </button>

                    <div id="newCustomerPanel" class="hidden mt-3 bg-indigo-50 border border-indigo-100 rounded-lg p-4 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tên khách <span class="text-red-500">*</span></label>
                                <input type="text" id="newCustomerName" value="{{ $campaignOrder->customer_name_raw }}"
                                       class="w-full px-3 py-2 border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại</label>
                                <input type="text" id="newCustomerPhone" class="w-full px-3 py-2 border rounded-lg text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Địa chỉ</label>
                            <input type="text" id="newCustomerAddress" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                        <div class="flex gap-2">
                            <button type="button" id="saveNewCustomer"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                                <i class="fas fa-save mr-1"></i>Lưu & chọn
                            </button>
                            <button type="button" id="cancelNewCustomer" class="px-4 py-2 border rounded-lg text-sm hover:bg-white">Hủy</button>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Thông tin đơn --}}
            <div class="bg-white rounded-lg shadow p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">
                    <i class="fas fa-file-invoice mr-2 text-indigo-600"></i>Thông tin đơn
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày đặt</label>
                        <input type="date" name="order_date" {{ $isCreated ? 'disabled' : '' }}
                               value="{{ old('order_date', $campaignOrder->order_date?->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tiền cọc</label>
                        <input type="number" name="deposit_amount" min="0" step="1000" {{ $isCreated ? 'disabled' : '' }}
                               value="{{ old('deposit_amount', (int) $campaignOrder->deposit_amount) }}"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giảm giá</label>
                        <input type="number" name="discount_amount" min="0" step="1000" {{ $isCreated ? 'disabled' : '' }}
                               value="{{ old('discount_amount', (int) $campaignOrder->discount_amount) }}"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú đơn</label>
                    <textarea name="note" rows="2" {{ $isCreated ? 'disabled' : '' }}
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('note', $campaignOrder->note) }}</textarea>
                </div>
            </div>

            {{-- Sản phẩm --}}
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800">
                        <i class="fas fa-box mr-2 text-indigo-600"></i>Sản phẩm
                    </h2>
                </div>

                <div id="itemsContainer" class="space-y-4">
                    @foreach($rows as $i => $row)
                    <div class="border rounded-lg p-4 item-row" data-index="{{ $i }}">
                        @if($row['id'])
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $row['id'] }}">
                        @endif
                        <input type="hidden" name="items[{{ $i }}][product_name_raw]" value="{{ $row['product_name_raw'] }}">

                        <div class="flex items-start justify-between gap-2 mb-3">
                            <p class="text-xs text-gray-500 flex-1 min-w-0">
                                <i class="fas fa-quote-left mr-1 text-gray-300"></i>
                                <span class="italic break-words">{{ $row['raw_text'] ?: $row['product_name_raw'] }}</span>
                            </p>
                            @unless($isCreated)
                            <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 shrink-0" title="Xoá dòng">
                                <i class="fas fa-times"></i>
                            </button>
                            @endunless
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Sản phẩm <span class="text-red-500">*</span>
                                    </label>
                                    @if($row['product_match'] === 'exact')
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Khớp chính xác</span>
                                    @elseif($row['product_match'] === 'high')
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Khớp gần đúng — kiểm tra lại</span>
                                    @elseif($row['product_match'] === 'low')
                                    <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">Chỉ có gợi ý</span>
                                    @else
                                    <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">Chưa tìm thấy</span>
                                    @endif
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="product-image-preview w-16 h-16 shrink-0 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center"
                                         data-index="{{ $i }}"
                                         title="Ảnh sản phẩm đang chọn">
                                        @if($row['product_id'] && ($productImages[$row['product_id']] ?? null))
                                        <img src="{{ $productImages[$row['product_id']] }}" alt="{{ $row['product_name_raw'] }}"
                                             style="width:4rem;height:4rem;object-fit:cover">
                                        @else
                                        <i class="fas fa-image text-xl text-gray-400"></i>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <select name="items[{{ $i }}][product_id]" class="product-select ts-select"
                                                data-index="{{ $i }}" {{ $isCreated ? 'disabled' : '' }}
                                                data-placeholder="-- Chọn sản phẩm --"
                                                data-no-results="Không tìm thấy sản phẩm">
                                            <option value="">-- Chọn sản phẩm --</option>
                                            @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ (int) $row['product_id'] === $product->id ? 'selected' : '' }}>
                                                {{ $product->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                @if(!$isCreated && $row['product_suggestions'])
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($row['product_suggestions'] as $suggestion)
                                    <button type="button" class="product-suggestion px-3 py-1 text-xs border border-indigo-200 text-indigo-700 bg-indigo-50 rounded-full hover:bg-indigo-100"
                                            data-index="{{ $i }}" data-id="{{ $suggestion['id'] }}">
                                        {{ $suggestion['name'] }}
                                        <span class="text-indigo-400">{{ (int) $suggestion['score'] }}%</span>
                                    </button>
                                    @endforeach
                                </div>
                                @endif

                                @unless($isCreated)
                                <button type="button" class="toggle-new-product mt-2 text-xs text-indigo-600 hover:text-indigo-800"
                                        data-index="{{ $i }}">
                                    <i class="fas fa-plus-circle mr-1"></i>Tạo sản phẩm mới từ dòng này
                                </button>

                                <div class="new-product-panel hidden mt-2 bg-indigo-50 border border-indigo-100 rounded-lg p-3 space-y-3"
                                     data-index="{{ $i }}">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
                                            <input type="text" class="new-product-name w-full px-3 py-2 border rounded-lg text-sm"
                                                   value="{{ $row['product_name_raw'] }}">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Giá mặc định</label>
                                            <input type="number" min="0" step="1000"
                                                   class="new-product-price w-full px-3 py-2 border rounded-lg text-sm"
                                                   value="{{ (int) ($row['price'] ?? 0) }}">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Ảnh (không bắt buộc)</label>
                                        <div class="flex items-center gap-3">
                                            <div class="new-product-image-preview w-16 h-16 shrink-0 bg-white border rounded-lg overflow-hidden flex items-center justify-center">
                                                <i class="fas fa-image text-xl text-gray-300"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <input type="file" accept="image/*" class="new-product-image w-full px-3 py-2 border rounded-lg text-sm bg-white">
                                                <button type="button" class="clear-new-product-image hidden mt-1 text-xs text-red-500 hover:text-red-700">
                                                    <i class="fas fa-times mr-1"></i>Bỏ ảnh
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" class="save-new-product px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
                                                data-index="{{ $i }}">
                                            <i class="fas fa-save mr-1"></i>Lưu & chọn
                                        </button>
                                        <button type="button" class="cancel-new-product px-4 py-2 border rounded-lg text-sm hover:bg-white"
                                                data-index="{{ $i }}">Hủy</button>
                                    </div>
                                </div>
                                @endunless
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Size <span class="text-red-500">*</span>
                                    @if($row['size_raw'] && !$row['size'])
                                    <span class="text-xs text-amber-600 font-normal">(note ghi "{{ $row['size_raw'] }}" — không có trong danh sách)</span>
                                    @endif
                                </label>
                                <select name="items[{{ $i }}][size]" {{ $isCreated ? 'disabled' : '' }}
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 {{ !$row['size'] ? 'border-amber-400 bg-amber-50' : '' }}">
                                    <option value="">-- Chọn size --</option>
                                    @foreach($sizes as $size)
                                    <option value="{{ $size }}" {{ $row['size'] === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SL <span class="text-red-500">*</span></label>
                                    <input type="number" name="items[{{ $i }}][quantity]" min="1" required
                                           {{ $isCreated ? 'disabled' : '' }}
                                           value="{{ $row['quantity'] }}"
                                           class="quantity-input w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                           data-index="{{ $i }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Giá <span class="text-red-500">*</span></label>
                                    <input type="number" name="items[{{ $i }}][price]" min="0" step="1000"
                                           {{ $isCreated ? 'disabled' : '' }}
                                           value="{{ $row['price'] }}"
                                           class="price-input w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                           data-index="{{ $i }}">
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú dòng</label>
                                <input type="text" name="items[{{ $i }}][note]" {{ $isCreated ? 'disabled' : '' }}
                                       value="{{ $row['note'] }}"
                                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t flex justify-between items-center">
                            <span class="text-sm text-gray-500">Thành tiền:</span>
                            <span class="font-semibold text-indigo-600 item-subtotal" data-index="{{ $i }}">0đ</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                @unless($isCreated)
                <button type="button" id="addItemBtn"
                        class="mt-4 w-full px-4 py-2.5 border-2 border-dashed border-green-400 text-green-700 rounded-lg hover:bg-green-50 hover:border-green-500 text-sm font-medium">
                    <i class="fas fa-plus mr-1"></i>Thêm dòng
                </button>
                @endunless
            </div>
        </div>

        {{-- Cột phải: tổng kết + hành động --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-5 sticky top-40 space-y-4">
                <h2 class="font-semibold text-gray-800">
                    <i class="fas fa-receipt mr-2 text-indigo-600"></i>Tổng kết
                </h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Số dòng:</span>
                        <span class="font-medium" id="totalItems">0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tổng số lượng:</span>
                        <span class="font-medium" id="totalQuantity">0</span>
                    </div>
                    <div class="border-t pt-2 flex justify-between">
                        <span class="text-gray-500">Tổng tiền hàng:</span>
                        <span class="font-medium tabular-nums text-gray-800" id="totalAmount">0đ</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tiền cọc:</span>
                        <span class="font-medium tabular-nums text-green-700" id="summaryDeposit">0đ</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Giảm giá:</span>
                        <span class="font-medium tabular-nums text-yellow-700" id="summaryDiscount">0đ</span>
                    </div>
                    <div class="border-t pt-2 flex justify-between text-base">
                        <span class="font-semibold">Còn phải thanh toán:</span>
                        <span class="font-bold tabular-nums text-orange-600" id="remainingAmount">0đ</span>
                    </div>
                </div>

                @unless($isCreated)
                <div class="space-y-2 pt-3 border-t">
                    <button type="submit" name="action" value="create"
                            class="w-full px-5 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                        <i class="fas fa-check mr-2"></i>Tạo đơn hàng
                    </button>
                    <button type="submit" name="action" value="save"
                            class="w-full px-5 py-2.5 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50">
                        <i class="fas fa-save mr-2"></i>Lưu nháp
                    </button>
                    <button type="submit" name="action" value="skip"
                            onclick="return confirm('Bỏ qua đơn này và sang đơn tiếp theo?')"
                            class="w-full px-5 py-2.5 border rounded-lg text-gray-600 hover:bg-gray-50">
                        <i class="fas fa-forward mr-2"></i>Bỏ qua đơn này
                    </button>
                </div>
                <p class="text-xs text-gray-400">
                    "Tạo đơn hàng" sẽ tạo đơn thật và tự chuyển sang đơn nháp kế tiếp.
                </p>
                @endunless
            </div>
        </div>
    </div>
</form>
</div>

@push('scripts')
<script>
const productsData = @json($productsPayload);
const sizesData = @json($sizes);
const CSRF = '{{ csrf_token() }}';
let itemIndex = {{ count($rows) }};

$(document).ready(function () {
    bindAllItemEvents();
    calculateTotals();

    $('#addItemBtn').on('click', addItem);

    $('input[name="deposit_amount"], input[name="discount_amount"]').on('input change', calculateTotals);

    // Gợi ý khách hàng
    $('.customer-suggestion').on('click', function () {
        setSelectValue($('#customerSelect'), $(this).data('id'));
    });

    // Tạo khách hàng mới
    $('#toggleNewCustomer').on('click', () => $('#newCustomerPanel').toggleClass('hidden'));
    $('#cancelNewCustomer').on('click', () => $('#newCustomerPanel').addClass('hidden'));
    $('#saveNewCustomer').on('click', saveNewCustomer);
});

function bindAllItemEvents() {
    $('.item-row').each(function () {
        bindItemEvents($(this).data('index'));
    });
}

function bindItemEvents(index) {
    const $row = $(`.item-row[data-index="${index}"]`);

    $row.find('.remove-item-btn').off('click').on('click', () => removeItem(index));

    $row.find('.quantity-input, .price-input').off('input change').on('input change', function () {
        calculateItemSubtotal(index);
        calculateTotals();
    });

    $row.find('.product-select').off('change').on('change', function () {
        onProductChange(index);
    });

    $row.find('.product-suggestion').off('click').on('click', function () {
        setSelectValue($row.find('.product-select'), $(this).data('id'));
        onProductChange(index);
    });

    $row.find('.toggle-new-product').off('click').on('click', function () {
        $row.find('.new-product-panel').toggleClass('hidden');
    });

    $row.find('.cancel-new-product').off('click').on('click', function () {
        $row.find('.new-product-panel').addClass('hidden');
    });

    $row.find('.new-product-image').off('change').on('change', function (event) {
        previewNewProductImage($row, event.target.files[0]);
    });

    $row.find('.clear-new-product-image').off('click').on('click', function () {
        $row.find('.new-product-image').val('');
        previewNewProductImage($row, null);
    });

    $row.find('.save-new-product').off('click').on('click', function () {
        saveNewProduct(index, $(this));
    });
}

function addItem() {
    const index = itemIndex++;

    const html = `
        <div class="border rounded-lg p-4 item-row" data-index="${index}">
            <input type="hidden" name="items[${index}][product_name_raw]" value="Dòng thêm tay">

            <div class="flex items-start justify-between gap-2 mb-3">
                <p class="text-xs text-gray-500 manual-row-label">Dòng thêm tay</p>
                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 shrink-0" title="Xoá dòng">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                    <div class="flex items-start gap-3">
                        <div class="product-image-preview w-16 h-16 shrink-0 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center"
                             data-index="${index}" title="Ảnh sản phẩm đang chọn">
                            <i class="fas fa-image text-xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <select name="items[${index}][product_id]" class="product-select ts-select" data-index="${index}"
                                    data-placeholder="-- Chọn sản phẩm --" data-no-results="Không tìm thấy sản phẩm">
                                <option value="">-- Chọn sản phẩm --</option>
                                ${productsData.map(p => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <button type="button" class="toggle-new-product mt-2 text-xs text-indigo-600 hover:text-indigo-800"
                            data-index="${index}">
                        <i class="fas fa-plus-circle mr-1"></i>Tạo sản phẩm mới từ dòng này
                    </button>

                    <div class="new-product-panel hidden mt-2 bg-indigo-50 border border-indigo-100 rounded-lg p-3 space-y-3"
                         data-index="${index}">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
                                <input type="text" class="new-product-name w-full px-3 py-2 border rounded-lg text-sm"
                                       placeholder="Nhập tên sản phẩm...">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Giá mặc định</label>
                                <input type="number" min="0" step="1000" value="0"
                                       class="new-product-price w-full px-3 py-2 border rounded-lg text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Ảnh (không bắt buộc)</label>
                            <div class="flex items-center gap-3">
                                <div class="new-product-image-preview w-16 h-16 shrink-0 bg-white border rounded-lg overflow-hidden flex items-center justify-center">
                                    <i class="fas fa-image text-xl text-gray-300"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" accept="image/*" class="new-product-image w-full px-3 py-2 border rounded-lg text-sm bg-white">
                                    <button type="button" class="clear-new-product-image hidden mt-1 text-xs text-red-500 hover:text-red-700">
                                        <i class="fas fa-times mr-1"></i>Bỏ ảnh
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="save-new-product px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
                                    data-index="${index}">
                                <i class="fas fa-save mr-1"></i>Lưu & chọn
                            </button>
                            <button type="button" class="cancel-new-product px-4 py-2 border rounded-lg text-sm hover:bg-white"
                                    data-index="${index}">Hủy</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Size <span class="text-red-500">*</span></label>
                    <select name="items[${index}][size]" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Chọn size --</option>
                        ${sizesData.map(s => `<option value="${s}">${s}</option>`).join('')}
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SL <span class="text-red-500">*</span></label>
                        <input type="number" name="items[${index}][quantity]" min="1" value="1" required
                               class="quantity-input w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                               data-index="${index}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giá <span class="text-red-500">*</span></label>
                        <input type="number" name="items[${index}][price]" min="0" step="1000" value="0"
                               class="price-input w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                               data-index="${index}">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú dòng</label>
                    <input type="text" name="items[${index}][note]"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="mt-3 pt-3 border-t flex justify-between items-center">
                <span class="text-sm text-gray-500">Thành tiền:</span>
                <span class="font-semibold text-indigo-600 item-subtotal" data-index="${index}">0đ</span>
            </div>
        </div>
    `;

    $('#itemsContainer').append(html);
    $(`.item-row[data-index="${index}"] .product-select`).tsSelect();
    bindItemEvents(index);
    calculateTotals();
}

function removeItem(index) {
    const $row = $(`.item-row[data-index="${index}"]`);
    $row.find('.product-select').tsDestroy();
    $row.remove();
    calculateTotals();
}

function onProductChange(index) {
    const $row = $(`.item-row[data-index="${index}"]`);
    const productId = $row.find('.product-select').val();
    const $price = $row.find('.price-input');
    const product = productId ? productsData.find(p => p.id == productId) : null;

    if (product && (!$price.val() || parseInt($price.val()) === 0)) {
        $price.val(product.price);
    }

    setRowImage(index, product?.image || null, product?.name || '');

    calculateItemSubtotal(index);
    calculateTotals();
}

/** Đổi ảnh thumbnail của một dòng. Không có ảnh thì trả về icon placeholder. */
function setRowImage(index, url, alt) {
    const $box = $(`.product-image-preview[data-index="${index}"]`);

    if (!$box.length) {
        return;
    }

    if (url) {
        $box.html($('<img>')
            .attr('src', url)
            .attr('alt', alt || '')
            .attr('style', 'width:4rem;height:4rem;object-fit:cover'));
    } else {
        $box.html('<i class="fas fa-image text-xl text-gray-400"></i>');
    }
}

/** Preview ảnh vừa chọn trong panel tạo sản phẩm mới. */
function previewNewProductImage($row, file) {
    const $box = $row.find('.new-product-image-preview');
    const $clear = $row.find('.clear-new-product-image');

    if (!file) {
        $box.html('<i class="fas fa-image text-xl text-gray-300"></i>');
        $clear.addClass('hidden');
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {
        $box.html($('<img>')
            .attr('src', e.target.result)
            .attr('alt', 'Ảnh sản phẩm mới')
            .attr('style', 'width:4rem;height:4rem;object-fit:cover'));
        $clear.removeClass('hidden');
    };

    reader.readAsDataURL(file);
}

/** Đặt giá trị cho select có Tom Select rồi đồng bộ lại hiển thị. */
function setSelectValue($select, value) {
    const el = $select.get(0);

    if (el && el.tomselect) {
        el.tomselect.setValue(String(value));
    } else {
        $select.val(String(value)).trigger('change');
    }
}

/** Thêm option sản phẩm mới vào mọi dòng, để các dòng sau cũng chọn được. */
function addProductOption(product, imageUrl) {
    productsData.push({
        id: product.id,
        name: product.name,
        price: parseInt(product.default_price) || 0,
        image: imageUrl || null,
    });

    $('.product-select').each(function () {
        if (this.tomselect) {
            this.tomselect.addOption({ value: String(product.id), text: product.name });
            this.tomselect.refreshOptions(false);
        } else {
            $(this).append(new Option(product.name, product.id));
        }
    });
}

function saveNewCustomer() {
    const name = $('#newCustomerName').val().trim();

    if (!name) {
        alert('Nhập tên khách hàng.');
        return;
    }

    const $btn = $('#saveNewCustomer');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang lưu...');

    $.ajax({
        url: '{{ route('chatbot.create-customer') }}',
        method: 'POST',
        data: {
            _token: CSRF,
            name: name,
            phone: $('#newCustomerPhone').val().trim(),
            address: $('#newCustomerAddress').val().trim(),
        },
    }).done(function (res) {
        const label = res.customer.name + (res.customer.phone ? ' - ' + res.customer.phone : '');
        const el = $('#customerSelect').get(0);

        if (el && el.tomselect) {
            el.tomselect.addOption({ value: String(res.customer.id), text: label });
            el.tomselect.setValue(String(res.customer.id));
        } else {
            $('#customerSelect').append(new Option(label, res.customer.id, true, true));
        }

        $('#newCustomerPanel').addClass('hidden');
    }).fail(function (xhr) {
        alert(firstError(xhr, 'Không tạo được khách hàng.'));
    }).always(function () {
        $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Lưu & chọn');
    });
}

function saveNewProduct(index, $btn) {
    const $row = $(`.item-row[data-index="${index}"]`);
    const $panel = $row.find('.new-product-panel');
    const name = $panel.find('.new-product-name').val().trim();

    if (!name) {
        alert('Nhập tên sản phẩm.');
        return;
    }

    const price = parseInt($panel.find('.new-product-price').val()) || 0;
    const file = $panel.find('.new-product-image').get(0).files[0];

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang lưu...');

    const send = function (imageBase64) {
        $.ajax({
            url: '{{ route('chatbot.create-product') }}',
            method: 'POST',
            data: {
                _token: CSRF,
                name: name,
                default_price: price,
                image_base64: imageBase64 || '',
            },
        }).done(function (res) {
            // Ảnh vừa resize dùng luôn làm thumbnail, khỏi phải chờ tải lại trang.
            addProductOption(res.product, res.product.image_url || imageBase64 || null);
            setSelectValue($row.find('.product-select'), res.product.id);

            // Dòng thêm tay chưa có tên thật trong note, lấy luôn tên sản phẩm vừa tạo.
            // Dòng đọc từ note thì giữ nguyên để còn đối chiếu lại được.
            const $rawName = $row.find('input[name$="[product_name_raw]"]');
            if ($rawName.val() === 'Dòng thêm tay') {
                $rawName.val(res.product.name);
                $row.find('.manual-row-label').text(res.product.name);
            }

            $panel.addClass('hidden');
            $panel.find('.new-product-image').val('');
            previewNewProductImage($row, null);
            onProductChange(index);
        }).fail(function (xhr) {
            alert(firstError(xhr, 'Không tạo được sản phẩm.'));
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Lưu & chọn');
        });
    };

    if (file) {
        resizeImage(file, send);
    } else {
        send(null);
    }
}

/** Thu nhỏ ảnh trước khi gửi để payload không quá lớn. */
function resizeImage(file, callback) {
    const reader = new FileReader();

    reader.onload = function (e) {
        const img = new Image();

        img.onload = function () {
            const canvas = document.createElement('canvas');
            const MAX = 800;
            let { width, height } = img;

            if (width > MAX || height > MAX) {
                const ratio = Math.min(MAX / width, MAX / height);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);
            }

            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);
            callback(canvas.toDataURL('image/jpeg', 0.8));
        };

        img.onerror = () => callback(null);
        img.src = e.target.result;
    };

    reader.onerror = () => callback(null);
    reader.readAsDataURL(file);
}

function calculateItemSubtotal(index) {
    const $row = $(`.item-row[data-index="${index}"]`);
    const quantity = parseInt($row.find('.quantity-input').val()) || 0;
    const price = parseInt($row.find('.price-input').val()) || 0;

    $row.find('.item-subtotal').text(formatCurrency(quantity * price));
}

function calculateTotals() {
    let totalItems = 0;
    let totalQuantity = 0;
    let totalAmount = 0;

    $('.item-row').each(function () {
        const index = $(this).data('index');
        const $row = $(this);
        const quantity = parseInt($row.find('.quantity-input').val()) || 0;
        const price = parseInt($row.find('.price-input').val()) || 0;

        if ($row.find('.product-select').val()) {
            totalItems++;
        }

        totalQuantity += quantity;
        totalAmount += quantity * price;
        calculateItemSubtotal(index);
    });

    const deposit = parseInt($('input[name="deposit_amount"]').val()) || 0;
    const discount = parseInt($('input[name="discount_amount"]').val()) || 0;

    $('#totalItems').text(totalItems);
    $('#totalQuantity').text(totalQuantity);
    $('#totalAmount').text(formatCurrency(totalAmount));
    $('#summaryDeposit').text(formatCurrency(deposit));
    $('#summaryDiscount').text(formatCurrency(discount));

    const remaining = totalAmount - deposit - discount;

    $('#remainingAmount')
        .text(formatCurrency(remaining))
        // Cọc + giảm vượt quá tổng tiền hàng thì gần như chắc chắn nhập nhầm.
        .toggleClass('text-orange-600', remaining >= 0)
        .toggleClass('text-red-600', remaining < 0);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
}

function escapeHtml(str) {
    return $('<div>').text(str == null ? '' : str).html();
}

function firstError(xhr, fallback) {
    const errors = xhr.responseJSON?.errors;

    if (errors) {
        const key = Object.keys(errors)[0];
        return errors[key][0];
    }

    return xhr.responseJSON?.message || fallback;
}
</script>
@endpush
@endsection
