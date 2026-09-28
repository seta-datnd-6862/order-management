@extends('layouts.app')

@section('title', 'Thêm hàng loạt')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <div class="mb-4 sm:mb-0">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-layer-group mr-2 text-indigo-600"></i>Thêm hàng loạt đơn hàng
        </h1>
        <p class="text-sm text-gray-500 mt-1">Từ take note → AI bóc tách ra JSON → import vào đây → tạo từng đơn</p>
    </div>
    <a href="{{ route('campaigns.create') }}"
       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>Đợt nhập mới
    </a>
</div>

@if($campaigns->isEmpty())
<div class="bg-white rounded-lg shadow p-10 text-center">
    <i class="fas fa-layer-group text-5xl text-gray-300 mb-4"></i>
    <p class="text-gray-600 mb-1">Chưa có đợt nhập nào</p>
    <p class="text-sm text-gray-400 mb-5">Tạo đợt nhập đầu tiên để thêm nhiều đơn hàng cùng lúc từ take note</p>
    <a href="{{ route('campaigns.create') }}"
       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
        <i class="fas fa-plus mr-2"></i>Tạo đợt nhập
    </a>
</div>
@else
<div class="space-y-4">
    @foreach($campaigns as $campaign)
    @php
        $total = $campaign->total_orders;
        $done = $campaign->created_orders + $campaign->skipped_orders;
        $remaining = $total - $done;
        $percent = $total > 0 ? (int) round($done / $total * 100) : 0;
    @endphp
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('campaigns.show', $campaign) }}"
                       class="text-lg font-semibold text-gray-800 hover:text-indigo-600 truncate">
                        {{ $campaign->name }}
                    </a>
                    @if($campaign->status === \App\Models\OrderCampaign::STATUS_COMPLETED)
                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i>Hoàn thành
                    </span>
                    @else
                    <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800">
                        Đang xử lý
                    </span>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    <i class="far fa-clock mr-1"></i>{{ $campaign->created_at->format('d/m/Y H:i') }}
                </p>

                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>{{ $done }}/{{ $total }} đơn đã xử lý</span>
                        <span class="font-medium">{{ $percent }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $percent }}%"></div>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-3 text-sm">
                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>{{ $campaign->created_orders }} đã tạo</span>
                    <span class="text-gray-500"><i class="fas fa-forward mr-1"></i>{{ $campaign->skipped_orders }} bỏ qua</span>
                    <span class="text-yellow-600"><i class="fas fa-hourglass-half mr-1"></i>{{ $remaining }} còn lại</span>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if($remaining > 0)
                <a href="{{ route('campaigns.start', $campaign) }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm whitespace-nowrap">
                    <i class="fas fa-play mr-1"></i>Tiếp tục
                </a>
                @endif
                <a href="{{ route('campaigns.show', $campaign) }}"
                   class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50 text-sm whitespace-nowrap">
                    <i class="fas fa-list mr-1"></i>Chi tiết
                </a>
                <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST"
                      onsubmit="return confirm('Xoá đợt nhập này? Các đơn hàng đã tạo vẫn được giữ lại.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-2 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 text-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-6">
    {{ $campaigns->links() }}
</div>
@endif
@endsection
