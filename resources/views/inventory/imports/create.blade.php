@extends('layouts.app')

@section('title', 'Tạo đơn nhập kho')

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.imports.index') }}" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-6">
                <i class="fas fa-box-open mr-2 text-indigo-600"></i>Thông tin đơn nhập
            </h2>

            <form action="{{ route('inventory.imports.store') }}" method="POST" id="importFormElement">
                @csrf

                <div class="space-y-4">
                    <!-- Import Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mã đơn nhập <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="import_code" value="{{ old('import_code') }}" 
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('import_code') border-red-500 @enderror"
                               required>
                        @error('import_code')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nhà cung cấp <span class="text-red-500">*</span>
                        </label>
                        <select name="supplier" class="chosen-select" required>
                            <option value="">-- Chọn nhà cung cấp --</option>
                            @foreach($suppliers as $key => $label)
                            <option value="{{ $key }}" {{ old('supplier') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('supplier')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Import Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày nhập hàng <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="import_date" value="{{ old('import_date', date('Y-m-d')) }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('import_date') border-red-500 @enderror"
                               required>
                        @error('import_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Note -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                        <textarea name="note" rows="3"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Thông tin bổ sung về đơn nhập...">{{ old('note') }}</textarea>
                    </div>

                    <!-- Items Section -->
                    <div class="border-t pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Danh sách sản phẩm</h3>
                        </div>

                        <div id="itemsContainer" class="space-y-4 max-h-96 overflow-y-auto">
                            <!-- Items will be added here by jQuery -->
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="button" id="addItemBtn" 
                                    class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                            </button>
                        </div>

                        @error('items')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <a href="{{ route('inventory.imports.index') }}" 
                           class="px-6 py-2 border rounded-lg hover:bg-gray-50">
                            Hủy
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-save mr-2"></i>Lưu đơn nhập
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-24">
            <h3 class="text-lg font-semibold mb-4">Tổng quan</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng sản phẩm:</span>
                    <span class="font-bold" id="totalItems">0</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng số lượng:</span>
                    <span class="font-bold text-indigo-600" id="totalQuantity">0</span>
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Nhập đầy đủ thông tin sản phẩm, size và số lượng cho mỗi mặt hàng.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<?php
$productsData = $products->map(function($product) {
    return [
        'id' => $product->id,
        'name' => $product->name,
        'image_url' => $product->image_url,
    ];
});
?>
<script>
const productsData = @json($productsData);
const sizesData = @json($sizes);

let itemIndex = 0;

$(document).ready(function() {
    // Add first item
    addItem();
    
    // Add item button
    $('#addItemBtn').on('click', function() {
        addItem();
    });
    
    // Initial calculation
    calculateTotals();
});

function addItem() {
    const index = itemIndex++;
    
    const itemHtml = `
        <div class="bg-gray-50 p-4 rounded-lg border item-row" data-index="${index}">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-700">Sản phẩm <span class="item-number">${$('.item-row').length + 1}</span></span>
                    <!-- Product Image Preview -->
                    <div class="product-image-preview" data-index="${index}">
                        <!-- Image will be shown here -->
                    </div>
                </div>
                <button type="button" class="remove-item-btn text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Product -->
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Sản phẩm</label>
                    <select name="items[${index}][product_id]" 
                            class="product-select chosen-select" 
                            data-index="${index}"
                            required>
                        <option value="">-- Chọn sản phẩm --</option>
                        ${productsData.map(p => `<option value="${p.id}" data-image="${p.image_url}">${p.name}</option>`).join('')}
                    </select>
                </div>

                <!-- Size -->
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Size</label>
                    <select name="items[${index}][size]" 
                            class="size-select chosen-select"
                            data-index="${index}"
                            required>
                        <option value="">-- Chọn size --</option>
                        ${sizesData.map(s => `<option value="${s}">${s}</option>`).join('')}
                    </select>
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Số lượng</label>
                    <input type="number" 
                           name="items[${index}][quantity]" 
                           class="quantity-input w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                           data-index="${index}"
                           value="1"
                           min="1"
                           required>
                </div>

                <!-- Note -->
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Ghi chú</label>
                    <input type="text" 
                           name="items[${index}][note]" 
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="Ghi chú về sản phẩm...">
                </div>
            </div>
        </div>
    `;
    
    $('#itemsContainer').append(itemHtml);

    $('#itemsContainer').scrollTop($('#itemsContainer')[0].scrollHeight);
    
    // Initialize Chosen for new selects
    initChosenForItem(index);
    
    // Bind events
    bindItemEvents(index);
    
    // Update item numbers
    updateItemNumbers();
    
    // Calculate totals
    calculateTotals();
}

function initChosenForItem(index) {
    // Product select
    const $productSelect = $(`.product-select[data-index="${index}"]`);
    if ($productSelect.length) {
        $productSelect.chosen({
            placeholder_text_single: 'Chọn sản phẩm',
            no_results_text: 'Không tìm thấy',
            width: '100%',
            search_contains: true
        });
        
        $productSelect.on('change', function() {
            onProductChange(index);
        });
    }
    
    // Size select
    const $sizeSelect = $(`.size-select[data-index="${index}"]`);
    if ($sizeSelect.length) {
        $sizeSelect.chosen({
            placeholder_text_single: 'Chọn size',
            no_results_text: 'Không tìm thấy',
            width: '100%',
            search_contains: true,
            disable_search_threshold: 5
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
        calculateTotals();
    });
}

function onProductChange(index) {
    const $select = $(`.product-select[data-index="${index}"]`);
    const $option = $select.find('option:selected');
    
    if ($option.val()) {
        const imageUrl = $option.data('image');
        const productName = $option.text();
        
        // Update image preview
        const $preview = $(`.product-image-preview[data-index="${index}"]`);
        if (imageUrl) {
            $preview.html(`<img src="${imageUrl}" alt="${productName}" class="w-20 h-20 object-cover rounded border border-gray-300" title="${productName}">`);
        } else {
            $preview.html('');
        }
    }
}

function removeItem(index) {
    // Destroy Chosen selects
    const $productSelect = $(`.product-select[data-index="${index}"]`);
    const $sizeSelect = $(`.size-select[data-index="${index}"]`);
    
    if ($productSelect.data('chosen')) {
        $productSelect.chosen('destroy');
    }
    if ($sizeSelect.data('chosen')) {
        $sizeSelect.chosen('destroy');
    }
    
    // Remove item
    $(`.item-row[data-index="${index}"]`).remove();
    
    // Update item numbers
    updateItemNumbers();
    
    // Calculate totals
    calculateTotals();
}

function updateItemNumbers() {
    $('.item-row').each(function(i) {
        $(this).find('.item-number').text(i + 1);
    });
}

function calculateTotals() {
    let totalItems = 0;
    let totalQuantity = 0;
    
    $('.item-row').each(function() {
        const index = $(this).data('index');
        const productId = $(`.product-select[data-index="${index}"]`).val();
        const quantity = parseInt($(`.quantity-input[data-index="${index}"]`).val()) || 0;
        
        if (productId) {
            totalItems++;
        }
        totalQuantity += quantity;
    });
    
    $('#totalItems').text(totalItems);
    $('#totalQuantity').text(totalQuantity);
}
</script>
@endpush

@endsection
