@extends('layouts.app')

@section('title', 'Sửa đơn hàng #' . $order->id)

@section('content')
<div id="orderForm">
    <div class="flex items-center mb-6">
        <a href="{{ route('orders.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Sửa đơn hàng #{{ $order->id }}</h1>
    </div>

    <form action="{{ route('orders.update', $order) }}" method="POST" enctype="multipart/form-data" id="orderFormElement">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Order Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Selection -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="fas fa-user mr-2 text-indigo-600"></i>Thông tin khách hàng
                    </h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Khách hàng <span class="text-red-500">*</span>
                            </label>
                            <select name="customer_id" required class="chosen-select">
                                <option value="">-- Chọn khách hàng --</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} {{ $customer->phone ? "- {$customer->phone}" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Trạng thái <span class="text-red-500">*</span>
                            </label>
                            <select name="status" required>
                                @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ old('status', $order->status) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Số tiền đã cọc
                        </label>
                        <input type="number" 
                               name="deposit_amount" 
                               value="{{ old('deposit_amount', $order->deposit_amount) }}"
                               min="0"
                               step="1000"
                               placeholder="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Nhập số tiền khách hàng đã đặt cọc cho đơn hàng này</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Số tiền giảm giá
                        </label>
                        <input type="number" 
                               name="discount_amount" 
                               value="{{ old('discount_amount', $order->discount_amount) }}"
                               min="0"
                               step="1000"
                               placeholder="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Nhập số tiền giảm giá cho đơn hàng này</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú đơn hàng</label>
                        <textarea name="note" rows="2"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Ghi chú cho đơn hàng...">{{ old('note', $order->note) }}</textarea>
                    </div>
                </div>

                <!-- Shipping Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="fas fa-shipping-fast mr-2 text-indigo-600"></i>Thông tin vận chuyển
                    </h2>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mã vận chuyển
                        </label>
                        <input type="text" 
                               name="shipping_code" 
                               value="{{ old('shipping_code', $order->shipping_code) }}"
                               placeholder="Nhập mã vận chuyển..."
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ảnh mã vận chuyển
                        </label>
                        <div class="space-y-3">
                            @if($order->shipping_image)
                            <div class="relative inline-block">
                                <img src="{{ $order->shipping_image_url }}" 
                                     alt="Shipping Image" 
                                     class="w-32 h-32 object-cover rounded-lg border">
                                <p class="mt-1 text-xs text-gray-500">Ảnh hiện tại</p>
                            </div>
                            @endif
                            <input type="file" 
                                   name="shipping_image" 
                                   accept="image/*"
                                   class="block w-full px-3 py-2 border rounded-lg text-sm">
                            <p class="text-xs text-gray-500">Upload ảnh mới nếu muốn thay đổi</p>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">
                            <i class="fas fa-box mr-2 text-indigo-600"></i>Sản phẩm
                        </h2>
                    </div>

                    <div id="itemsContainer" class="space-y-4">
                        <!-- Items will be added here by jQuery -->
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button type="button" id="addItemBtn" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                            <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="fas fa-receipt mr-2 text-indigo-600"></i>Tổng kết
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Số sản phẩm:</span>
                            <span class="font-medium" id="totalItems">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tổng số lượng:</span>
                            <span class="font-medium" id="totalQuantity">0</span>
                        </div>
                        <div class="border-t pt-3 flex justify-between text-lg">
                            <span class="font-semibold">Tổng tiền:</span>
                            <span class="font-bold text-indigo-600" id="totalAmount">0đ</span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <button type="submit" 
                                class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                            <i class="fas fa-save mr-2"></i>Cập nhật đơn hàng
                        </button>
                        <a href="{{ route('orders.show', $order) }}" 
                           class="block w-full px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-center">
                           Xem chi tiết đơn hàng
                        </a>
                        <a href="{{ route('orders.index') }}" 
                           class="block w-full px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-center">
                            Hủy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<?php 
$productsData = $products->map(function($p) {
    return [
        'id' => $p->id,
        'name' => $p->name,
        'price' => $p->default_price,
        'image' => $p->image_url
    ];
});

$orderItemsData = $order->items->map(function($item) {
    return [
        'id' => $item->id,
        'product_id' => (string)$item->product_id,
        'size' => $item->size,
        'quantity' => $item->quantity,
        'price' => $item->price,
        'note' => $item->note ?? '',
        'currentImage' => $item->image_url,
        'productImage' => $item->product->image_url ?? null,
    ];
});
?>
<script>
// Products data từ server
const productsData = @json($productsData);
const sizesData = @json($sizes);
const existingItems = @json($orderItemsData);

let itemIndex = 0;
const itemIndexMap = {}; // Map để track index của existing items

$(document).ready(function() {
    // Load existing items
    loadExistingItems();
    
    // Add item button
    $('#addItemBtn').on('click', function() {
        addItem();
    });
    
    // Initial calculation
    calculateTotals();
});

function loadExistingItems() {
    existingItems.forEach(function(itemData) {
        addItem(itemData);
    });
}

function addItem(existingData = null) {
    const index = itemIndex++;
    
    // If existing item, store its ID
    if (existingData && existingData.id) {
        itemIndexMap[index] = existingData.id;
    }
    
    const itemHtml = `
        <div class="border rounded-lg p-4 relative item-row" data-index="${index}">
            ${existingData && existingData.id ? `<input type="hidden" name="items[${index}][id]" value="${existingData.id}">` : ''}
            
            <button type="button" class="remove-item-btn absolute top-2 right-2 text-red-500 hover:text-red-700" style="display: none;">
                <i class="fas fa-times"></i>
            </button>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Product -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Sản phẩm <span class="text-red-500">*</span>
                    </label>
                    <select name="items[${index}][product_id]" 
                            class="product-select chosen-select" 
                            data-index="${index}"
                            required>
                        <option value="">-- Chọn sản phẩm --</option>
                        ${productsData.map(p => `<option value="${p.id}" data-price="${p.price}" data-image="${p.image}" ${existingData && existingData.product_id == p.id ? 'selected' : ''}>${p.name}</option>`).join('')}
                    </select>
                </div>

                <!-- Size -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Size <span class="text-red-500">*</span>
                    </label>
                    <select name="items[${index}][size]" 
                            class="size-select w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                            required>
                        <option value="">-- Chọn size --</option>
                        ${sizesData.map(s => `<option value="${s}" ${existingData && existingData.size == s ? 'selected' : ''}>${s}</option>`).join('')}
                    </select>
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Số lượng <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="items[${index}][quantity]" 
                           class="quantity-input w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                           data-index="${index}"
                           value="${existingData ? existingData.quantity : 1}"
                           min="1" 
                           required>
                </div>

                <!-- Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Giá <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="items[${index}][price]" 
                           class="price-input w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                           data-index="${index}"
                           value="${existingData ? existingData.price : 0}"
                           min="0" 
                           required>
                </div>

                <!-- Image Preview -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh sản phẩm</label>
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center image-preview" data-index="${index}">
                            ${existingData && (existingData.currentImage || existingData.productImage) 
                                ? `<img src="${existingData.currentImage || existingData.productImage}" class="w-full h-full object-cover">`
                                : '<i class="fas fa-image text-xl text-gray-400"></i>'
                            }
                        </div>
                        <input type="file" 
                               name="items[${index}][image]" 
                               class="image-input flex-1 px-3 py-2 border rounded-lg text-sm"
                               data-index="${index}"
                               accept="image/*">
                    </div>
                </div>

                <!-- Note -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <input type="text" 
                           name="items[${index}][note]" 
                           value="${existingData ? (existingData.note || '') : ''}"
                           placeholder="Ghi chú cho sản phẩm này..."
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Item Subtotal -->
            <div class="mt-3 pt-3 border-t flex justify-between items-center">
                <span class="text-sm text-gray-500">Thành tiền:</span>
                <span class="font-semibold text-indigo-600 item-subtotal" data-index="${index}">0đ</span>
            </div>
        </div>
    `;
    
    $('#itemsContainer').append(itemHtml);
    
    // Initialize Chosen for new select
    initChosenForItem(index);
    
    // Bind events
    bindItemEvents(index);
    
    // Update remove button visibility
    updateRemoveButtons();
    
    // Calculate totals
    calculateTotals();
}

function initChosenForItem(index) {
    const $select = $(`.product-select[data-index="${index}"]`);
    
    if ($select.length) {
        $select.chosen({
            placeholder_text_single: 'Chọn sản phẩm',
            no_results_text: 'Không tìm thấy',
            width: '100%',
            search_contains: true
        });
        
        // Bind change event for Chosen
        $select.on('change', function() {
            onProductChange(index);
        });
    }
}

function bindItemEvents(index) {
    // Remove button
    $(`.item-row[data-index="${index}"] .remove-item-btn`).on('click', function() {
        removeItem(index);
    });
    
    // Quantity change
    $(`.quantity-input[data-index="${index}"]`).on('input change', function() {
        calculateItemSubtotal(index);
        calculateTotals();
    });
    
    // Price change
    $(`.price-input[data-index="${index}"]`).on('input change', function() {
        calculateItemSubtotal(index);
        calculateTotals();
    });
    
    // Image change
    $(`.image-input[data-index="${index}"]`).on('change', function(e) {
        onImageChange(index, e);
    });
}

function onProductChange(index) {
    const $select = $(`.product-select[data-index="${index}"]`);
    const $option = $select.find('option:selected');
    
    if ($option.val()) {
        const price = $option.data('price') || 0;
        const image = $option.data('image') || '';
        
        // Chỉ update price nếu là item mới (không có ID)
        const hasExistingId = itemIndexMap[index];
        if (!hasExistingId) {
            $(`.price-input[data-index="${index}"]`).val(price);
        }
        
        // Update image preview nếu chưa có ảnh hiện tại
        const $preview = $(`.image-preview[data-index="${index}"]`);
        const hasCurrentImage = $preview.find('img').length > 0;
        
        if (image && !hasCurrentImage) {
            $preview.html(`<img src="${image}" class="w-full h-full object-cover">`);
        }
        
        // Calculate subtotal
        calculateItemSubtotal(index);
        calculateTotals();
    }
}

function onImageChange(index, event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $(`.image-preview[data-index="${index}"]`).html(`<img src="${e.target.result}" class="w-full h-full object-cover">`);
        };
        reader.readAsDataURL(file);
    }
}

