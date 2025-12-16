@extends('layouts.app')

@section('title', 'Thống kê kho')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
        <i class="fas fa-warehouse mr-2 text-indigo-600"></i>Thống kê kho
    </h1>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <input type="text" name="search" value="{{ request('search') }}" 
               placeholder="Tìm sản phẩm..."
               class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        
        <select name="product_id" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tất cả sản phẩm --</option>
            @foreach($allProducts as $product)
            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                {{ $product->name }}
            </option>
            @endforeach
        </select>
        
        <div class="flex gap-2 lg:col-span-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
            <a href="{{ route('inventory.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full mr-4">
                <i class="fas fa-boxes text-2xl text-blue-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Tổng số lượng nhập</p>
                <p class="text-2xl font-bold text-blue-600">
                    {{ number_format(collect($inventory)->sum('total_imported')) }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full mr-4">
                <i class="fas fa-shopping-cart text-2xl text-green-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Tổng đã bán</p>
                <p class="text-2xl font-bold text-green-600">
                    {{ number_format(collect($inventory)->sum('total_sold')) }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-indigo-100 rounded-full mr-4">
                <i class="fas fa-warehouse text-2xl text-indigo-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Tồn kho hiện tại</p>
                <p class="text-2xl font-bold text-indigo-600">
                    {{ number_format(collect($inventory)->sum('total_stock')) }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Inventory List -->
<div class="space-y-4">
    @forelse($inventory as $item)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6" x-data="{ showDetails: false }">
            <!-- Product Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    @if($item['product']->image_url)
                    <img src="{{ $item['product']->image_url }}" 
                         class="w-20 h-20 object-cover rounded">
                    @else
                    <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                        <i class="fas fa-image text-gray-400 text-2xl"></i>
                    </div>
                    @endif
                    
                    <div>
                        <h3 class="text-lg font-bold">{{ $item['product']->name }}</h3>
                        <p class="text-sm text-gray-500">{{ count($item['sizes']) }} size khác nhau</p>
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Đã nhập</p>
                            <p class="text-lg font-bold text-blue-600">{{ number_format($item['total_imported']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Đã bán</p>
                            <p class="text-lg font-bold text-green-600">{{ number_format($item['total_sold']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Tồn kho</p>
                            <p class="text-lg font-bold text-indigo-600">{{ number_format($item['total_stock']) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toggle Details Button -->
            <div class="mt-4 pt-4 border-t flex justify-between items-center">
                <button @click="showDetails = !showDetails" 
                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                    <span x-show="!showDetails">
                        <i class="fas fa-chevron-down mr-1"></i>Xem chi tiết theo size
                    </span>
                    <span x-show="showDetails">
                        <i class="fas fa-chevron-up mr-1"></i>Ẩn chi tiết
                    </span>
                </button>
                
                <a href="{{ route('inventory.detail', $item['product']) }}" 
                   class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-history mr-1"></i>Xem lịch sử chi tiết
                </a>
            </div>

            <!-- Size Details (collapsible) -->
            <div x-show="showDetails" 
                 x-collapse
                 class="mt-4 border-t pt-4">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($item['sizes'] as $size => $data)
                    <div class="bg-gray-50 rounded-lg p-3 border">
                        <div class="text-center mb-2">
                            <span class="px-3 py-1 bg-gray-200 rounded-full text-sm font-bold">{{ $size }}</span>
                        </div>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nhập:</span>
                                <span class="font-medium text-blue-600">{{ $data['imported'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Bán:</span>
                                <span class="font-medium text-green-600">{{ $data['sold'] }}</span>
                            </div>
                            <div class="flex justify-between pt-1 border-t">
                                <span class="text-gray-600">Tồn:</span>
                                <span class="font-bold text-indigo-600">{{ $data['stock'] }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
        <i class="fas fa-warehouse text-4xl mb-4 text-gray-300"></i>
        <p>Chưa có dữ liệu kho</p>
        <a href="{{ route('inventory.imports.create') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
            Tạo đơn nhập kho →
        </a>
    </div>
    @endforelse
</div>

@endsection
