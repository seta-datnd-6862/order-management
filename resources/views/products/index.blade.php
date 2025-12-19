@extends('layouts.app')

@section('title', 'Sản phẩm')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
        <i class="fas fa-box mr-2 text-indigo-600"></i>Sản phẩm
    </h1>
    <a href="{{ route('products.create') }}" 
       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>Thêm sản phẩm
    </a>
</div>

<!-- Search -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" class="flex gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Tìm theo tên sản phẩm..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        {{-- Search by product code --}}
        <div class="flex-1">
            <input type="text" name="product_code" value="{{ request('product_code') }}" 
                   placeholder="Tìm theo mã sản phẩm..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

<!-- Product Grid -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @forelse($products as $product)
    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
        <div class="aspect-square bg-gray-100 relative">
            @if($product->image)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" 
                 class="w-full object-cover">
            @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <i class="fas fa-image text-4xl"></i>
            </div>
            @endif
        </div>
        <div class="p-4">
            <h3 class="font-medium text-gray-900 truncate">{{ $product->name }}</h3>
            <p class="text-xs text-gray-400 mt-1" >{{ $product->product_code }}
                <button onclick="copyToClipboard('{{ $product->product_code }}')" 
                        class="text-gray-400 hover:text-indigo-600 transition flex-shrink-0"
                        title="Copy mã sản phẩm">
                    <i class="fas fa-copy text-sm"></i>
                </button>
            </p>
            <p class="text-sm text-gray-500 mt-1">
                {{ number_format($product->default_price) }}đ
            </p>
            <div class="flex justify-end space-x-2 mt-3">
                <a href="{{ route('products.edit', $product) }}" 
                   class="p-2 text-indigo-600 hover:bg-indigo-50 rounded">
                    <i class="fas fa-edit"></i>
                </a>
                <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline"
                      onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-gray-500">
        <i class="fas fa-box text-4xl mb-4 text-gray-300"></i>
        <p>Chưa có sản phẩm nào</p>
    </div>
    @endforelse
</div>

@if($products->hasPages())
<div class="mt-6">
    {{ $products->appends(request()->query())->links() }}
</div>
@endif
@endsection
