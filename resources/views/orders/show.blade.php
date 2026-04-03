@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . $order->id)

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('orders.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Chi tiết đơn hàng #{{ $order->id }}</h1>
                
                {{-- Overall Inventory Status Badge --}}
                @if(in_array($order->status, ['ordered', 'preparing']))
                    @if($order->inventory_status === 'full')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 border border-green-300 mt-2">
                        <i class="fas fa-check-circle mr-2"></i>Hàng đủ - Có thể ship
                    </span>
                    @elseif($order->inventory_status === 'partial')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 border border-orange-300 mt-2">
                        <i class="fas fa-exclamation-circle mr-2"></i>Hàng thiếu một phần
                    </span>
                    @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 border border-red-300 mt-2">
                        <i class="fas fa-times-circle mr-2"></i>Chưa có hàng
                    </span>
                    @endif
                @endif
            </div>
        </div>
        <div class="flex space-x-2">
            {{-- Split Order Button --}}
            <button onclick="openSplitModal()" 
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                    {{ $order->items->count() < 2 ? 'disabled' : '' }}
                    title="{{ $order->items->count() < 2 ? 'Cần ít nhất 2 sản phẩm để tách đơn' : 'Tách đơn hàng' }}">
                <i class="fas fa-code-branch mr-1"></i>Tách đơn
            </button>
            <a href="{{ route('orders.edit', $order) }}" 
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="fas fa-edit mr-1"></i>Sửa
            </a>
            {{-- Viettel Post Button --}}
            @if($order->hasViettelOrder())
                <a href="{{ route('viettel-posts.show', $order->viettelOrder) }}" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-truck mr-1"></i>Xem vận chuyển
                </a>
            @else
                <a href="{{ route('viettel-posts.create-from-order', $order) }}" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-plus-circle mr-1"></i>Tạo đơn Viettel Post
                </a>
            @endif
            <form action="{{ route('orders.destroy', $order) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-1"></i>Xóa
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-indigo-600"></i>Thông tin khách hàng
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Tên khách hàng</p>
                        <p class="font-medium"><a target="_blank" href="{{ route('customers.edit', $order->customer) }}" class="text-indigo-600 hover:text-indigo-800">{{ $order->customer->name }}</a></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Số điện thoại</p>
                        <div class="flex items-center gap-2">
                            <p class="font-medium">{{ $order->customer->phone ?? '-' }}</p>
                            @if($order->customer->phone)
                            <button onclick="copyToClipboard('{{ $order->customer->phone }}')" 
                                    class="text-gray-400 hover:text-indigo-600 transition"
                                    title="Copy số điện thoại">
                                <i class="fas fa-copy text-sm"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">{{ $order->customer->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Địa chỉ</p>
                        <div class="flex items-center gap-2">
                            <p class="font-medium">{{ $order->customer->address ?? '-' }}</p>
                            @if($order->customer->address)
                            <button onclick="copyToClipboard('{{ $order->customer->address }}')" 
                                    class="text-gray-400 hover:text-indigo-600 transition flex-shrink-0"
                                    title="Copy địa chỉ">
                                <i class="fas fa-copy text-sm"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-shipping-fast mr-2 text-indigo-600"></i>Thông tin vận chuyển
                </h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Mã vận chuyển</p>
                        @if($order->shipping_code)
                            <p class="font-mono text-lg font-semibold text-indigo-600">{{ $order->shipping_code }}</p>
                        @else
                            <p class="text-gray-400 italic">Chưa có mã vận chuyển</p>
                        @endif
                    </div>
                    
                    @if($order->shipping_image)
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Ảnh mã vận chuyển</p>
                        <a href="{{ $order->shipping_image_url }}" target="_blank" class="block">
                            <img src="{{ $order->shipping_image_url }}" 
                                 alt="Shipping Code Image" 
                                 class="w-64 h-64 object-cover rounded-lg border hover:opacity-90 transition">
                        </a>
                    </div>
                    @endif

                    @if($order->hasViettelOrder())
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Trạng thái</p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $order->viettelOrder->status_color }}">
                                    {{ $order->viettelOrder->status_label }}
                                </span>
                            </div>
                            
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Dịch vụ</p>
                                <p class="text-sm font-medium">{{ $order->viettelOrder->service_name }}</p>
                            </div>
                            
                            @if($order->viettelOrder->money_collection > 0)
                            <div class="pt-3 border-t">
                                <p class="text-xs text-gray-500 mb-1">Tiền thu hộ</p>
                                <p class="text-lg font-bold text-green-600">
                                    {{ $order->viettelOrder->formatted_money_collection }}
                                </p>
                            </div>
                            @endif
                            
                            <a href="{{ route('viettel-posts.show', $order->viettelOrder) }}" 
                            class="block text-center px-3 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 text-sm font-medium mt-3">
                                Xem chi tiết vận chuyển →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Order Items with Inventory Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-box mr-2 text-indigo-600"></i>Sản phẩm
                </h2>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                    <div class="border rounded-lg p-4 {{ isset($item->inventory_check) && $item->inventory_check['status'] === 'insufficient' ? 'border-orange-300 bg-orange-50' : '' }}">
                        <div class="flex items-start space-x-4">
                            <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-2xl text-gray-400"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex items-start justify-between">
                                    <h3 class="font-semibold text-gray-800">{{ $item->product->name }}</h3>
                                    
                                    {{-- Item Inventory Status Badge --}}
                                    @if(isset($item->inventory_check))
                                        @if($item->inventory_check['status'] === 'sufficient')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                            <i class="fas fa-check mr-1"></i>Đủ hàng
                                        </span>
                                        @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 ml-2">
                                            <i class="fas fa-exclamation mr-1"></i>Thiếu {{ $item->inventory_check['shortage'] }}
                                        </span>
                                        @endif
                                    @endif
                                </div>
                                
                                <div class="mt-2 grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500">Size</p>
                                        <p class="font-medium">{{ $item->size }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Số lượng</p>
                                        <p class="font-medium">{{ $item->quantity }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Giá</p>
                                        <p class="font-medium">{{ number_format($item->price) }}đ</p>
                                    </div>
                                </div>
                                
                                {{-- Inventory Details --}}
                                @if(isset($item->inventory_check))
                                <div class="mt-3 p-3 bg-gray-50 rounded-lg border">
                                    <p class="text-xs font-semibold text-gray-600 mb-2">
                                        <i class="fas fa-warehouse mr-1"></i>Tình trạng kho:
                                    </p>
                                    <div class="grid grid-cols-3 gap-2 text-xs">
                                        <div>
                                            <span class="text-gray-500">Cần:</span>
                                            <span class="font-semibold text-indigo-600">{{ $item->inventory_check['needed'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Có sẵn:</span>
                                            <span class="font-semibold {{ $item->inventory_check['status'] === 'sufficient' ? 'text-green-600' : 'text-orange-600' }}">
                                                {{ $item->inventory_check['available'] }}
                                            </span>
                                        </div>
                                        @if($item->inventory_check['shortage'] > 0)
                                        <div>
                                            <span class="text-gray-500">Thiếu:</span>
                                            <span class="font-semibold text-red-600">{{ $item->inventory_check['shortage'] }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    @if($item->inventory_check['status'] === 'insufficient')
                                    <div class="mt-2 pt-2 border-t">
                                        <a href="{{ route('inventory.detail', $item->product) }}" 
                                           target="_blank"
                                           class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center">
                                            <i class="fas fa-external-link-alt mr-1"></i>
                                            Xem lịch sử nhập/xuất kho
                                        </a>
                                    </div>
                                    @endif
                                </div>
                                @endif
                                
                                @if($item->note)
                                <div class="mt-2">
                                    <p class="text-gray-500 text-sm">Ghi chú</p>
                                    <p class="text-sm">{{ $item->note }}</p>
                                </div>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Thành tiền</p>
                                <p class="font-semibold text-indigo-600">{{ number_format($item->price * $item->quantity) }}đ</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Order Note -->
            @if($order->note)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-sticky-note mr-2 text-indigo-600"></i>Ghi chú đơn hàng
                </h2>
                <p class="text-gray-700">{{ $order->note }}</p>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Order Summary -->
            <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-receipt mr-2 text-indigo-600"></i>Tóm tắt đơn hàng
                </h2>
                
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Ngày tạo:</span>
                        <span class="font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Trạng thái:</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $order->status_color }}">
                            {{ $order->status_label }}
                        </span>
                    </div>
                </div>

                <div class="border-t pt-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Số sản phẩm:</span>
                        <span class="font-medium">{{ $order->items->count() }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Tổng số lượng:</span>
                        <span class="font-medium">{{ $order->items->sum('quantity') }}</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold border-t pt-3">
                        <span>Tổng tiền hàng:</span>
                        <span class="text-indigo-600">{{ number_format($order->total_amount) }}đ</span>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="border-t mt-4 pt-4 space-y-3">
                    <h3 class="font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>Thanh toán
                    </h3>
                    
                    <div class="bg-green-50 rounded-lg p-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Đã cọc:</span>
                            <span class="font-semibold text-green-700">{{ number_format($order->deposit_amount) }}đ</span>
                        </div>
                        @if($order->deposit_amount > 0)
                        <div class="text-xs text-gray-500">
                            ({{ number_format($order->deposit_percentage, 1) }}% tổng đơn)
                        </div>
                        @endif
                    </div>

                    <div class="bg-yellow-50 rounded-lg p-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Giảm giá:</span>
                            <span class="font-semibold text-yellow-700">{{ number_format($order->discount_amount) }}đ</span>
                        </div>
                        @if($order->discount_amount > 0)
                        <div class="text-xs text-gray-500">
                            ({{ number_format($order->discount_percentage, 1) }}% tổng đơn)
                        </div>
                        @endif
                    </div>

                    <div class="bg-orange-50 rounded-lg p-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Còn phải thanh toán:</span>
                            <span class="font-bold text-lg text-orange-600">{{ number_format($order->remaining_amount) }}đ</span>
                        </div>
                    </div>

                    @if($order->remaining_amount == 0 && $order->deposit_amount > 0)
                    <div class="bg-green-100 border border-green-300 rounded-lg p-3 text-center">
                        <i class="fas fa-check-circle text-green-600 mr-1"></i>
                        <span class="text-sm font-semibold text-green-800">Đã thanh toán đủ</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Quick Status Update -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-3 flex items-center">
                    <i class="fas fa-sync-alt mr-2 text-indigo-600"></i>Cập nhật trạng thái
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Models\Order::getStatuses() as $key => $label)
                    <button onclick="updateStatus('{{ $key }}')"
                            class="px-3 py-1 text-xs rounded-full transition {{ $order->status === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>
            
            <!-- Inventory Summary (if applicable) -->
            @if(in_array($order->status, ['ordered', 'preparing']))
            <div class="bg-white rounded-lg shadow p-6 border-l-4 
                {{ $order->inventory_status === 'full' ? 'border-green-500' : ($order->inventory_status === 'partial' ? 'border-orange-500' : 'border-red-500') }}">
                <h3 class="font-semibold mb-3 flex items-center">
                    <i class="fas fa-warehouse mr-2 text-indigo-600"></i>Tổng quan kho
                </h3>
                
                @if($order->inventory_status === 'full')
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-800">Hàng đủ</p>
                            <p class="text-xs text-green-600">Có thể chuyển sang ship ngay</p>
                        </div>
                    </div>
                </div>
                @elseif($order->inventory_status === 'partial')
                <div class="bg-orange-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-circle text-orange-600 text-2xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-orange-800">Hàng thiếu</p>
                            <p class="text-xs text-orange-600">Cần nhập thêm hàng</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-orange-200">
                        <a href="{{ route('inventory.imports.create') }}" 
                           class="text-sm text-orange-700 hover:text-orange-900 flex items-center">
                            <i class="fas fa-plus-circle mr-1"></i>
                            Tạo đơn nhập kho
                        </a>
                    </div>
                </div>
                @else
                <div class="bg-red-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-times-circle text-red-600 text-2xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-red-800">Chưa có hàng</p>
                            <p class="text-xs text-red-600">Cần nhập hàng về</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-red-200">
                        <a href="{{ route('inventory.imports.create') }}" 
                           class="text-sm text-red-700 hover:text-red-900 flex items-center">
                            <i class="fas fa-plus-circle mr-1"></i>
                            Tạo đơn nhập kho
                        </a>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ==================== SPLIT ORDER MODAL ==================== --}}
<div x-data="splitOrderModal()" x-cloak>
    <!-- Backdrop -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 z-40"
         @click="open = false">
    </div>

    <!-- Modal -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden" @click.away="open = false">
            
            <!-- Modal Header -->
            <div class="bg-purple-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center text-white">
                    <i class="fas fa-code-branch text-xl mr-3"></i>
                    <div>
                        <h2 class="text-lg font-bold">Tách đơn hàng #{{ $order->id }}</h2>
                        <p class="text-purple-200 text-sm">Chọn sản phẩm để tạo đơn mới</p>
                    </div>
                </div>
                <button @click="open = false" class="text-white hover:text-purple-200 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="overflow-y-auto" style="max-height: calc(90vh - 180px);">
                <div class="p-6 space-y-6">

                    <!-- Step 1: Select Items -->
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span class="w-6 h-6 bg-purple-600 text-white rounded-full text-xs flex items-center justify-center mr-2">1</span>
                            Chọn sản phẩm chuyển sang đơn mới
                        </h3>
                        <p class="text-sm text-gray-500 mb-4">Chọn ít nhất 1 sản phẩm. Có thể điều chỉnh số lượng tách.</p>

                        <div class="space-y-3">
                            <template x-for="(item, index) in items" :key="item.id">
                                <div class="border rounded-lg p-4 transition"
                                     :class="item.selected ? 'border-purple-400 bg-purple-50' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center space-x-4">
                                        <!-- Checkbox -->
                                        <label class="flex-shrink-0 cursor-pointer">
                                            <input type="checkbox" x-model="item.selected" class="w-5 h-5 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                        </label>

                                        <!-- Image -->
                                        <div class="w-14 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                            <template x-if="item.image_url">
                                                <img :src="item.image_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!item.image_url">
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Info -->
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-gray-800 truncate" x-text="item.product_name"></p>
                                            <div class="flex items-center gap-3 text-sm text-gray-500 mt-1">
                                                <span>Size: <span x-text="item.size" class="font-medium text-gray-700"></span></span>
                                                <span>Giá: <span x-text="formatCurrency(item.price)" class="font-medium text-gray-700"></span></span>
                                            </div>
                                        </div>

                                        <!-- Quantity Selector -->
                                        <div class="flex-shrink-0" x-show="item.selected">
                                            <label class="text-xs text-gray-500 block mb-1 text-center">SL tách</label>
                                            <div class="flex items-center border rounded-lg overflow-hidden">
                                                <button type="button" @click="decreaseQty(index)" 
                                                        class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 transition"
                                                        :disabled="item.split_quantity <= 1">
                                                    <i class="fas fa-minus text-xs"></i>
                                                </button>
                                                <input type="number" x-model.number="item.split_quantity" 
                                                       :max="item.max_quantity" min="1"
                                                       @input="clampQty(index)"
                                                       class="w-12 text-center border-x py-1 text-sm focus:outline-none">
                                                <button type="button" @click="increaseQty(index)" 
                                                        class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 transition"
                                                        :disabled="item.split_quantity >= item.max_quantity">
                                                    <i class="fas fa-plus text-xs"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-400 text-center mt-1">
                                                / <span x-text="item.max_quantity"></span> hiện có
                                            </p>
                                        </div>

                                        <!-- Subtotal -->
                                        <div class="flex-shrink-0 text-right w-28" x-show="item.selected">
                                            <p class="text-xs text-gray-500">Thành tiền</p>
                                            <p class="font-semibold text-purple-600" x-text="formatCurrency(item.price * item.split_quantity)"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 2: New Order Details -->
                    <div class="border-t pt-6">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span class="w-6 h-6 bg-purple-600 text-white rounded-full text-xs flex items-center justify-center mr-2">2</span>
                            Thông tin đơn mới
                        </h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                                <select x-model="newOrder.status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    @foreach(\App\Models\Order::getStatuses() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Deposit -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tiền cọc đơn mới</label>
                                <input type="number" x-model.number="newOrder.deposit_amount" min="0"
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="0">
                            </div>

                            <!-- Discount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Giảm giá đơn mới</label>
                                <input type="number" x-model.number="newOrder.discount_amount" min="0"
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="0">
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                                <input type="text" x-model="newOrder.note"
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="Ghi chú cho đơn mới...">
                            </div>
                        </div>
                    </div>

                    <!-- Summary Preview -->
                    <div class="border-t pt-6" x-show="selectedItems().length > 0">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span class="w-6 h-6 bg-purple-600 text-white rounded-full text-xs flex items-center justify-center mr-2">3</span>
                            Xem trước kết quả
                        </h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Original Order After Split -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-800 text-sm mb-3">
                                    <i class="fas fa-file-alt mr-1"></i>Đơn gốc #{{ $order->id }} (sau tách)
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Số SP còn lại:</span>
                                        <span class="font-medium" x-text="remainingItemsCount()"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tổng tiền:</span>
                                        <span class="font-semibold text-blue-600" x-text="formatCurrency(remainingTotal())"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- New Order Preview -->
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <h4 class="font-semibold text-purple-800 text-sm mb-3">
                                    <i class="fas fa-file-medical mr-1"></i>Đơn mới (sẽ tạo)
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Số SP:</span>
                                        <span class="font-medium" x-text="selectedItems().length"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tổng tiền:</span>
                                        <span class="font-semibold text-purple-600" x-text="formatCurrency(splitTotal())"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Đã cọc:</span>
                                        <span class="font-medium text-green-600" x-text="formatCurrency(newOrder.deposit_amount)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Giảm giá:</span>
                                        <span class="font-medium text-yellow-600" x-text="formatCurrency(newOrder.discount_amount)"></span>
                                    </div>
                                    <div class="flex justify-between border-t pt-2 mt-2">
                                        <span class="text-gray-600">Còn thanh toán:</span>
                                        <span class="font-bold text-orange-600" x-text="formatCurrency(newOrderRemaining())"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warning if original becomes empty -->
                        <div class="mt-3 bg-yellow-50 border border-yellow-300 rounded-lg p-3 flex items-start"
                             x-show="willOriginalBeEmpty()">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 mt-0.5"></i>
                            <p class="text-sm text-yellow-800">
                                Lưu ý: Tất cả sản phẩm sẽ được chuyển sang đơn mới. Đơn gốc sẽ không còn sản phẩm nào.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
                <p class="text-sm text-gray-500">
                    Đã chọn <span class="font-semibold text-purple-600" x-text="selectedItems().length"></span> sản phẩm
                    · Tổng: <span class="font-semibold text-purple-600" x-text="formatCurrency(splitTotal())"></span>
                </p>
                <div class="flex space-x-3">
                    <button @click="open = false" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Hủy
                    </button>
                    <button @click="submitSplit()" 
                            :disabled="selectedItems().length === 0 || submitting"
                            :class="selectedItems().length === 0 || submitting ? 'bg-gray-300 cursor-not-allowed' : 'bg-purple-600 hover:bg-purple-700'"
                            class="px-6 py-2 text-white rounded-lg transition flex items-center">
                        <i class="fas fa-code-branch mr-2"></i>
                        <span x-text="submitting ? 'Đang xử lý...' : 'Xác nhận tách đơn'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
function updateStatus(status) {
    fetch('{{ route("orders.updateStatus", $order) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.createElement('div');
        toast.className = 'fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        toast.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Đã copy!';
        document.body.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 2000);
    });
}

// ==================== SPLIT ORDER LOGIC ====================
<?php
    $itemsJson = $order->items->map(function ($item) {
        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product->name,
            'size' => $item->size,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'note' => $item->note,
            'image_url' => $item->image_url,
        ];
    });
?>

const orderItemsData = @json($itemsJson);

function openSplitModal() {
    window.dispatchEvent(new CustomEvent('open-split-modal'));
}

function splitOrderModal() {
    return {
        open: false,
        submitting: false,
        items: [],
        newOrder: {
            status: '{{ $order->status }}',
            deposit_amount: 0,
            discount_amount: 0,
            note: '',
        },

        init() {
            this.items = orderItemsData.map(item => ({
                ...item,
                selected: false,
                split_quantity: item.quantity,
                max_quantity: item.quantity,
            }));

            window.addEventListener('open-split-modal', () => {
                // Reset state on each open
                this.items = orderItemsData.map(item => ({
                    ...item,
                    selected: false,
                    split_quantity: item.quantity,
                    max_quantity: item.quantity,
                }));
                this.newOrder = {
                    status: '{{ $order->status }}',
                    deposit_amount: 0,
                    discount_amount: 0,
                    note: '',
                };
                this.submitting = false;
                this.open = true;
            });
        },

        selectedItems() {
            return this.items.filter(i => i.selected);
        },

        decreaseQty(index) {
            if (this.items[index].split_quantity > 1) {
                this.items[index].split_quantity--;
            }
        },

        increaseQty(index) {
            if (this.items[index].split_quantity < this.items[index].max_quantity) {
                this.items[index].split_quantity++;
            }
        },

        clampQty(index) {
            let item = this.items[index];
            if (item.split_quantity < 1) item.split_quantity = 1;
            if (item.split_quantity > item.max_quantity) item.split_quantity = item.max_quantity;
        },

        splitTotal() {
            return this.selectedItems().reduce((sum, item) => sum + (item.price * item.split_quantity), 0);
        },

        remainingTotal() {
            let total = {{ $order->total_amount }};
            return total - this.splitTotal();
        },

        remainingItemsCount() {
            let totalItems = {{ $order->items->count() }};
            let fullyMoved = this.selectedItems().filter(i => i.split_quantity === i.max_quantity).length;
            return totalItems - fullyMoved;
        },

        willOriginalBeEmpty() {
            return this.items.every(item => item.selected && item.split_quantity === item.max_quantity);
        },

        newOrderRemaining() {
            let total = this.splitTotal();
            let remaining = total - this.newOrder.deposit_amount - this.newOrder.discount_amount;
            return Math.max(0, remaining);
        },

        formatCurrency(value) {
            if (!value && value !== 0) return '0đ';
            return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
        },

        submitSplit() {
            if (this.selectedItems().length === 0) return;
            
            if (!confirm('Bạn có chắc chắn muốn tách đơn hàng? Hành động này không thể hoàn tác.')) {
                return;
            }

            this.submitting = true;

            const payload = {
                items: this.selectedItems().map(item => ({
                    order_item_id: item.id,
                    quantity: item.split_quantity,
                })),
                status: this.newOrder.status,
                deposit_amount: this.newOrder.deposit_amount || 0,
                discount_amount: this.newOrder.discount_amount || 0,
                note: this.newOrder.note || '',
            };

            fetch('{{ route("orders.split", $order) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Show success & redirect to new order
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    toast.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    document.body.appendChild(toast);

                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000);
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                    this.submitting = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
                this.submitting = false;
            });
        }
    };
}
</script>
@endpush
@endsection
