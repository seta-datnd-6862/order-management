@extends('layouts.app')

@section('title', 'Đợt nhập mới')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="{{ route('campaigns.index') }}" class="text-gray-500 hover:text-gray-700 mr-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-layer-group mr-2 text-indigo-600"></i>Đợt nhập mới
        </h1>
    </div>

    {{-- Bước 1: lấy prompt --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-5 border-b">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-gray-800">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white text-xs mr-2">1</span>
                        Copy prompt gửi cho AI
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 ml-8">
                        Dán prompt này vào ChatGPT / Claude, thay <code class="bg-gray-100 px-1 rounded">&lt;&lt;&lt; DÁN TAKE NOTE VÀO ĐÂY &gt;&gt;&gt;</code>
                        bằng take note, rồi copy JSON nhận được xuống bước 2.
                    </p>
                </div>
                <button type="button" id="copyPromptBtn"
                        class="shrink-0 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                    <i class="fas fa-copy mr-2"></i><span id="copyPromptLabel">Copy prompt</span>
                </button>
            </div>
        </div>

        <div x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="w-full px-5 py-3 text-sm text-left text-indigo-600 hover:bg-gray-50 flex items-center justify-between">
                <span><i class="fas fa-eye mr-2"></i>Xem nội dung prompt</span>
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
            <div x-show="open" x-cloak class="px-5 pb-5">
                <pre class="bg-gray-50 border rounded-lg p-4 text-xs text-gray-700 overflow-x-auto whitespace-pre-wrap max-h-96 overflow-y-auto">{{ $prompt }}</pre>
            </div>
        </div>
    </div>

    {{-- Bước 2: dán JSON --}}
    <form action="{{ route('campaigns.store') }}" method="POST" id="campaignForm">
        @csrf

        <div class="bg-white rounded-lg shadow p-5 space-y-5">
            <div>
                <h2 class="font-semibold text-gray-800">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white text-xs mr-2">2</span>
                    Dán JSON do AI trả về
                </h2>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tên đợt nhập</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       placeholder="Để trống sẽ lấy tên trong JSON (vd: Đơn 7/9 - 12/9/26)"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    JSON <span class="text-red-500">*</span>
                </label>
                <textarea name="raw_json" id="rawJson" rows="12" required
                          placeholder='{"version": 1, "name": "...", "orders": [...]}'
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono text-xs">{{ old('raw_json') }}</textarea>
                @error('raw_json')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-500">Dán cả khối JSON. Nếu AI có bọc trong dấu ``` thì hệ thống tự bỏ.</p>
            </div>

            <div x-data="{ open: false }">
                <button type="button" @click="open = !open" class="text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fas fa-sticky-note mr-1"></i>
                    <span x-text="open ? 'Ẩn take note gốc' : 'Lưu kèm take note gốc (không bắt buộc)'"></span>
                </button>
                <div x-show="open" x-cloak class="mt-2">
                    <textarea name="source_note" rows="6"
                              placeholder="Dán take note gốc vào đây để sau này đối chiếu..."
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('source_note') }}</textarea>
                </div>
            </div>

            {{-- Kết quả kiểm tra --}}
            <div id="previewBox" class="hidden rounded-lg border p-4"></div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2 border-t">
                <button type="button" id="checkJsonBtn"
                        class="px-5 py-2.5 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50">
                    <i class="fas fa-vial mr-2"></i>Kiểm tra JSON
                </button>
                <button type="submit"
                        class="flex-1 px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                    <i class="fas fa-file-import mr-2"></i>Tạo đợt nhập
                </button>
                <a href="{{ route('campaigns.index') }}"
                   class="px-5 py-2.5 border rounded-lg text-gray-700 hover:bg-gray-50 text-center">Hủy</a>
            </div>
        </div>
    </form>

    <textarea id="promptSource" class="hidden" aria-hidden="true">{{ $prompt }}</textarea>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#copyPromptBtn').on('click', async function () {
        const text = document.getElementById('promptSource').value;

        try {
            await navigator.clipboard.writeText(text);
        } catch (e) {
            // Fallback cho trình duyệt / http không cho dùng clipboard API
            const el = document.getElementById('promptSource');
            el.classList.remove('hidden');
            el.select();
            document.execCommand('copy');
            el.classList.add('hidden');
        }

        $('#copyPromptLabel').text('Đã copy!');
        setTimeout(() => $('#copyPromptLabel').text('Copy prompt'), 2000);
    });

    $('#checkJsonBtn').on('click', function () {
        const raw = $('#rawJson').val().trim();
        const $box = $('#previewBox');

        if (!raw) {
            showBox('error', 'Chưa dán JSON.', '');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang kiểm tra...');

        $.ajax({
            url: '{{ route('campaigns.preview') }}',
            method: 'POST',
            data: { raw_json: raw, _token: '{{ csrf_token() }}' },
        }).done(function (res) {
            let body = '<div class="text-sm space-y-1">';
            body += '<p><strong>' + res.order_count + '</strong> đơn · <strong>' + res.item_count + '</strong> dòng sản phẩm</p>';
            if (res.name) {
                body += '<p class="text-gray-600">Tên đợt: ' + escapeHtml(res.name) + '</p>';
            }
            if (res.warnings && res.warnings.length) {
                body += '<p class="mt-2 font-medium text-amber-700">' + res.warnings.length + ' điểm cần kiểm tra:</p>';
                body += '<ul class="list-disc list-inside text-amber-700 max-h-48 overflow-y-auto">';
                res.warnings.forEach(w => body += '<li>' + escapeHtml(w) + '</li>');
                body += '</ul>';
            }
            body += '</div>';
            showBox('success', 'JSON hợp lệ, có thể tạo đợt nhập.', body);
        }).fail(function (xhr) {
            const msg = xhr.responseJSON?.message
                || xhr.responseJSON?.errors?.raw_json?.[0]
                || 'Không kiểm tra được JSON.';
            showBox('error', msg, '');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-vial mr-2"></i>Kiểm tra JSON');
        });
    });

    function showBox(type, title, body) {
        const cls = type === 'success'
            ? 'border-green-300 bg-green-50 text-green-800'
            : 'border-red-300 bg-red-50 text-red-800';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        $('#previewBox')
            .removeClass('hidden border-green-300 bg-green-50 text-green-800 border-red-300 bg-red-50 text-red-800')
            .addClass(cls)
            .html('<p class="font-medium"><i class="fas ' + icon + ' mr-2"></i>' + escapeHtml(title) + '</p>' + body);
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }
});
</script>
@endpush
@endsection
