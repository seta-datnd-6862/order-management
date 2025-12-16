@extends('layouts.app')

@section('title', 'Chi tiết kho - ' . $product->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại thống kê kho
    </a>
</div>

<!-- Product Header -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center space-x-4">
        @if($product->image_url)
        <img src="{{ $product->image_url }}" class="w-24 h-24 object-cover rounded">
        @else
        <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
            <i class="fas fa-image text-gray-400 text-3xl"></i>
        </div>
        @endif
        
        <div>
            <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
            @if($product->note)
            <p class="text-gray-600 mt-1">{{ $product->note }}</p>
            @endif
            <p class="text-sm text-gray-500 mt-2">
                Giá mặc định: <span class="font-bold text-indigo-600">{{ number_format($product->default_price) }}đ</span>
            </p>
        </div>
    </div>
</div>

<!-- Size Tabs -->
<div class="space-y-6">
    @forelse($details as $size => $data)
    <div class="bg-white rounded-lg shadow" x-data="{ showImports: true, showSales: true }">
        <!-- Size Header -->
        <div class="p-6 border-b">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">
                        Size: <span class="px-4 py-2 bg-gray-200 rounded-lg">{{ $size }}</span>
                    </h2>
                </div>
                <div class="grid grid-cols-3 gap-6 text-center">
                    <div>
                        <p class="text-sm text-gray-500">Tổng nhập</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($data['total_imported']) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tổng bán</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($data['total_sold']) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tồn kho</p>
                        <p class="text-2xl font-bold text-indigo-600">{{ number_format($data['stock']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Imports Section -->
            <div>
                <button @click="showImports = !showImports" 
                        class="w-full flex items-center justify-between py-3 px-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <span class="font-semibold text-blue-800">
                        <i class="fas fa-box-open mr-2"></i>Lịch sử nhập hàng ({{ $data['imports']->count() }} đợt)
                    </span>
                    <i class="fas" :class="showImports ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>

                <div x-show="showImports" x-collapse class="mt-3 space-y-2">
                    @forelse($data['imports'] as $import)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                        <div>
                            <a href="{{ route('inventory.imports.show', $import->inventoryImport) }}" 
                               class="font-medium text-indigo-600 hover:text-indigo-800">
                                {{ $import->inventoryImport->import_code }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-calendar mr-1"></i>
                                {{ $import->inventoryImport->import_date->format('d/m/Y') }}
                                - 
                                <i class="fas fa-truck mr-1"></i>
                                {{ $import->inventoryImport->supplier_label }}
                            </p>
                            @if($import->note)
                            <p class="text-xs text-gray-600 mt-1">
                                <i class="fas fa-note-sticky mr-1"></i>{{ $import->note }}
                            </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-blue-600">+{{ $import->quantity }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 text-center py-4">Chưa có lịch sử nhập</p>
                    @endforelse
                </div>
            </div>

            <!-- Sales Section -->
            <div>
                <button @click="showSales = !showSales" 
                        class="w-full flex items-center justify-between py-3 px-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <span class="font-semibold text-green-800">
                        <i class="fas fa-shopping-cart mr-2"></i>Lịch sử bán hàng ({{ $data['sales']->count() }} đơn)
                    </span>
                    <i class="fas" :class="showSales ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>

                <div x-show="showSales" x-collapse class="mt-3 space-y-2">
                    @forelse($data['sales'] as $sale)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                        <div>
                            <a href="{{ route('orders.show', $sale->order) }}" 
                               class="font-medium text-indigo-600 hover:text-indigo-800">
                                #{{ $sale->order->id }} - {{ $sale->order->customer->name }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-calendar mr-1"></i>
                                {{ $sale->order->created_at->format('d/m/Y H:i') }}
                                - 
                                <span class="px-2 py-0.5 rounded-full {{ $sale->order->status_color }}">
                                    {{ $sale->order->status_label }}
                                </span>
                            </p>
                            @if($sale->note)
                            <p class="text-xs text-gray-600 mt-1">
                                <i class="fas fa-note-sticky mr-1"></i>{{ $sale->note }}
                            </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-green-600">-{{ $sale->quantity }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($sale->price) }}đ</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 text-center py-4">Chưa có đơn hàng</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
        <p>Chưa có dữ liệu cho sản phẩm này</p>
    </div>
    @endforelse
</div>

@endsection
