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
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái đơn hàng</label>
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                @foreach($statuses as $key => $label)
                <option value="{{ $key }}" {{ $status == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tạo đơn</label>
            <input type="date" name="date" value="{{ $date }}"
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

<!-- Summary Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b bg-yellow-50">
        <p class="text-sm text-yellow-800">
            <i class="fas fa-info-circle mr-1"></i>
            Bảng dưới đây tổng hợp tất cả sản phẩm từ các đơn hàng có trạng thái 
            <strong>"{{ $statuses[$status] }}"</strong>
            {{ $date ? 'vào ngày ' . \Carbon\Carbon::parse($date)->format('d/m/Y') : '' }}.
            Sử dụng để double check với ứng dụng đặt hàng.
        </p>
    </div>
    
    @if($summary->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ảnh</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($summary as $index => $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $index + 1 }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                            @if($item['sample_image'] ?? $item['product_image'])
                            <img src="{{ $item['sample_image'] ?? $item['product_image'] }}" 
                                 alt="{{ $item['product_name'] }}" 
                                 class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <i class="fas fa-image text-xl"></i>
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $item['product_name'] }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 rounded-full">
                            {{ $item['size'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-4 py-2 text-lg font-bold bg-indigo-100 text-indigo-800 rounded-lg">
                            {{ $item['total_quantity'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-right font-semibold text-gray-900">
                        TỔNG CỘNG:
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-4 py-2 text-xl font-bold bg-green-100 text-green-800 rounded-lg">
                            {{ $totalItems }}
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Copy-friendly summary -->
    <div class="p-4 border-t bg-gray-50">
        <h3 class="font-medium text-gray-700 mb-2">
            <i class="fas fa-copy mr-1"></i>Danh sách copy nhanh:
        </h3>
        <div class="bg-white border rounded-lg p-4 text-sm font-mono whitespace-pre-wrap" id="copy-text">@foreach($summary as $item)
{{ $item['product_name'] }} - Size {{ $item['size'] }}: {{ $item['total_quantity'] }} cái
@endforeach
---
Tổng: {{ $totalItems }} sản phẩm</div>
        <button onclick="copyToClipboard()" 
                class="mt-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
            <i class="fas fa-copy mr-1"></i>Copy danh sách
        </button>
    </div>
    @else
    <div class="p-12 text-center text-gray-500">
        <i class="fas fa-box-open text-4xl mb-4 text-gray-300"></i>
        <p>Không có sản phẩm nào với bộ lọc hiện tại</p>
        <p class="text-sm mt-2">Thử thay đổi trạng thái hoặc ngày để xem kết quả</p>
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
</script>
@endpush
@endsection
