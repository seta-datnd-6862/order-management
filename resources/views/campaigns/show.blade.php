@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
@php
    $total = $campaign->total_count;
    $created = $campaign->created_count;
    $skipped = $campaign->skipped_count;
    $pending = $campaign->pending_count;
    $percent = $campaign->progress_percent;
@endphp

<div class="flex items-start mb-6">
    <a href="{{ route('campaigns.index') }}" class="text-gray-500 hover:text-gray-700 mr-3 mt-1">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="flex-1 min-w-0">
        <h1 class="text-2xl font-bold text-gray-800 truncate">{{ $campaign->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            Tạo lúc {{ $campaign->created_at->format('d/m/Y H:i') }}
        </p>
    </div>
</div>

{{-- Tiến độ --}}
<div class="bg-white rounded-lg shadow p-5 mb-6">
    <div class="flex items-center justify-between mb-2">
        <h2 class="font-semibold text-gray-800"><i class="fas fa-tasks mr-2 text-indigo-600"></i>Tiến độ</h2>
        <span class="text-lg font-bold text-indigo-600">{{ $percent }}%</span>
    </div>

    <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
        <div class="bg-indigo-600 h-3 rounded-full transition-all" style="width: {{ $percent }}%"></div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-2xl font-bold text-gray-800">{{ $total }}</p>
            <p class="text-xs text-gray-500">Tổng đơn</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3">
            <p class="text-2xl font-bold text-green-700">{{ $created }}</p>
            <p class="text-xs text-green-600">Đã tạo</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-3">
            <p class="text-2xl font-bold text-yellow-700">{{ $pending }}</p>
            <p class="text-xs text-yellow-600">Còn chờ</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-2xl font-bold text-gray-500">{{ $skipped }}</p>
            <p class="text-xs text-gray-500">Bỏ qua</p>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 mt-5 pt-4 border-t">
        @if($nextPending)
        <a href="{{ route('campaigns.process', [$campaign, $nextPending]) }}"
           class="flex-1 px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-center font-semibold">
            <i class="fas fa-play mr-2"></i>Tạo đơn tiếp theo (#{{ $nextPending->sequence }} · {{ $nextPending->customer_name_raw }})
        </a>
        @else
        <div class="flex-1 px-5 py-2.5 bg-green-50 text-green-700 rounded-lg text-center font-medium">
            <i class="fas fa-check-circle mr-2"></i>Đã xử lý xong tất cả đơn
        </div>
        @endif

        <form action="{{ route('campaigns.rematch', $campaign) }}" method="POST">
            @csrf
            <button type="submit" class="w-full px-4 py-2.5 border rounded-lg text-gray-700 hover:bg-gray-50"
                    title="Tìm lại khách hàng / sản phẩm cho các đơn còn chờ">
                <i class="fas fa-sync-alt mr-2"></i>Khớp lại
            </button>
        </form>
    </div>
</div>

{{-- Cảnh báo khi import --}}
@if($campaign->import_warnings)
<div class="bg-amber-50 border border-amber-200 rounded-lg mb-6" x-data="{ open: false }">
    <button type="button" @click="open = !open" class="w-full p-4 text-left flex items-center justify-between">
        <span class="font-medium text-amber-800">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ count($campaign->import_warnings) }} điểm cần kiểm tra khi bóc tách
        </span>
        <i class="fas text-amber-700" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
    </button>
    <div x-show="open" x-cloak class="px-4 pb-4">
        <ul class="text-sm text-amber-800 space-y-1 list-disc list-inside max-h-64 overflow-y-auto">
            @foreach($campaign->import_warnings as $warning)
            <li>{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- Lọc --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tất cả trạng thái --</option>
            @foreach($statuses as $key => $label)
            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="marked" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Dấu 👌 / ✅ trong note --</option>
            <option value="done" {{ request('marked') === 'done' ? 'selected' : '' }}>Đã đánh dấu xong</option>
            <option value="undone" {{ request('marked') === 'undone' ? 'selected' : '' }}>Chưa đánh dấu</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            <i class="fas fa-filter mr-1"></i>Lọc
        </button>
        <a href="{{ route('campaigns.show', $campaign) }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50 text-center">
            <i class="fas fa-times"></i>
        </a>
    </form>
</div>

{{-- Danh sách đơn nháp --}}
<div class="space-y-3">
    @forelse($campaignOrders as $campaignOrder)
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="text-xs font-mono text-gray-400">#{{ $campaignOrder->sequence }}</span>
                    <span class="font-semibold text-gray-800">{{ $campaignOrder->customer_name_raw }}</span>

                    @if($campaignOrder->customer)
                    <span class="text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded">
                        <i class="fas fa-user-check mr-1"></i>{{ $campaignOrder->customer->name }}
                    </span>
                    @else
                    <span class="text-xs text-red-700 bg-red-50 px-2 py-0.5 rounded">
                        <i class="fas fa-user-slash mr-1"></i>chưa khớp khách
                    </span>
                    @endif

                    <span class="px-2 py-0.5 text-xs rounded-full {{ $campaignOrder->status_color }}">
                        {{ $campaignOrder->status_label }}
                    </span>

                    @if($campaignOrder->is_marked_done)
                    <span class="text-xs text-gray-500" title="Note đã có dấu 👌/✅">👌</span>
                    @endif
                </div>

                <p class="text-xs text-gray-500 mb-2">
                    @if($campaignOrder->order_date)
                    <i class="far fa-calendar mr-1"></i>{{ $campaignOrder->order_date->format('d/m/Y') }}
                    @else
                    <i class="far fa-calendar mr-1"></i><span class="text-amber-600">chưa có ngày</span>
                    @endif
                    @if($campaignOrder->customer_note_raw)
                    · {{ $campaignOrder->customer_note_raw }}
                    @endif
                </p>

                <ul class="text-sm text-gray-700 space-y-0.5">
                    @foreach($campaignOrder->items as $item)
                    <li class="flex items-start gap-1">
                        <span class="text-gray-400">•</span>
                        <span>
                            {{ $item->quantity }}× {{ $item->product?->name ?? $item->product_name_raw }}
                            @if($item->size)
                            <span class="text-gray-500">· size {{ $item->size }}</span>
                            @else
                            <span class="text-amber-600">· thiếu size{{ $item->size_raw ? ' (note ghi: '.$item->size_raw.')' : '' }}</span>
                            @endif
                            @if(!$item->product_id)
                            <span class="text-red-600">· chưa khớp SP</span>
                            @endif
                        </span>
                    </li>
                    @endforeach
                </ul>

                @if($campaignOrder->missing_labels && $campaignOrder->status === \App\Models\CampaignOrder::STATUS_PENDING)
                <p class="mt-2 text-xs text-amber-700 bg-amber-50 inline-block px-2 py-1 rounded">
                    <i class="fas fa-exclamation-circle mr-1"></i>Còn thiếu: {{ implode(', ', $campaignOrder->missing_labels) }}
                </p>
                @endif

                @if($campaignOrder->raw_line)
                <p class="mt-2 text-xs text-gray-400 italic break-words">{{ $campaignOrder->raw_line }}</p>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if($campaignOrder->status === \App\Models\CampaignOrder::STATUS_CREATED && $campaignOrder->order)
                <a href="{{ route('orders.show', $campaignOrder->order) }}"
                   class="px-4 py-2 border border-green-300 text-green-700 rounded-lg hover:bg-green-50 text-sm whitespace-nowrap">
                    <i class="fas fa-receipt mr-1"></i>Đơn #{{ $campaignOrder->order_id }}
                </a>
                @elseif($campaignOrder->status === \App\Models\CampaignOrder::STATUS_SKIPPED)
                <form action="{{ route('campaigns.orders.reopen', [$campaign, $campaignOrder]) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50 text-sm whitespace-nowrap">
                        <i class="fas fa-undo mr-1"></i>Mở lại
                    </button>
                </form>
                @else
                <a href="{{ route('campaigns.process', [$campaign, $campaignOrder]) }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm whitespace-nowrap">
                    <i class="fas fa-pen mr-1"></i>Xử lý
                </a>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        Không có đơn nào khớp bộ lọc.
    </div>
    @endforelse
</div>
@endsection
