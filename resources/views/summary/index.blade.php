@extends('layouts.app')

@section('title', 'Tổng hợp sản phẩm')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
        <i class="fas fa-chart-bar mr-2 text-indigo-600"></i>Tổng hợp sản phẩm cần đặt
    </h1>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái đơn hàng</label>
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">Tất cả</option>
                @foreach($statuses as $key => $label)
                <option value="{{ $key }}" {{ $status == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
            <input type="date" name="from_date" value="{{ $fromDate }}"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
            <input type="date" name="to_date" value="{{ $toDate }}"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
            <a href="{{ route('summary.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-shopping-cart text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Số đơn hàng</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-box text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Loại sản phẩm</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalProducts }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-cubes text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Tổng số lượng</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalItems }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Financial Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Tổng giá trị đơn</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($financialStats['total_amount']) }}đ</p>
            </div>
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Đã cọc</p>
                <p class="text-2xl font-bold text-green-900">{{ number_format($financialStats['deposit_amount']) }}đ</p>
            </div>
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-hand-holding-usd text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Giảm giá</p>
                <p class="text-2xl font-bold text-yellow-900">{{ number_format($financialStats['discount_amount']) }}đ</p>
            </div>
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-tag text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Còn phải thu</p>
                <p class="text-2xl font-bold text-red-900">{{ number_format($financialStats['remaining_amount']) }}đ</p>
            </div>
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-coins text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Button -->
@if($totalOrders > 0 && $status)
@php
    $statusFlow = [
        'new' => 'preparing',
        'preparing' => 'ordered',
        'ordered' => 'shipping',
        'shipping' => 'delivered',
    ];
    $nextStatus = $statusFlow[$status] ?? null;
@endphp

@if($nextStatus)
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1 text-indigo-600"></i>
                Có <strong>{{ $totalOrders }}</strong> đơn hàng đang ở trạng thái 
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100">{{ $statuses[$status] }}</span>
            </p>
        </div>
        <button onclick="moveAllToNextStatus()" 
                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition">
            <i class="fas fa-arrow-right mr-2"></i>
            Chuyển tất cả sang "{{ $statuses[$nextStatus] }}"
        </button>
    </div>
</div>
@endif
@endif

<!-- Summary -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b bg-yellow-50">
        <p class="text-sm text-yellow-800">
            <i class="fas fa-info-circle mr-1"></i>
            Bảng dưới đây tổng hợp tất cả sản phẩm từ các đơn hàng
            @if($status)
            có trạng thái <strong>"{{ $statuses[$status] }}"</strong>
            @endif
            @if($fromDate || $toDate)
            <strong>
                @if($fromDate && $toDate)
                từ {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}
                @elseif($fromDate)
                từ {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }}
                @else
                đến {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}
                @endif
            </strong>
            @endif.
            Sử dụng để double check với ứng dụng đặt hàng.
        </p>
    </div>
    
    @if($summary->count() > 0)
    <div class="p-6">
        <div class="space-y-4">
            @foreach($summary as $index => $item)
            <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 hover:shadow-md transition">
                <div class="flex items-start gap-4">
                    <!-- Product Image -->
                    <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                        @if($item['product_image'])
                        <img src="{{ $item['product_image'] }}" 
                             alt="{{ $item['product_name'] }}" 
                             class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <i class="fas fa-image text-3xl"></i>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Product Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm">
                                        {{ $index + 1 }}
                                    </span>
                                    <h4 class="font-bold text-gray-900 text-lg">{{ $item['product_name'] }}</h4>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Tổng số lượng</p>
                                <p class="text-2xl font-bold text-indigo-600">{{ number_format($item['total_quantity']) }}</p>
                            </div>
                        </div>
                        
                        <!-- Sizes Breakdown -->
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-600 mb-2 uppercase">Chi tiết theo size:</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                @foreach($item['sizes'] as $sizeInfo)
                                <div class="bg-white rounded-lg p-3 border border-gray-200 hover:border-indigo-300 transition">
                                    @if($sizeInfo['sample_image'])
                                    <div class="w-full aspect-square bg-gray-100 rounded mb-2 overflow-hidden">
                                        <img src="{{ $sizeInfo['sample_image'] }}" 
                                             alt="Size {{ $sizeInfo['size'] }}" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    @endif
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-700 mb-1">Size {{ $sizeInfo['size'] }}</p>
                                        <p class="text-lg font-bold text-indigo-600">{{ $sizeInfo['quantity'] }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Summary Footer -->
        <div class="mt-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-6 border-2 border-green-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-calculator text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tổng cộng tất cả sản phẩm</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($totalItems) }} <span class="text-lg text-gray-600">sản phẩm</span></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Số loại sản phẩm</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ $totalProducts }} <span class="text-base text-gray-600">loại</span></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Copy-friendly summary -->
    <div class="p-4 border-t bg-gray-50">
        <h3 class="font-medium text-gray-700 mb-2">
            <i class="fas fa-copy mr-1"></i>Danh sách copy nhanh:
        </h3>
        <div class="bg-white border rounded-lg p-4 text-sm font-mono whitespace-pre-wrap" id="copy-text">@foreach($summary as $item){{ $item['product_name'] }}:
@foreach($item['sizes'] as $sizeInfo)  - Size {{ $sizeInfo['size'] }}: {{ $sizeInfo['quantity'] }} cái
@endforeach  Tổng: {{ $item['total_quantity'] }} cái

@endforeach---
TỔNG CỘNG: {{ $totalItems }} sản phẩm ({{ $totalProducts }} loại)</div>
        <button onclick="copyToClipboard()" 
                class="mt-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
            <i class="fas fa-copy mr-1"></i>Copy danh sách
        </button>
    </div>
    @else
    <div class="p-12 text-center text-gray-500">
        <i class="fas fa-box-open text-4xl mb-4 text-gray-300"></i>
        <p>Không có sản phẩm nào với bộ lọc hiện tại</p>
        <p class="text-sm mt-2">Thử thay đổi trạng thái hoặc khoảng thời gian để xem kết quả</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyToClipboard() {
    const text = document.getElementById('copy-text').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã copy danh sách!');
    });
}

function moveAllToNextStatus() {
    const currentStatus = '{{ $status }}';
    const nextStatus = '{{ $nextStatus ?? '' }}';
    const totalOrders = {{ $totalOrders }};
    const nextStatusLabel = '{{ isset($nextStatus) ? $statuses[$nextStatus] : '' }}';
    
    if (!nextStatus) {
        alert('Không thể chuyển trạng thái tiếp theo');
        return;
    }
    
    if (!confirm(`Bạn có chắc muốn chuyển ${totalOrders} đơn hàng sang trạng thái "${nextStatusLabel}"?`)) {
        return;
    }
    
    fetch('{{ route("summary.moveToNextStatus") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            current_status: currentStatus,
            from_date: '{{ $fromDate }}',
            to_date: '{{ $toDate }}'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Có lỗi xảy ra: ' + err.message);
    });
}
</script>
@endpush
@endsection