function removeItem(index) {
    // Destroy Chosen
    const $select = $(`.product-select[data-index="${index}"]`);
    if ($select.data('chosen')) {
        $select.chosen('destroy');
    }
    
    // Remove item
    $(`.item-row[data-index="${index}"]`).remove();
    
    // Remove from index map
    delete itemIndexMap[index];
    
    // Update buttons and totals
    updateRemoveButtons();
    calculateTotals();
}

function updateRemoveButtons() {
    const itemCount = $('.item-row').length;
    
    if (itemCount > 1) {
        $('.remove-item-btn').show();
    } else {
        $('.remove-item-btn').hide();
    }
}

function calculateItemSubtotal(index) {
    const quantity = parseInt($(`.quantity-input[data-index="${index}"]`).val()) || 0;
    const price = parseInt($(`.price-input[data-index="${index}"]`).val()) || 0;
    const subtotal = quantity * price;
    
    $(`.item-subtotal[data-index="${index}"]`).text(formatCurrency(subtotal));
}

function calculateTotals() {
    let totalItems = 0;
    let totalQuantity = 0;
    let totalAmount = 0;
    
    $('.item-row').each(function() {
        const index = $(this).data('index');
        const productId = $(`.product-select[data-index="${index}"]`).val();
        const quantity = parseInt($(`.quantity-input[data-index="${index}"]`).val()) || 0;
        const price = parseInt($(`.price-input[data-index="${index}"]`).val()) || 0;
        
        if (productId) {
            totalItems++;
        }
        totalQuantity += quantity;
        totalAmount += quantity * price;
        
        // Update item subtotal
        calculateItemSubtotal(index);
    });
    
    $('#totalItems').text(totalItems);
    $('#totalQuantity').text(totalQuantity);
    $('#totalAmount').text(formatCurrency(totalAmount));
}

function formatCurrency(value) {
    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
}
</script>
@endpush
@endsection
