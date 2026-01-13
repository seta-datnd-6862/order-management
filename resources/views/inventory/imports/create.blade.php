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

            <form action="{{ route('inventory.imports.store') }}" method="POST" id="importForm">
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
                        <select name="supplier" 
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('supplier') border-red-500 @enderror"
                                required>
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

                        <div id="itemsContainer" class="space-y-4">
                            <!-- Items will be added here dynamically -->
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
                    <span class="font-bold" id="totalProducts">0</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng số lượng:</span>
                    <span class="font-bold text-indigo-600" id="totalQuantity">0</span>
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mỗi sản phẩm có thể thêm nhiều size với số lượng khác nhau.
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
$sizesJson = json_encode($sizes);
$productsHtml = '';
foreach($products as $product) {
    $productsHtml .= '<option value="' . $product->id . '">' . htmlspecialchars($product->name) . '</option>';
}
?>
<script>
var productsData = <?php echo json_encode($productsData); ?>;
var itemIndex = 0;

$(document).ready(function() {

    // Add first item by default
    addItem();
    
    // Add item button
    $('#addItemBtn').on('click', function() {
        addItem();
    });
    
    // Update totals on any change
    $(document).on('change input', 'input[name*="[quantity]"]', function() {
        updateTotals();
    });
});

function addItem() {
    var sizes = <?php echo $sizesJson; ?>;
    
    var itemHtml = '<div class="bg-gray-50 p-4 rounded-lg border item-row" data-index="' + itemIndex + '">' +
        '<div class="flex items-start justify-between mb-3">' +
            '<div class="flex items-center space-x-2">' +
                '<span class="text-sm font-medium text-gray-700">Sản phẩm <span class="item-number">' + (itemIndex + 1) + '</span></span>' +
                '<img class="product-image w-10 h-10 object-cover rounded border border-gray-300" style="display:none;">' +
            '</div>' +
            '<button type="button" class="remove-item text-red-600 hover:text-red-800">' +
                '<i class="fas fa-times"></i>' +
            '</button>' +
        '</div>' +
        '<div class="mb-3">' +
            '<label class="block text-sm text-gray-600 mb-1">Sản phẩm <span class="text-red-500">*</span></label>' +
            '<select name="items[' + itemIndex + '][product_id]" class="product-select chosen-select w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" required>' +
                '<option value="">-- Chọn sản phẩm --</option>' +
                '<?php echo $productsHtml; ?>' +
            '</select>' +
        '</div>' +
        '<div class="mb-3">' +
            '<label class="block text-sm text-gray-600 mb-1">Ghi chú sản phẩm</label>' +
            '<input type="text" name="items[' + itemIndex + '][product_note]" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Ghi chú chung về sản phẩm...">' +
        '</div>' +
        '<div class="border-t pt-3">' +
            '<div class="flex items-center justify-between mb-2">' +
                '<label class="text-sm font-medium text-gray-700">Size & Số lượng <span class="text-red-500">*</span></label>' +
                '<button type="button" class="add-size-btn px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">' +
                    '<i class="fas fa-plus mr-1"></i>Thêm size' +
                '</button>' +
            '</div>' +
            '<div class="sizes-container space-y-2">' +
                '<!-- Sizes will be added here -->' +
            '</div>' +
        '</div>' +
    '</div>';
    
    $('#itemsContainer').append(itemHtml);
    
    // Add first size by default
    addSize(itemIndex);
    
    itemIndex++;
    updateItemNumbers();
    updateTotals();
    // device is not ipad
    
    if (!isMobileDevice()) {
        $('.chosen-select').chosen({ width: '100%' });
    }

}

// Add size to a product item
$(document).on('click', '.add-size-btn', function() {
    var $itemRow = $(this).closest('.item-row');
    var index = $itemRow.data('index');
    addSize(index);
});

function addSize(itemIndex) {
    var sizes = <?php echo $sizesJson; ?>;
    var sizeOptions = '';
    for (var i = 0; i < sizes.length; i++) {
        sizeOptions += '<option value="' + sizes[i] + '">' + sizes[i] + '</option>';
    }
    
    var sizeIndex = $('.item-row[data-index="' + itemIndex + '"] .size-row').length;
    
    var sizeHtml = '<div class="size-row flex items-center gap-2 bg-white p-2 rounded border">' +
        '<div class="flex-1">' +
            '<select name="items[' + itemIndex + '][sizes][' + sizeIndex + '][size]" class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-indigo-500 text-sm" required>' +
                '<option value="">-- Size --</option>' +
                sizeOptions +
            '</select>' +
        '</div>' +
        '<div class="flex-1">' +
            '<input type="number" name="items[' + itemIndex + '][sizes][' + sizeIndex + '][quantity]" min="1" value="1" class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="SL" required>' +
        '</div>' +
        '<div class="flex-1">' +
            '<input type="text" name="items[' + itemIndex + '][sizes][' + sizeIndex + '][note]" class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Ghi chú size">' +
        '</div>' +
        '<button type="button" class="remove-size-btn text-red-600 hover:text-red-800 px-2">' +
            '<i class="fas fa-times"></i>' +
        '</button>' +
    '</div>';
    
    $('.item-row[data-index="' + itemIndex + '"] .sizes-container').append(sizeHtml);
    updateTotals();
}

// Remove size
$(document).on('click', '.remove-size-btn', function() {
    var $itemRow = $(this).closest('.item-row');
    var sizeCount = $itemRow.find('.size-row').length;
    
    if (sizeCount > 1) {
        $(this).closest('.size-row').remove();
        updateTotals();
    } else {
        alert('Mỗi sản phẩm phải có ít nhất 1 size!');
    }
});

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
