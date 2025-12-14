@extends('layouts.app')

@section('title', 'Thêm sản phẩm')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="{{ route('products.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Thêm sản phẩm mới</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tên sản phẩm <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror"
                       placeholder="VD: Áo thun trắng">
                @error('name')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4" x-data="{ preview: null }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh sản phẩm</label>
                <div class="flex items-center space-x-4">
                    <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                        <template x-if="preview">
                            <img :src="preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <i class="fas fa-image text-2xl text-gray-400"></i>
                        </template>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" accept="image/*"
                               @change="preview = URL.createObjectURL($event.target.files[0])"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG, GIF, WEBP. Tối đa 5MB</p>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Giá mặc định</label>
                <input type="number" name="default_price" value="{{ old('default_price', 0) }}" min="0"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="0">
                <p class="mt-1 text-xs text-gray-500">Giá này sẽ được điền tự động khi chọn sản phẩm trong đơn hàng</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                <textarea name="note" rows="3"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Ghi chú về sản phẩm...">{{ old('note') }}</textarea>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('products.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Hủy
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Lưu
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
