@extends('layouts.app')

@section('title', 'Thùng rác - Sản phẩm')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
        <i class="fas fa-trash mr-2 text-red-500"></i>Thùng rác - Sản phẩm
    </h1>
    <a href="{{ route('products.index') }}"
       class="inline-flex items-center px-4 py-2 border border-gray-400 text-gray-600 rounded-lg hover:bg-gray-50 transition text-sm">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
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
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

<!-- Product Grid -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @forelse($products as $product)
    <div class="bg-white rounded-lg shadow overflow-hidden opacity-75">
        <div class="aspect-square bg-gray-100 relative">
            @if($product->image)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                 class="w-full object-cover grayscale">
            @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
                <i class="fas fa-image text-4xl"></i>
            </div>
            @endif
            <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                Đã xóa
            </div>
        </div>
        <div class="p-4">
            <h3 class="font-medium text-gray-500 truncate line-through">{{ $product->name }}</h3>
            <p class="text-xs text-gray-400 mt-1">{{ $product->product_code }}</p>
            <p class="text-sm text-gray-400 mt-1">
                {{ number_format($product->default_price) }}đ
            </p>
            <p class="text-xs text-gray-400 mt-1">
                <i class="fas fa-clock mr-1"></i>Xóa lúc: {{ $product->deleted_at->format('d/m/Y H:i') }}
            </p>
            <div class="flex justify-end space-x-2 mt-3">
                <!-- Khôi phục -->
                <form action="{{ route('products.restore', $product->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="p-2 text-green-600 hover:bg-green-50 rounded"
                            title="Khôi phục">
                        <i class="fas fa-undo"></i>
                    </button>
                </form>
                <!-- Xóa vĩnh viễn -->
                <form action="{{ route('products.forceDelete', $product->id) }}" method="POST" class="inline"
                      onsubmit="return confirm('Xóa vĩnh viễn sản phẩm này? Hành động này không thể hoàn tác!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="p-2 text-red-600 hover:bg-red-50 rounded"
                            title="Xóa vĩnh viễn">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-gray-500">
        <i class="fas fa-trash text-4xl mb-4 text-gray-300"></i>
        <p>Thùng rác trống</p>
    </div>
    @endforelse
</div>

@if($products->hasPages())
<div class="mt-6">
    {{ $products->appends(request()->query())->links() }}
</div>
@endif
@endsection
