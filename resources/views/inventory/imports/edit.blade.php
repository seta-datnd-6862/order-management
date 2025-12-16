@extends('layouts.app')

@section('title', 'Sửa đơn nhập - ' . $import->import_code)

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.imports.show', $import) }}" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại chi tiết
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-6">
                <i class="fas fa-edit mr-2 text-indigo-600"></i>Sửa đơn nhập kho
            </h2>

            <form action="{{ route('inventory.imports.update', $import) }}" method="POST" 
                  x-data="importForm()" x-init="initItems()">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <!-- Import Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mã đơn nhập <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="import_code" value="{{ old('import_code', $import->import_code) }}" 
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
                            <option value="{{ $key }}" {{ old('supplier', $import->supplier) == $key ? 'selected' : '' }}>{{ $label }}</option>
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
                        <input type="date" name="import_date" value="{{ old('import_date', $import->import_date->format('Y-m-d')) }}"
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
                                  placeholder="Thông tin bổ sung về đơn nhập...">{{ old('note', $import->note) }}</textarea>
                    </div>

                    <!-- Items Section -->
                    <div class="border-t pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Danh sách sản phẩm</h3>
                            <button type="button" @click="addItem()" 
                                    class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium text-gray-700">Sản phẩm <span x-text="index + 1"></span></span>
                                            <!-- Product Image Preview -->
                                            <template x-if="item.product_id && getProductImage(item.product_id)">
                                                <img :src="getProductImage(item.product_id)" 
                                                     :alt="getProductName(item.product_id)"
                                                     class="w-10 h-10 object-cover rounded border border-gray-300"
                                                     :title="getProductName(item.product_id)">
                                            </template>
                                        </div>
                                        <button type="button" @click="removeItem(index)" 
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    <!-- Hidden ID field for existing items -->
                                    <input type="hidden" :name="'items[' + index + '][id]'" x-model="item.id">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <!-- Product -->
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">Sản phẩm</label>
                                            <select :name="'items[' + index + '][product_id]'" 
                                                    x-model="item.product_id"
                                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                                    required>
                                                <option value="">-- Chọn sản phẩm --</option>
                                                @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Size -->
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">Size</label>
                                            <select :name="'items[' + index + '][size]'" 
                                                    x-model="item.size"
                                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                                    required>
                                                <option value="">-- Chọn size --</option>
                                                @foreach($sizes as $size)
                                                <option value="{{ $size }}">{{ $size }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Quantity -->
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">Số lượng</label>
                                            <input type="number" 
                                                   :name="'items[' + index + '][quantity]'" 
                                                   x-model="item.quantity"
                                                   min="1"
                                                   class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                                   required>
                                        </div>

                                        <!-- Note -->
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">Ghi chú</label>
                                            <input type="text" 
                                                   :name="'items[' + index + '][note]'" 
                                                   x-model="item.note"
                                                   class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                                   placeholder="Ghi chú về sản phẩm...">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        @error('items')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <a href="{{ route('inventory.imports.show', $import) }}" 
                           class="px-6 py-2 border rounded-lg hover:bg-gray-50">
                            Hủy
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-save mr-2"></i>Cập nhật
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-24" x-data="importForm()">
            <h3 class="text-lg font-semibold mb-4">Tổng quan</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng sản phẩm:</span>
                    <span class="font-bold" x-text="items.length"></span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng số lượng:</span>
                    <span class="font-bold text-indigo-600" x-text="totalQuantity()"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<?php
$existingItems = $import->items->map(function($item) {
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
?>
<script>
const existingItems = @json($existingItems);
const productsData = @json($productsData);

function importForm() {
    return {
        items: [],
        initItems() {
            this.items = existingItems.length > 0 ? existingItems : [
                { id: '', product_id: '', size: '', quantity: 1, note: '' }
            ];
        },
        addItem() {
            this.items.push({ 
                id: '',
                product_id: '', 
                size: '', 
                quantity: 1, 
                note: '' 
            });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        totalQuantity() {
            return this.items.reduce((sum, item) => {
                return sum + (parseInt(item.quantity) || 0);
            }, 0);
        },
        getProductImage(productId) {
            if (!productId) return null;
            const product = productsData.find(p => p.id == productId);
            return product ? product.image_url : null;
        },
        getProductName(productId) {
            if (!productId) return '';
            const product = productsData.find(p => p.id == productId);
            return product ? product.name : '';
        }
    }
}
</script>
@endpush

@endsection
