@extends('layouts.app')

@section('title', 'Import đơn Viettel Post')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('viettel-posts.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Import đơn Viettel Post</h1>
            <p class="text-sm text-gray-600 mt-1">Nhập mã vận chuyển có sẵn để quản lý trong hệ thống</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('viettel-posts.import') }}" method="POST">
            @csrf

            <!-- Select Order -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Chọn đơn hàng <span class="text-red-500">*</span>
                </label>
                <select name="order_id" id="order-select" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Chọn đơn hàng --</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" 
                                data-shipping-code="{{ $order->shipping_code }}"
                                {{ old('order_id') == $order->id ? 'selected' : '' }}>
                            #{{ $order->id }} - {{ $order->customer->name }} - {{ number_format($order->total_amount) }}đ - ({{ $order->shipping_code }})
                        </option>
                    @endforeach
                </select>
                @error('order_id')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-info-circle"></i> Chỉ hiển thị đơn đang shipping và có mã vận chuyển
                </p>
            </div>

            <!-- Tracking Number -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Mã vận chuyển Viettel Post <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="tracking_number" 
                       id="tracking-number-input"
                       value="{{ old('tracking_number') }}" 
                       required 
                       placeholder="Sẽ tự động điền khi chọn đơn hàng"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                @error('tracking_number')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-info-circle"></i> Mã vận đơn sẽ tự động lấy từ đơn hàng đã chọn
                </p>
            </div>

            <!-- Note -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                <textarea name="note" 
                          rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                          placeholder="Ghi chú về đơn hàng này...">{{ old('note') }}</textarea>
            </div>

            <!-- Info Box -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-1"></i> Lưu ý:
                </h3>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                    <li>Chỉ hiển thị đơn hàng có trạng thái "Đang ship" và đã có mã vận chuyển</li>
                    <li>Mã vận chuyển sẽ tự động điền từ thông tin đơn hàng</li>
                    <li>Hệ thống sẽ tự động pull thông tin từ Viettel Post (nếu API hỗ trợ)</li>
                    <li>Mã vận đơn phải là duy nhất trong hệ thống</li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-file-import mr-2"></i>Import đơn
                </button>
                <a href="{{ route('viettel-posts.index') }}" 
                   class="px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-fill tracking number when order is selected
    $('#order-select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var shippingCode = selectedOption.data('shipping-code');
        
        if (shippingCode) {
            $('#tracking-number-input').val(shippingCode);
            
            // Optional: Show a brief success indicator
            $('#tracking-number-input').addClass('border-green-500');
            setTimeout(function() {
                $('#tracking-number-input').removeClass('border-green-500');
            }, 1000);
        } else {
            $('#tracking-number-input').val('');
        }
    });
    
    // Trigger change event on page load if there's an old value
    @if(old('order_id'))
        $('#order-select').trigger('change');
    @endif
});
</script>
@endpush
