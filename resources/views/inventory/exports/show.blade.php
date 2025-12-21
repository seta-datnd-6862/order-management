@extends('layouts.app')

@section('title', 'Chi tiết đơn xuất - ' . $export->export_code)

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.exports.index') }}" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Export Info -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">{{ $export->export_code }}</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-calendar mr-1"></i>{{ $export->export_date->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-sm font-medium rounded-full {{ $export->reason_color }}">
                            {{ $export->reason_label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <!-- Reason -->
                <div>
                    <label class="text-sm font-medium text-gray-500">Lý do xuất kho</label>
                    <p class="mt-1 text-gray-900">{{ $export->reason_label }}</p>
                </div>

                <!-- Export Date -->
                <div>
                    <label class="text-sm font-medium text-gray-500">Ngày xuất kho</label>
                    <p class="mt-1 text-gray-900">{{ $export->export_date->format('d/m/Y') }}</p>
                </div>

                @if($export->note)
                <!-- Note -->
                <div>
                    <label class="text-sm font-medium text-gray-500">Ghi chú</label>
                    <p class="mt-1 text-gray-900">{{ $export->note }}</p>
                </div>
                @endif

                <!-- Timestamps -->
                <div class="pt-4 border-t">
                    <p class="text-xs text-gray-500">
                        Tạo lúc: {{ $export->created_at->format('d/m/Y H:i') }}
                    </p>
                    @if($export->updated_at != $export->created_at)
                    <p class="text-xs text-gray-500">
                        Cập nhật: {{ $export->updated_at->format('d/m/Y H:i') }}
                    </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Export Items -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">Danh sách sản phẩm xuất</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $export->total_products }} sản phẩm - Tổng {{ number_format($export->total_quantity) }} đơn vị</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($export->items as $index => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($item->product->image_url)
                                    <img src="{{ $item->product->image_url }}" 
                                         alt="{{ $item->product->name }}"
                                         class="w-10 h-10 rounded object-cover mr-3">
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $item->product->sku }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 rounded">{{ $item->size }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-bold text-red-600">-{{ number_format($item->quantity) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $item->note ?? '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-sm font-bold text-gray-900 text-right">
                                Tổng cộng:
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-lg font-bold text-red-600">-{{ number_format($export->total_quantity) }}</span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow sticky top-24">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">Thao tác</h3>
            </div>
            
            <div class="p-6 space-y-3">
                <a href="{{ route('inventory.exports.edit', $export) }}" 
                   class="block w-full px-4 py-2 bg-indigo-600 text-white text-center rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-edit mr-2"></i>Sửa đơn xuất
                </a>

                <form action="{{ route('inventory.exports.destroy', $export) }}" 
                      method="POST" 
                      onsubmit="return confirm('Bạn có chắc muốn xóa đơn xuất này? Hành động này không thể hoàn tác!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="block w-full px-4 py-2 bg-red-600 text-white text-center rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-2"></i>Xóa đơn xuất
                    </button>
                </form>

                <a href="{{ route('inventory.exports.index') }}" 
                   class="block w-full px-4 py-2 border border-gray-300 text-gray-700 text-center rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-list mr-2"></i>Danh sách đơn xuất
                </a>
            </div>

            <!-- Summary -->
            <div class="p-6 border-t bg-red-50">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Tóm tắt</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tổng sản phẩm:</span>
                        <span class="font-semibold">{{ $export->total_products }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tổng số lượng xuất:</span>
                        <span class="font-bold text-red-600">-{{ number_format($export->total_quantity) }}</span>
                    </div>
                    <div class="flex justify-between text-sm pt-2 border-t border-red-200">
                        <span class="text-gray-600">Lý do:</span>
                        <span class="font-semibold">{{ $export->reason_label }}</span>
                    </div>
                </div>
            </div>

            <!-- Warning -->
            <div class="p-6 border-t">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Lưu ý:</strong> Đơn xuất này đã trừ {{ number_format($export->total_quantity) }} sản phẩm khỏi kho.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
