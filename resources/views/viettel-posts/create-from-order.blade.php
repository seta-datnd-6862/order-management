@extends('layouts.app')

@section('title', 'Tạo đơn Viettel Post')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('orders.show', $order) }}" class="mr-4 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tạo đơn Viettel Post</h1>
            <p class="text-sm text-gray-600 mt-1">Cho đơn hàng #{{ $order->id }} - {{ $order->customer->name }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <form action="{{ route('viettel-posts.store-from-order', $order) }}" method="POST" id="viettel-form">
                    @csrf

                    <!-- Receiver Info -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-user mr-2 text-indigo-600"></i>Thông tin người nhận
                    </h3>

                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tên người nhận <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="receiver_name" 
                                   value="{{ old('receiver_name', $order->customer->name) }}" 
                                   required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @error('receiver_name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Số điện thoại <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="receiver_phone" 
                                   value="{{ old('receiver_phone', $order->customer->phone) }}" 
                                   required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @error('receiver_phone')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Địa chỉ nhận hàng <span class="text-red-500">*</span>
                            </label>
                            <textarea name="receiver_address" 
                                      rows="3" 
                                      required 
                                      maxlength="500"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('receiver_address', $order->customer->address) }}</textarea>
                            @error('receiver_address')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Shipping Info -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pt-6 border-t">
                        <i class="fas fa-box mr-2 text-orange-600"></i>Thông tin vận chuyển
                    </h3>

                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Trọng lượng (gram) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="product_weight" 
                                   id="product-weight"
                                   value="{{ old('product_weight', 1000) }}" 
                                   min="1" 
                                   required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @error('product_weight')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Ước tính trọng lượng hàng hóa</p>
                        </div>

                        <!-- Services Section - Initially Hidden -->
                        <div id="services-section" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Chọn dịch vụ vận chuyển <span class="text-red-500">*</span>
                            </label>
                            
                            <!-- Services will be loaded here -->
                            <div id="services-list" class="space-y-2">
                                <!-- Dynamic service cards -->
                            </div>
                            
                            <input type="hidden" name="service_code" id="selected-service-code" required>
                        </div>

                        <!-- Loading State -->
                        <div id="services-loading" class="hidden">
                            <div class="flex items-center justify-center py-8">
                                <i class="fas fa-spinner fa-spin text-2xl text-indigo-600 mr-3"></i>
                                <span class="text-gray-600">Đang tải dịch vụ...</span>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div id="services-error" class="hidden">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <p class="text-red-600 text-sm">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <span id="services-error-message"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Money Collection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tiền thu hộ (VNĐ) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="money_collection" 
                                   id="money-collection"
                                   value="{{ old('money_collection', $order->remaining_amount) }}" 
                                   min="0" 
                                   step="1000"
                                   required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @error('money_collection')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Số tiền hàng cần thu hộ từ người nhận</p>
                        </div>

                        <!-- Payment By (Who pays shipping) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Người trả phí vận chuyển <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" 
                                           name="payment_by" 
                                           value="sender" 
                                           {{ old('payment_by', 'sender') === 'sender' ? 'checked' : '' }}
                                           class="mr-3 text-indigo-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Người gửi trả</span>
                                        <p class="text-xs text-gray-500">Shop trả phí vận chuyển</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" 
                                           name="payment_by" 
                                           value="receiver"
                                           {{ old('payment_by') === 'receiver' ? 'checked' : '' }}
                                           class="mr-3 text-indigo-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Người nhận trả</span>
                                        <p class="text-xs text-gray-500">Thu hộ thêm phí vận chuyển từ người nhận</p>
                                    </div>
                                </label>
                            </div>
                            @error('payment_by')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea name="note" 
                                      rows="2" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                      placeholder="Ghi chú cho đơn vận chuyển...">{{ old('note', 'Cho xem hàng') }}</textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-6 border-t">
                        <button type="submit" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-check-circle mr-2"></i>Tạo đơn Viettel Post
                        </button>
                        <a href="{{ route('orders.show', $order) }}" 
                           class="px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin đơn hàng</h3>
                
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Mã đơn:</span>
                        <span class="font-medium">#{{ $order->id }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Khách hàng:</span>
                        <span class="font-medium">{{ $order->customer->name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tổng tiền:</span>
                        <span class="font-semibold text-indigo-600">{{ number_format($order->total_amount) }}đ</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Còn phải thu:</span>
                        <span class="font-semibold text-orange-600">{{ number_format($order->remaining_amount) }}đ</span>
                    </div>
                </div>

                <!-- Shipping Summary -->
                <div id="shipping-summary" class="border-t pt-4 hidden">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Tổng cước</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tiền thu hộ:</span>
                            <span class="font-medium" id="summary-collection">0đ</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phí ship:</span>
                            <span class="font-medium" id="summary-shipping">0đ</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t font-semibold">
                            <span>Tổng thu từ khách:</span>
                            <span class="text-green-600" id="summary-total">0đ</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span id="payment-note">Shop trả phí vận chuyển</span>
                    </p>
                </div>

                <div class="border-t pt-4 mt-4">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Bạn có thể điều chỉnh số tiền thu hộ trước khi tạo đơn
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let availableServices = [];
    let selectedServiceCode = null;
    
    /**
     * Load all services with prices
     */
    function loadServices() {
        const receiverAddress = $('textarea[name="receiver_address"]').val();
        const productWeight = $('#product-weight').val();
        const productPrice = {{ $order->total_amount }};
        const moneyCollection = $('#money-collection').val();
        
        // Validate input
        if (!receiverAddress || !productWeight) {
            return;
        }
        
        // Show loading
        $('#services-section').addClass('hidden');
        $('#services-error').addClass('hidden');
        $('#services-loading').removeClass('hidden');
        
        // Call API
        $.ajax({
            url: '{{ route("viettel-posts.get-services") }}',
            method: 'POST',
            data: {
                receiver_address: receiverAddress,
                product_weight: productWeight,
                product_price: productPrice,
                money_collection: moneyCollection || 0,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#services-loading').addClass('hidden');
                
                if (response.success && response.data.length > 0) {
                    availableServices = response.data;
                    renderServices(response.data);
                    $('#services-section').removeClass('hidden');
                } else {
                    showServicesError(response.message || 'Không có dịch vụ phù hợp');
                }
            },
            error: function(xhr) {
                $('#services-loading').addClass('hidden');
                showServicesError('Lỗi kết nối. Vui lòng thử lại.');
            }
        });
    }

    /**
     * Render service cards (SORTED by price - lowest first)
     */
    function renderServices(services) {
        // SORT services by price (lowest first) - GIÁ RẺ NHẤT LÊN ĐẦU
        const sortedServices = [...services].sort((a, b) => a.MONEY_TOTAL - b.MONEY_TOTAL);
        
        const html = sortedServices.map((service, index) => {
            const isRecommended = index === 0; // First (cheapest) = recommended
            
            return `
                <div class="service-card border-2 rounded-lg p-4 cursor-pointer transition-all hover:border-indigo-500 ${isRecommended ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200'}"
                     data-service-code="${service.SERVICE_CODE}"
                     data-money-total="${service.MONEY_TOTAL}"
                     data-kpi-ht="${service.KPI_HT}">
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1">
                            <!-- Icon -->
                            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center mr-4">
                                ${getServiceIcon(service.SERVICE_CODE)}
                            </div>
                            
                            <!-- Info -->
                            <div class="flex-1">
                                <div class="flex items-center mb-1">
                                    <h4 class="font-semibold text-gray-900">
                                        ${service.SERVICE_NAME}
                                    </h4>
                                    ${isRecommended ? '<span class="ml-2 px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded">Đề xuất</span>' : ''}
                                </div>
                                <p class="text-sm text-gray-600">
                                    <i class="far fa-clock mr-1"></i>
                                    Dự kiến giao sau ${service.KPI_HT} giờ
                                </p>
                            </div>
                        </div>
                        
                        <!-- Price -->
                        <div class="text-right ml-4">
                            <div class="text-2xl font-bold text-gray-900">
                                ${formatCurrency(service.MONEY_TOTAL)}
                            </div>
                            <div class="text-xs text-gray-500">
                                ${service.SERVICE_CODE}
                            </div>
                        </div>
                        
                        <!-- Radio -->
                        <div class="ml-4">
                            <input type="radio" 
                                   name="service_radio" 
                                   value="${service.SERVICE_CODE}"
                                   class="w-5 h-5 text-indigo-600"
                                   ${isRecommended ? 'checked' : ''}>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        $('#services-list').html(html);
        
        // Add click handlers
        $('.service-card').on('click', function() {
            const serviceCode = $(this).data('service-code');
            selectService(serviceCode);
        });
        
        // Auto-select first (cheapest) service
        if (sortedServices.length > 0) {
            selectService(sortedServices[0].SERVICE_CODE);
        }
    }

    /**
     * Select service and UPDATE SUMMARY
     */
    function selectService(serviceCode) {
        selectedServiceCode = serviceCode;
        $('#selected-service-code').val(serviceCode);
        
        // Update UI
        $('.service-card').removeClass('border-indigo-500 bg-indigo-50').addClass('border-gray-200');
        $(`.service-card[data-service-code="${serviceCode}"]`)
            .removeClass('border-gray-200')
            .addClass('border-indigo-500 bg-indigo-50');
        
        // Update radio
        $(`input[value="${serviceCode}"]`).prop('checked', true);
        
        // Update summary with selected service
        updateSummary(serviceCode);
    }

    /**
     * Update summary with selected service
     */
    function updateSummary(serviceCode) {
        const service = availableServices.find(s => s.SERVICE_CODE === serviceCode);
        if (!service) return;
        
        const moneyCollection = parseFloat($('#money-collection').val()) || 0;
        const paymentBy = $('input[name="payment_by"]:checked').val();
        
        $('#summary-collection').text(formatCurrency(moneyCollection));
        $('#summary-shipping').text(formatCurrency(service.MONEY_TOTAL));
        
        if (paymentBy === 'receiver') {
            // Người nhận trả phí ship → Tổng = Thu hộ + Phí ship
            $('#summary-total').text(formatCurrency(moneyCollection + service.MONEY_TOTAL));
            $('#payment-note').text('Người nhận trả phí vận chuyển');
        } else {
            // Người gửi trả phí ship → Tổng = Chỉ thu hộ
            $('#summary-total').text(formatCurrency(moneyCollection));
            $('#payment-note').text('Shop trả phí vận chuyển');
        }
        
        $('#shipping-summary').removeClass('hidden');
    }

    /**
     * Get service icon - UPDATED with new codes
     */
    function getServiceIcon(serviceCode) {
        const icons = {
            'SHT': '<i class="fas fa-rocket text-orange-500 text-xl"></i>',      // Hỏa tốc
            'SCN': '<i class="fas fa-shipping-fast text-blue-500 text-xl"></i>', // Nhanh
            'STK': '<i class="fas fa-box text-green-500 text-xl"></i>',          // Tiêu chuẩn
            'VMCH': '<i class="fas fa-undo text-purple-500 text-xl"></i>',       // Miễn cước hoàn
        };
        return icons[serviceCode] || '<i class="fas fa-truck text-gray-500 text-xl"></i>';
    }

    /**
     * Show error
     */
    function showServicesError(message) {
        $('#services-error-message').text(message);
        $('#services-error').removeClass('hidden');
    }

    /**
     * Format currency
     */
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
    }
    
    /**
     * Trigger load services when input changes
     */
    $('#product-weight, textarea[name="receiver_address"]').on('blur', function() {
        loadServices();
    });

    /**
     * Update summary when money collection changes
     */
    $('#money-collection').on('input', function() {
        if (selectedServiceCode) {
            updateSummary(selectedServiceCode);
        }
    });

    /**
     * Update summary when payment_by changes
     */
    $('input[name="payment_by"]').on('change', function() {
        if (selectedServiceCode) {
            updateSummary(selectedServiceCode);
        }
    });

    // Auto-load services on page load
    loadServices();
});
</script>
@endpush
