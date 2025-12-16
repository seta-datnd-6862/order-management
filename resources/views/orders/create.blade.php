@extends('layouts.app')

@section('title', 'Tạo đơn hàng')

@section('content')
<div x-data="orderForm()">
    <div class="flex items-center mb-6">
        <a href="{{ route('orders.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Tạo đơn hàng mới</h1>
    </div>

    <form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Order Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Selection -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="fas fa-user mr-2 text-indigo-600"></i>Thông tin khách hàng
                    </h2>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Khách hàng <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" required
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Chọn khách hàng --</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} {{ $customer->phone ? "- {$customer->phone}" : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <a href="{{ route('customers.create') }}" target="_blank" 
                           class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-plus mr-1"></i>Thêm khách hàng mới
                        </a>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ngày đặt hàng</label>
                        <input type="date" name="created_at" 
                               value="{{ old('created_at') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                               placeholder="Ngày đặt hàng...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú đơn hàng</label>
                        <textarea name="note" rows="2"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Ghi chú cho đơn hàng...">{{ old('note') }}</textarea>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">
                            <i class="fas fa-box mr-2 text-indigo-600"></i>Sản phẩm
                        </h2>
                        <button type="button" @click="addItem()" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                            <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="border rounded-lg p-4 relative">
                                <button type="button" @click="removeItem(index)" 
                                        x-show="items.length > 1"
                                        class="absolute top-2 right-2 text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Product -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Sản phẩm <span class="text-red-500">*</span>
                                        </label>
                                        <select :name="`items[${index}][product_id]`" 
                                                x-model="item.product_id"
                                                @change="onProductChange(index)"
                                                required
                                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                                            <option value="">-- Chọn sản phẩm --</option>
                                            @foreach($products as $product)
                                            <option value="{{ $product->id }}" 
                                                    data-price="{{ $product->default_price }}"
                                                    data-image="{{ $product->image_url }}">
                                                {{ $product->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Size -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Size <span class="text-red-500">*</span>
                                        </label>
                                        <select :name="`items[${index}][size]`" 
                                                x-model="item.size"
                                                required
                                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                                            <option value="">-- Chọn size --</option>
                                            @foreach($sizes as $size)
                                            <option value="{{ $size }}">{{ $size }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Quantity -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Số lượng <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" 
                                               :name="`items[${index}][quantity]`" 
                                               x-model.number="item.quantity"
                                               min="1" 
                                               required
                                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    </div>

                                    <!-- Price -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Giá <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" 
                                               :name="`items[${index}][price]`" 
                                               x-model.number="item.price"
                                               min="0" 
                                               required
                                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    </div>

                                    <!-- Image -->

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh sản phẩm</label>
                                        <div class="flex items-center space-x-4">
                                            <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                                <template x-if="item.preview || item.productImage">
                                                    <img :src="item.preview || item.productImage" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!item.preview && !item.productImage">
                                                    <i class="fas fa-image text-xl text-gray-400"></i>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <div class="flex items-center space-x-4">
                                            <input type="file" 
                                                   :name="`items[${index}][image]`" 
                                                   accept="image/*"
                                                   @change="onImageChange(index, $event)"
                                                   class="flex-1 px-3 py-2 border rounded-lg text-sm">
                                        </div>
                                    </div>

                                    <!-- Note -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                                        <input type="text" 
                                               :name="`items[${index}][note]`" 
                                               x-model="item.note"
                                               placeholder="Ghi chú cho sản phẩm này..."
                                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <!-- Item Subtotal -->
                                <div class="mt-3 pt-3 border-t flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Thành tiền:</span>
                                    <span class="font-semibold text-indigo-600" x-text="formatCurrency(item.price * item.quantity)"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    @error('items')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
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
                            <span class="font-medium" x-text="totalItems()"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tổng số lượng:</span>
                            <span class="font-medium" x-text="totalQuantity()"></span>
                        </div>
                        <div class="border-t pt-3 flex justify-between text-lg">
                            <span class="font-semibold">Tổng tiền:</span>
                            <span class="font-bold text-indigo-600" x-text="formatCurrency(totalAmount())"></span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <button type="submit" 
                                class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                            <i class="fas fa-save mr-2"></i>Tạo đơn hàng
                        </button>
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
<script>
function orderForm() {
    return {
        items: [{ product_id: '', size: '', quantity: 1, price: 0, note: '', preview: null, productImage: null }],
        
        addItem() {
            this.items.push({ product_id: '', size: '', quantity: 1, price: 0, note: '', preview: null, productImage: null });
        },
        
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        
        onProductChange(index) {
            const select = document.querySelector(`select[name="items[${index}][product_id]"]`);
            const option = select.options[select.selectedIndex];
            if (option) {
                this.items[index].price = parseInt(option.dataset.price) || 0;
                this.items[index].productImage = option.dataset.image || null;
            }
        },
        
        onImageChange(index, event) {
            const file = event.target.files[0];
            if (file) {
                this.items[index].preview = URL.createObjectURL(file);
            }
        },
        
        totalItems() {
            return this.items.filter(i => i.product_id).length;
        },
        
        totalQuantity() {
            return this.items.reduce((sum, i) => sum + (parseInt(i.quantity) || 0), 0);
        },
        
        totalAmount() {
            return this.items.reduce((sum, i) => sum + ((parseInt(i.price) || 0) * (parseInt(i.quantity) || 0)), 0);
        },
        
        formatCurrency(value) {
            return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
        }
    }
}
</script>
@endpush
@endsection
