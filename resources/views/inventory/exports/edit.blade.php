@extends('layouts.app')

@section('title', 'Sửa đơn xuất - ' . $export->export_code)

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.exports.show', $export) }}" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại chi tiết
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-6">
                <i class="fas fa-edit mr-2 text-red-600"></i>Sửa đơn xuất kho
            </h2>

            <form action="{{ route('inventory.exports.update', $export) }}" method="POST" id="exportForm">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <!-- Export Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mã đơn xuất <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="export_code" value="{{ old('export_code', $export->export_code) }}" 
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('export_code') border-red-500 @enderror"
                               required>
                        @error('export_code')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Lý do xuất kho <span class="text-red-500">*</span>
                        </label>
                        <select name="reason" 
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('reason') border-red-500 @enderror"
                                required>
                            <option value="">-- Chọn lý do --</option>
                            @foreach($reasons as $key => $label)
                            <option value="{{ $key }}" {{ old('reason', $export->reason) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Export Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày xuất kho <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="export_date" value="{{ old('export_date', $export->export_date->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('export_date') border-red-500 @enderror"
                               required>
                        @error('export_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Note -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                        <textarea name="note" rows="3"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Thông tin bổ sung về đơn xuất...">{{ old('note', $export->note) }}</textarea>
                    </div>

                    <!-- Items Section -->
                    <div class="border-t pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Danh sách sản phẩm xuất</h3>
                        </div>

                        <div id="itemsContainer" class="space-y-4">
                            <!-- Items will be loaded here dynamically -->
                        </div>

                        <div class="flex items-center justify-between mb-4 mt-4">
                            <button type="button" id="addItemBtn" 
                                    class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                                <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                            </button>
                        </div>

                        @error('items')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <a href="{{ route('inventory.exports.show', $export) }}" 
                           class="px-6 py-2 border rounded-lg hover:bg-gray-50">
                            Hủy
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-save mr-2"></i>Cập nhật
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
                    <span class="font-bold" id="totalProducts">0</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng số lượng xuất:</span>
                    <span class="font-bold text-red-600" id="totalQuantity">0</span>
                </div>
            </div>

            <div class="mt-6 p-4 bg-red-50 rounded-lg border border-red-200">
                <p class="text-sm text-red-800">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>Lưu ý:</strong> Xuất kho sẽ trừ số lượng trong kho. Kiểm tra kỹ trước khi lưu.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<?php
$existingItems = $export->items->map(function($item) {
    return [
        'id' => $item->id,
        'product_id' => $item->product_id,
        'size' => $item->size,
        'quantity' => $item->quantity,
        'note' => $item->note ?? '',
    ];
});

$productsData = $products->map(function($product) {
    return [
        'id' => $product->id,
        'name' => $product->name,
        'image_url' => $product->image_url,
    ];
});

$sizesJson = json_encode($sizes);
$productsHtml = '';
foreach($products as $product) {
    $productsHtml .= '<option value="' . $product->id . '">' . htmlspecialchars($product->name) . '</option>';
}
?>
<script>
var existingItems = <?php echo json_encode($existingItems); ?>;
var productsData = <?php echo json_encode($productsData); ?>;
var itemIndex = 0;

$(document).ready(function() {
    // Load existing items
    if (existingItems.length > 0) {
        existingItems.forEach(function(item) {
            addItem(item);
        });
    } else {
        // Add one empty item if no existing items
        addItem();
    }
    
    // Add item button
    $('#addItemBtn').on('click', function() {
        addItem();
    });
    
    // Update totals on any change
    $(document).on('change input', 'input[name*="[quantity]"]', function() {
        updateTotals();
    });
});

function addItem(existingItem) {
    existingItem = existingItem || { id: '', product_id: '', size: '', quantity: 1, note: '' };
    
    var sizes = <?php echo $sizesJson; ?>;
    var sizeOptions = '';
    for (var i = 0; i < sizes.length; i++) {
        var selected = (existingItem.size == sizes[i]) ? 'selected' : '';
        sizeOptions += '<option value="' + sizes[i] + '" ' + selected + '>' + sizes[i] + '</option>';
    }
    
    var itemHtml = '<div class="bg-red-50 p-4 rounded-lg border border-red-200 item-row" data-index="' + itemIndex + '">' +
        '<div class="flex items-start justify-between mb-3">' +
            '<div class="flex items-center space-x-2">' +
                '<span class="text-sm font-medium text-gray-700">Sản phẩm <span class="item-number">' + (itemIndex + 1) + '</span></span>' +
                '<img class="product-image w-10 h-10 object-cover rounded border border-gray-300" style="display:none;">' +
            '</div>' +
            '<button type="button" class="remove-item text-red-600 hover:text-red-800">' +
                '<i class="fas fa-times"></i>' +
            '</button>' +
        '</div>' +
        
        // Hidden ID field for existing items
        '<input type="hidden" name="items[' + itemIndex + '][id]" value="' + (existingItem.id || '') + '">' +
        
        '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">' +
            '<div>' +
                '<label class="block text-sm text-gray-600 mb-1">Sản phẩm</label>' +
                '<select name="items[' + itemIndex + '][product_id]" class="product-select chosen-select w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" required>' +
                    '<option value="">-- Chọn sản phẩm --</option>' +
                    '<?php echo $productsHtml; ?>' +
                '</select>' +
            '</div>' +
            '<div>' +
                '<label class="block text-sm text-gray-600 mb-1">Size</label>' +
                '<select name="items[' + itemIndex + '][size]" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" required>' +
                    '<option value="">-- Chọn size --</option>' +
                    sizeOptions +
                '</select>' +
            '</div>' +
            '<div>' +
                '<label class="block text-sm text-gray-600 mb-1">Số lượng xuất</label>' +
                '<input type="number" name="items[' + itemIndex + '][quantity]" min="1" value="' + existingItem.quantity + '" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" required>' +
            '</div>' +
            '<div>' +
                '<label class="block text-sm text-gray-600 mb-1">Ghi chú</label>' +
                '<input type="text" name="items[' + itemIndex + '][note]" value="' + (existingItem.note || '') + '" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Ghi chú...">' +
            '</div>' +
        '</div>' +
    '</div>';
    
    $('#itemsContainer').append(itemHtml);
    
    // Set product selection if existing
    if (existingItem.product_id) {
        var $row = $('.item-row[data-index="' + itemIndex + '"]');
        $row.find('.product-select').val(existingItem.product_id);
        
        // Show product image
        var product = productsData.find(function(p) { return p.id == existingItem.product_id; });
        if (product && product.image_url) {
            $row.find('.product-image')
                .attr('src', product.image_url)
                .attr('alt', product.name)
                .attr('title', product.name)
                .show();
        }
    }
    
    itemIndex++;
    updateItemNumbers();
    updateTotals();
    $('.chosen-select').chosen({ width: '100%' }); 
}

// Remove item
$(document).on('click', '.remove-item', function() {
    if ($('.item-row').length > 1) {
        $(this).closest('.item-row').remove();
        updateItemNumbers();
        updateTotals();
    } else {
        alert('Phải có ít nhất 1 sản phẩm!');
    }
});

// Show product image when selected
$(document).on('change', '.product-select', function() {
    var productId = $(this).val();
    var $row = $(this).closest('.item-row');
    var $img = $row.find('.product-image');
    
    if (productId) {
        var product = productsData.find(function(p) { return p.id == productId; });
        if (product && product.image_url) {
            $img.attr('src', product.image_url);
            $img.attr('alt', product.name);
            $img.attr('title', product.name);
            $img.show();
        } else {
            $img.hide();
        }
    } else {
        $img.hide();
    }
});

function updateItemNumbers() {
    $('.item-row').each(function(index) {
        $(this).find('.item-number').text(index + 1);
    });
}

function updateTotals() {
    var totalProducts = $('.item-row').length;
    var totalQuantity = 0;
    
    $('input[name*="[quantity]"]').each(function() {
        var qty = parseInt($(this).val()) || 0;
        totalQuantity += qty;
    });
    
    $('#totalProducts').text(totalProducts);
    $('#totalQuantity').text(totalQuantity);
}
</script>
@endpush

@endsection
