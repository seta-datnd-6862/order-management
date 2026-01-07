@extends('layouts.app')

@section('title', 'Đơn Viettel Post')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('orders.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Đơn Viettel Post</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('viettel-posts.import-form') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-file-import mr-1"></i>Import từ mã vận chuyển
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('viettel-posts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tất cả</option>
                    @foreach(\App\Models\ViettelOrder::getStatuses() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Mã vận đơn, tên, SĐT..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-search mr-1"></i>Lọc
                </button>
                <a href="{{ route('viettel-posts.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-redo mr-1"></i>Đặt lại
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã vận đơn</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn hàng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người nhận</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SĐT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dịch vụ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thu hộ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($viettelOrders as $viettelOrder)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-indigo-600">
                            {{ $viettelOrder->tracking_number }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $viettelOrder->created_at->format('d/m/Y H:i') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('orders.show', $viettelOrder->order) }}" 
                           class="text-sm text-indigo-600 hover:text-indigo-900">
                            #{{ $viettelOrder->order->id }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $viettelOrder->receiver_name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $viettelOrder->receiver_phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $viettelOrder->service_name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $viettelOrder->formatted_money_collection }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $viettelOrder->status_color }}">
                            {{ $viettelOrder->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('viettel-posts.show', $viettelOrder) }}" 
                           class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4 block"></i>
                        <p>Chưa có đơn Viettel Post nào</p>
                        <a href="{{ route('viettel-posts.import-form') }}" 
                           class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
                            Import đơn đầu tiên →
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($viettelOrders->hasPages())
    <div class="mt-6">
        {{ $viettelOrders->links() }}
    </div>
    @endif
</div>
@endsection
