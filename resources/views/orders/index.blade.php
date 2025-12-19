@extends('layouts.app')

@section('title', 'Đơn hàng')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
        <i class="fas fa-shopping-cart mr-2 text-indigo-600"></i>Đơn hàng
    </h1>
    <a href="{{ route('orders.create') }}" 
       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>Tạo đơn hàng
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <input type="text" name="search" value="{{ request('search') }}" 
               placeholder="Tìm theo tên khách..."
               class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        
        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tất cả trạng thái --</option>
            @foreach($statuses as $key => $label)
            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        
        <input type="date" name="date" value="{{ request('date') }}"
               class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        
        <select name="customer_id" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tất cả khách --</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                {{ $customer->name }}
            </option>
            @endforeach
        </select>
        
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
            <a href="{{ route('orders.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Bulk Actions -->
<div x-data="bulkActions()" x-cloak>
    {{-- Sorting Info for ORDERED status --}}
    @if(request('status') === 'ordered' && $orders->count() > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex items-center">
            <i class="fas fa-info-circle text-blue-600 mr-3"></i>
            <div>
                <p class="text-sm text-blue-800 font-medium">
                    Đơn hàng được sắp xếp theo độ ưu tiên:
                </p>
                <p class="text-xs text-blue-700 mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs mr-2">
                        <i class="fas fa-check-circle mr-1"></i>Hàng đủ
                    </span>
                    →
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-xs mx-2">
                        <i class="fas fa-exclamation-circle mr-1"></i>Hàng thiếu
                    </span>
                    →
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs ml-2">
                        <i class="fas fa-times-circle mr-1"></i>Chưa có hàng
                    </span>
                </p>
            </div>
        </div>
    </div>
    @endif

    <div x-show="selectedOrders.length > 0" class="bg-white rounded-lg shadow mb-4 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <span class="text-sm text-gray-600">
                Đã chọn <strong x-text="selectedOrders.length"></strong> đơn
            </span>
            <select x-model="bulkStatus" class="px-3 py-1 border rounded-lg text-sm">
                <option value="">-- Chuyển trạng thái --</option>
                @foreach($statuses as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <button @click="updateBulkStatus()" 
                    :disabled="!bulkStatus"
                    class="px-4 py-1 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                Áp dụng
            </button>
        </div>
    </div>

    <!-- Order List -->
    <div class="space-y-4">
        @forelse($orders as $order)
        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
            <div class="p-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-3">
                        <input type="checkbox" 
                               value="{{ $order->id }}" 
                               x-model="selectedOrders"
                               class="mt-1 h-4 w-4 text-indigo-600 rounded">
                        <div>
                            <div class="flex items-center space-x-2 flex-wrap">
                                <span class="font-bold text-lg">#{{ $order->id }}</span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                                
                                {{-- Inventory Status Label for ORDERED status --}}
                                @if(request('status') === 'ordered' && isset($order->inventory_status))
                                    @if($order->inventory_status === 'full')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 border border-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>Hàng đủ
                                    </span>
                                    @elseif($order->inventory_status === 'partial')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 border border-orange-300">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Hàng thiếu
                                    </span>
                                    @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 border border-red-300">
                                        <i class="fas fa-times-circle mr-1"></i>Chưa có hàng
                                    </span>
                                    @endif
                                @endif
                            </div>
                            <p class="text-gray-600 mt-1">
                                <i class="fas fa-user mr-1"></i>{{ $order->customer->name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-calendar mr-1"></i>{{ $order->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-lg text-indigo-600">
                            {{ number_format($order->total_amount) }}đ
                        </p>
                        <p class="text-sm text-gray-500">{{ $order->items->count() }} sản phẩm</p>
                    </div>
                </div>
                
                <!-- Order Items Preview -->
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($order->items->take(4) as $item)
                    <div class="flex items-center bg-gray-50 rounded-lg p-2 text-sm">
                        @if($item->image_url)
                        <img src="{{ $item->image_url }}" class="w-10 h-10 object-cover rounded mr-2">
                        @endif
                        <div>
                            <p class="font-medium">{{ Str::limit($item->product->name, 20) }}</p>
                            <p class="text-xs text-gray-500">{{ $item->size }} × {{ $item->quantity }}</p>
                        </div>
                    </div>
                    @endforeach
                    @if($order->items->count() > 4)
                    <div class="flex items-center text-gray-500 text-sm">
                        +{{ $order->items->count() - 4 }} sản phẩm khác
                    </div>
                    @endif
                </div>
                
                <!-- Quick Status Update -->
                <div class="mt-4 pt-4 border-t flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Chuyển:</span>
                        @php
                            $statusFlow = [
                                'new' => 'preparing',
                                'preparing' => 'ordered',
                                'ordered' => 'shipping',
                                'shipping' => 'delivered',
                            ];
                            $nextStatus = $statusFlow[$order->status] ?? null;
                        @endphp
                        @if($nextStatus)
                        <button onclick="updateStatus({{ $order->id }}, '{{ $nextStatus }}')"
                                class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full hover:bg-green-200">
                            → {{ $statuses[$nextStatus] }}
                        </button>
                        @endif
                        @if($order->status === 'shipping')
                        <button onclick="updateStatus({{ $order->id }}, 'failed')"
                                class="px-3 py-1 text-xs bg-red-100 text-red-800 rounded-full hover:bg-red-200">
                            → Giao thất bại
                        </button>
                        @endif
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('orders.show', $order) }}" 
                           class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('orders.edit', $order) }}" 
                           class="px-3 py-1 text-sm text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('orders.destroy', $order) }}" method="POST" class="inline"
                              onsubmit="return confirm('Bạn có chắc muốn xóa đơn hàng này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 text-sm text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
            <i class="fas fa-shopping-cart text-4xl mb-4 text-gray-300"></i>
            <p>Chưa có đơn hàng nào</p>
            <a href="{{ route('orders.create') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
                Tạo đơn hàng đầu tiên →
            </a>
        </div>
        @endforelse
    </div>
</div>

@if($orders->hasPages())
<div class="mt-6">
    {{ $orders->appends(request()->query())->links() }}
</div>
@endif

@push('scripts')
<script>
function updateStatus(orderId, status) {
    fetch(`/orders/${orderId}/status`, {
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

function bulkActions() {
    return {
        selectedOrders: [],
        bulkStatus: '',
        updateBulkStatus() {
            if (!this.bulkStatus || this.selectedOrders.length === 0) return;
            
            fetch('/orders/bulk-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    order_ids: this.selectedOrders,
                    status: this.bulkStatus
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    }
}
</script>
@endpush
@endsection
