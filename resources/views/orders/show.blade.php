@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . $order->id)

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('orders.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Chi tiết đơn hàng #{{ $order->id }}</h1>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('orders.edit', $order) }}" 
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="fas fa-edit mr-1"></i>Sửa
            </a>
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
                        <p class="font-medium"><a target="_blank" href="{{ route('customers.edit', $order->customer) }}">{{ $order->customer->name }}</a></p>
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
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-box mr-2 text-indigo-600"></i>Sản phẩm
                </h2>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                    <div class="border rounded-lg p-4">
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
                                <h3 class="font-semibold text-gray-800">{{ $item->product->name }}</h3>
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
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                            @elseif($order->status == 'confirmed') bg-blue-100 text-blue-800
                            @elseif($order->status == 'shipping') bg-purple-100 text-purple-800
                            @elseif($order->status == 'completed') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $statuses[$order->status] ?? $order->status }}
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
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Giảm giá:</span>
                            <span class="font-bold text-lg text-yellow-600">{{ number_format($order->discount_amount) }}đ</span>
                        </div>
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

            <!-- Quick Actions -->

            <!-- Quick Status Update -->
            <div class="mt-6 p-6 bg-white rounded-lg shadow border-t">
                <h3 class="font-medium mb-3">Cập nhật trạng thái</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Models\Order::getStatuses() as $key => $label)
                    <button onclick="updateStatus('{{ $key }}')"
                            class="px-3 py-1 text-xs rounded-full {{ $order->status === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                    @endforeach
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
</script>
@endpush
@endsection
