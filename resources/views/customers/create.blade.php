@extends('layouts.app')

@section('title', 'Thêm khách hàng')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="{{ route('customers.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Thêm khách hàng mới</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tên khách hàng <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror"
                       placeholder="Nhập tên khách hàng">
                @error('name')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="0xxx xxx xxx">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Link Facebook</label>
                <input type="text" name="facebook_link" value="{{ old('facebook_link') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="https://facebook.com/...">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Link Zalo</label>
                <input type="text" name="zalo_link" value="{{ old('zalo_link') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Link hoặc số Zalo">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                <textarea name="note" rows="3"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Ghi chú về khách hàng...">{{ old('note') }}</textarea>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('customers.index') }}" 
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
