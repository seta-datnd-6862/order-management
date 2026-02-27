@extends('layouts.app')

@section('title', 'Import sản phẩm')

@section('content')
<div class="max-w-2xl mx-auto" x-data="importForm()">
    <div class="flex items-center mb-6">
        <a href="{{ route('products.index') }}" class="text-gray-500 hover:text-gray-700 mr-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-file-import mr-2 text-indigo-600"></i>Import sản phẩm
        </h1>
    </div>

    {{-- Hướng dẫn --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="font-semibold text-blue-800 mb-2">
            <i class="fas fa-info-circle mr-1"></i>Hướng dẫn
        </h3>
        <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
            <li>Tải file mẫu Excel để xem đúng định dạng cột</li>
            <li>Cột <strong>Mã sản phẩm</strong>: để trống = thêm mới, điền mã = cập nhật sản phẩm đó</li>
            <li>Cột <strong>Ảnh (URL)</strong>: điền đường link ảnh công khai (https://...)</li>
            <li>File tối đa 10MB, định dạng .xlsx hoặc .xls</li>
        </ul>
    </div>

    {{-- Tải mẫu + Form --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        {{-- Tải file mẫu --}}
        <div class="flex items-center justify-between py-3 border-b">
            <div>
                <p class="font-medium text-gray-700">File mẫu Excel</p>
                <p class="text-sm text-gray-500">Tải về để xem cấu trúc cột chuẩn</p>
            </div>
            <a href="{{ route('products.export') }}" 
               class="inline-flex items-center px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 transition text-sm">
                <i class="fas fa-download mr-2"></i>Xuất dữ liệu hiện tại
            </a>
        </div>

        {{-- Upload form --}}
        <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition"
                 :class="fileName ? 'border-indigo-400 bg-indigo-50' : 'hover:border-gray-400'"
                 @dragover.prevent @drop.prevent="handleDrop($event)">

                <input type="file" name="file" id="file-input" accept=".xlsx,.xls"
                       class="hidden" @change="handleFile($event)">

                <template x-if="!fileName">
                    <div>
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-600 mb-2">Kéo thả file vào đây hoặc</p>
                        <label for="file-input"
                               class="cursor-pointer inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                            <i class="fas fa-folder-open mr-2"></i>Chọn file
                        </label>
                        <p class="text-xs text-gray-400 mt-3">.xlsx, .xls — tối đa 10MB</p>
                    </div>
                </template>

                <template x-if="fileName">
                    <div>
                        <i class="fas fa-file-excel text-4xl text-green-500 mb-3"></i>
                        <p class="font-medium text-gray-800" x-text="fileName"></p>
                        <p class="text-sm text-gray-500 mt-1" x-text="fileSize"></p>
                        <button type="button" @click="clearFile()"
                                class="mt-3 text-sm text-red-500 hover:text-red-700">
                            <i class="fas fa-times mr-1"></i>Xóa file
                        </button>
                    </div>
                </template>
            </div>

            @error('file')
            <p class="mt-2 text-sm text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
            @enderror

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('products.index') }}"
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Hủy
                </a>
                <button type="submit" :disabled="!fileName"
                        :class="fileName ? 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer' : 'bg-gray-300 cursor-not-allowed'"
                        class="inline-flex items-center px-6 py-2 text-white rounded-lg transition">
                    <i class="fas fa-upload mr-2"></i>Bắt đầu import
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function importForm() {
    return {
        fileName: null,
        fileSize: null,
        handleFile(event) {
            const file = event.target.files[0];
            if (file) this.setFile(file);
        },
        handleDrop(event) {
            const file = event.dataTransfer.files[0];
            if (file) {
                // Gán vào input
                const input = document.getElementById('file-input');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                this.setFile(file);
            }
        },
        setFile(file) {
            this.fileName = file.name;
            const mb = (file.size / 1024 / 1024).toFixed(2);
            this.fileSize = `${mb} MB`;
        },
        clearFile() {
            this.fileName = null;
            this.fileSize = null;
            document.getElementById('file-input').value = '';
        }
    }
}
</script>
@endpush
