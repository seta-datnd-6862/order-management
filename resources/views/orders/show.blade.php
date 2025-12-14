@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . $order->id)

@section('content')
<div class="flex items-center mb-6">
    <a href="{{ route('orders.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-gray-800">
        Đơn hàng #{{ $order->id }}
        <span class="ml-2 px-3 py-1 text-sm font-medium rounded-full {{ $order->status_color }}">
            {{ $order->status_label }}
        </span>
    </h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Order Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Customer Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">
                <i class="fas fa-user mr-2 text-indigo-600"></i>Thông tin khách hàng
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">Tên khách hàng</span>
                    <p class="font-medium">{{ $order->customer->name }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Số điện thoại</span>
                    <p class="font-medium">{{ $order->customer->phone ?: '-' }}</p>
                </div>
                @if($order->customer->facebook_link)
                <div>
                    <span class="text-sm text-gray-500">Facebook</span>
                    <p><a href="{{ $order->customer->facebook_link }}" target="_blank" class="text-blue-600 hover:underline">Xem profile</a></p>
                </div>
                @endif
                @if($order->customer->zalo_link)
                <div>
                    <span class="text-sm text-gray-500">Zalo</span>
                    <p><a href="{{ $order->customer->zalo_link }}" target="_blank" class="text-blue-600 hover:underline">Liên hệ</a></p>
                </div>
                @endif
            </div>
            @if($order->note)
            <div class="mt-4 pt-4 border-t">
                <span class="text-sm text-gray-500">Ghi chú đơn hàng</span>
                <p class="mt-1">{{ $order->note }}</p>
            </div>
            @endif
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">
                <i class="fas fa-box mr-2 text-indigo-600"></i>Sản phẩm ({{ $order->items->count() }})
            </h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg">
                    <div class="w-20 h-20 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                        @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <i class="fas fa-image text-2xl"></i>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-gray-900">{{ $item->product->name }}</h3>
                        <div class="flex flex-wrap gap-2 mt-1 text-sm text-gray-500">
                            <span class="px-2 py-0.5 bg-gray-200 rounded">Size: {{ $item->size }}</span>
                            <span class="px-2 py-0.5 bg-gray-200 rounded">SL: {{ $item->quantity }}</span>
                            <span class="px-2 py-0.5 bg-gray-200 rounded">{{ number_format($item->price) }}đ/cái</span>
                        </div>
                        @if($item->note)
                        <p class="mt-2 text-sm text-gray-600">{{ $item->note }}</p>
                        @endif
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-semibold text-indigo-600">{{ number_format($item->subtotal) }}đ</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Right: Summary & Actions -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-24">
            <h2 class="text-lg font-semibold mb-4">
                <i class="fas fa-receipt mr-2 text-indigo-600"></i>Tổng kết
            </h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Mã đơn hàng:</span>
                    <span class="font-medium">#{{ $order->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Ngày tạo:</span>
                    <span class="font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Số sản phẩm:</span>
                    <span class="font-medium">{{ $order->items->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Tổng số lượng:</span>
                    <span class="font-medium">{{ $order->items->sum('quantity') }}</span>
                </div>
                <div class="border-t pt-3 flex justify-between text-lg">
                    <span class="font-semibold">Tổng tiền:</span>
                    <span class="font-bold text-indigo-600">{{ number_format($order->total_amount) }}đ</span>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <a href="{{ route('orders.edit', $order) }}" 
                   class="block w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-center font-semibold">
                    <i class="fas fa-edit mr-2"></i>Sửa đơn hàng
                </a>
                <form action="{{ route('orders.destroy', $order) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa đơn hàng này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full px-6 py-3 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 font-semibold">
                        <i class="fas fa-trash mr-2"></i>Xóa đơn hàng
                    </button>
                </form>
            </div>

            <!-- Quick Status Update -->
            <div class="mt-6 pt-6 border-t">
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
