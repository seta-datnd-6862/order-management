@extends('layouts.app')

@section('title', 'Đơn nhập kho')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
        <i class="fas fa-box-open mr-2 text-indigo-600"></i>Đơn nhập kho
    </h1>
    <a href="{{ route('inventory.imports.create') }}" 
       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>Tạo đơn nhập
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <input type="text" name="import_code" value="{{ request('import_code') }}" 
               placeholder="Mã đơn nhập..."
               class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        
        <select name="supplier" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tất cả nhà cung cấp --</option>
            @foreach($suppliers as $key => $label)
            <option value="{{ $key }}" {{ request('supplier') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        
        <select name="product_id" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tất cả sản phẩm --</option>
            @foreach($products as $product)
            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                {{ $product->name }}
            </option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}"
                placeholder="Từ ngày"
                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
                placeholder="Đến ngày"
                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
        
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-filter mr-1"></i>
            </button>
            <a href="{{ route('inventory.imports.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Import List -->
<div class="space-y-4">
    @forelse($imports as $import)
    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
        <div class="p-4">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-lg">{{ $import->import_code }}</span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            {{ $import->supplier_label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-calendar mr-1"></i>{{ $import->import_date->format('d/m/Y') }}
                    </p>
                    @if($import->note)
                    <p class="text-sm text-gray-600 mt-1">
                        <i class="fas fa-note-sticky mr-1"></i>{{ $import->note }}
                    </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="font-bold text-lg text-indigo-600">
                        {{ number_format($import->total_quantity) }} SP
                    </p>
                    <p class="text-sm text-gray-500">{{ $import->total_products }} loại</p>
                </div>
            </div>
            
            <!-- Import Items Preview -->
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($import->items->take(4) as $item)
                <div class="flex items-center bg-gray-50 rounded-lg p-2 text-sm">
                    @if($item->product->image_url)
                    <img src="{{ $item->product->image_url }}" class="w-10 h-10 object-cover rounded mr-2">
                    @endif
                    <div>
                        <p class="font-medium">{{ Str::limit($item->product->name, 20) }}</p>
                        <p class="text-xs text-gray-500">{{ $item->size }} × {{ $item->quantity }}</p>
                    </div>
                </div>
                @endforeach
                @if($import->items->count() > 4)
                <div class="flex items-center text-gray-500 text-sm">
                    +{{ $import->items->count() - 4 }} mặt hàng khác
                </div>
                @endif
            </div>
            
            <!-- Actions -->
            <div class="mt-4 pt-4 border-t flex justify-end space-x-2">
                <a href="{{ route('inventory.imports.show', $import) }}" 
                   class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-eye"></i> Xem
                </a>
                <a href="{{ route('inventory.imports.edit', $import) }}" 
                   class="px-3 py-1 text-sm text-indigo-600 hover:text-indigo-900">
                    <i class="fas fa-edit"></i> Sửa
                </a>
                <form action="{{ route('inventory.imports.destroy', $import) }}" method="POST" class="inline"
                      onsubmit="return confirm('Bạn có chắc muốn xóa đơn nhập này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1 text-sm text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
        <i class="fas fa-box-open text-4xl mb-4 text-gray-300"></i>
        <p>Chưa có đơn nhập kho nào</p>
        <a href="{{ route('inventory.imports.create') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
            Tạo đơn nhập đầu tiên →
        </a>
    </div>
    @endforelse
</div>

@if($imports->hasPages())
<div class="mt-6">
    {{ $imports->appends(request()->query())->links() }}
</div>
@endif

@endsection
