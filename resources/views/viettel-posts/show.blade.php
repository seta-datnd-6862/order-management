@extends('layouts.app')

@section('title', 'Chi tiết đơn Viettel Post')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('viettel-posts.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Chi tiết đơn Viettel Post</h1>
                <p class="text-sm text-gray-600 mt-1">Mã vận đơn: 
                    <span class="font-mono font-semibold text-indigo-600">{{ $viettelOrder->tracking_number }}</span>
                    <button class="copy-btn ml-2 text-gray-400 hover:text-indigo-600" 
                            data-copy="{{ $viettelOrder->tracking_number }}"
                            title="Copy mã vận đơn">
                        <i class="fas fa-copy"></i>
                    </button>
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('orders.show', $viettelOrder->order) }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                <i class="fas fa-shopping-cart mr-1"></i>Xem đơn hàng
            </a>
            <form action="{{ route('viettel-posts.destroy', $viettelOrder) }}" 
                  method="POST" 
                  class="inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-1"></i>Xóa
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Status & Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Trạng thái vận chuyển</h2>
                    <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $viettelOrder->status_color }}">
                        <span class="status-text">{{ $viettelOrder->status_label }}</span>
                    </span>
                </div>

                <!-- Quick Status Update Buttons -->
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Models\ViettelOrder::getStatuses() as $statusKey => $statusLabel)
                    <button class="update-status-btn px-3 py-2 text-sm rounded-lg border transition
                                   {{ $viettelOrder->status === $statusKey ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                            data-status="{{ $statusKey }}"
                            data-url="{{ route('viettel-posts.update-status', $viettelOrder) }}">
                        {{ $statusLabel }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Receiver Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user mr-2 text-indigo-600"></i>Thông tin người nhận
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Tên người nhận:</span>
                        <span class="text-sm font-medium text-gray-900">{{ $viettelOrder->receiver_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Số điện thoại:</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">{{ $viettelOrder->receiver_phone }}</span>
                            <button class="copy-btn text-gray-400 hover:text-indigo-600" 
                                    data-copy="{{ $viettelOrder->receiver_phone }}"
                                    title="Copy số điện thoại">
                                <i class="fas fa-copy text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600 block mb-1">Địa chỉ:</span>
                        <div class="flex items-start gap-2">
                            <p class="text-sm text-gray-900 flex-1">{{ $viettelOrder->receiver_address }}</p>
                            <button class="copy-btn text-gray-400 hover:text-indigo-600 flex-shrink-0" 
                                    data-copy="{{ $viettelOrder->receiver_address }}"
                                    title="Copy địa chỉ">
                                <i class="fas fa-copy text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Link -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-shopping-cart mr-2 text-green-600"></i>Đơn hàng liên kết
                </h2>
                <a href="{{ route('orders.show', $viettelOrder->order) }}" 
                   class="block p-4 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Mã đơn hàng</p>
                            <p class="font-medium text-gray-900">#{{ $viettelOrder->order->id }}</p>
                        </div>
                        <i class="fas fa-external-link-alt text-gray-400"></i>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span class="text-gray-600">Khách hàng:</span>
                        <span class="font-medium">{{ $viettelOrder->order->customer->name }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-sm">
                        <span class="text-gray-600">Tổng tiền:</span>
                        <span class="font-semibold text-indigo-600">{{ number_format($viettelOrder->order->total_amount) }}đ</span>
                    </div>
                </a>
            </div>

            @if($viettelOrder->note)
            <!-- Note -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-sticky-note mr-2 text-yellow-600"></i>Ghi chú
                </h2>
                <p class="text-gray-700">{{ $viettelOrder->note }}</p>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin vận chuyển</h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Dịch vụ:</span>
                        <span class="font-medium text-right">{{ $viettelOrder->service_name }}</span>
                    </div>

                    @if($viettelOrder->product_weight)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Trọng lượng:</span>
                        <span class="font-medium">{{ $viettelOrder->product_weight }} gram</span>
                    </div>
                    @endif

                    @if($viettelOrder->estimated_delivery_time)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Thời gian dự kiến:</span>
                        <span class="font-medium">{{ $viettelOrder->formatted_delivery_time }}</span>
                    </div>
                    @endif

                    <div class="border-t pt-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Cước phí:</span>
                            <span class="font-medium">{{ $viettelOrder->formatted_shipping_fee }}</span>
                        </div>
                        
                        @if($viettelOrder->money_collection > 0)
                        <div class="flex justify-between pt-2 border-t">
                            <span class="text-sm text-gray-600">Tiền thu hộ:</span>
                            <span class="text-lg font-bold text-green-600">{{ $viettelOrder->formatted_money_collection }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="border-t pt-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Ngày tạo:</span>
                            <span class="font-medium">{{ $viettelOrder->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($viettelOrder->updated_at != $viettelOrder->created_at)
                        <div class="flex justify-between text-sm mt-2">
                            <span class="text-gray-600">Cập nhật:</span>
                            <span class="font-medium">{{ $viettelOrder->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification (hidden by default) -->
<div id="toast" class="fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50" style="display: none;">
    <i class="fas fa-check-circle mr-2"></i>
    <span id="toast-message"></span>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Copy to clipboard functionality
    $('.copy-btn').on('click', function() {
        const textToCopy = $(this).data('copy');
        
        // Create temporary textarea
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(textToCopy).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show toast
        showToast('Đã copy!');
    });

    // Update status functionality
    $('.update-status-btn').on('click', function() {
        const $btn = $(this);
        const status = $btn.data('status');
        const url = $btn.data('url');
        
        // Don't update if already current status
        if ($btn.hasClass('bg-indigo-600')) {
            return;
        }
        
        $.ajax({
            url: url,
            method: 'PATCH',
            data: {
                status: status,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Update all status buttons
                    $('.update-status-btn').removeClass('bg-indigo-600 text-white border-indigo-600')
                                          .addClass('bg-white text-gray-700 border-gray-300 hover:bg-gray-50');
                    
                    // Highlight current button
                    $btn.removeClass('bg-white text-gray-700 border-gray-300 hover:bg-gray-50')
                        .addClass('bg-indigo-600 text-white border-indigo-600');
                    
                    // Update status badge
                    $('.status-badge').attr('class', 'status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' + response.status_color);
                    $('.status-text').text(response.status_label);
                    
                    // Show toast
                    showToast(response.message);
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể cập nhật trạng thái'));
                }
            },
            error: function() {
                alert('Lỗi kết nối. Vui lòng thử lại');
            }
        });
    });

    // Delete form confirmation
    $('.delete-form').on('submit', function(e) {
        if (!confirm('Bạn có chắc chắn muốn xóa đơn Viettel Post này?')) {
            e.preventDefault();
        }
    });

    // Toast notification function
    function showToast(message) {
        const $toast = $('#toast');
        $('#toast-message').text(message);
        $toast.fadeIn(300);
        
        setTimeout(function() {
            $toast.fadeOut(300);
        }, 2000);
    }
});
</script>
@endpush
