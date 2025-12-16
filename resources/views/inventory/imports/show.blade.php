@extends('layouts.app')

@section('title', 'Chi tiết đơn nhập - ' . $import->import_code)

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.imports.index') }}" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Import Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-box-open mr-2 text-indigo-600"></i>{{ $import->import_code }}
                </h2>
                <div class="flex space-x-2">
                    <a href="{{ route('inventory.imports.edit', $import) }}" 
                       class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-edit mr-1"></i>Sửa
                    </a>
                    <form action="{{ route('inventory.imports.destroy', $import) }}" method="POST" class="inline"
                          onsubmit="return confirm('Bạn có chắc muốn xóa đơn nhập này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-1"></i>Xóa
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nhà cung cấp</p>
                    <p class="font-medium">
                        <span class="px-2 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                            {{ $import->supplier_label }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ngày nhập</p>
                    <p class="font-medium">{{ $import->import_date->format('d/m/Y') }}</p>
                </div>
                @if($import->note)
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Ghi chú</p>
                    <p class="font-medium">{{ $import->note }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Items List -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Danh sách sản phẩm</h3>
            
            <div class="space-y-4">
                @foreach($import->items as $item)
                <div class="flex items-center bg-gray-50 rounded-lg p-4">
                    @if($item->product->image_url)
                    <img src="{{ $item->product->image_url }}" 
                         class="w-16 h-16 object-cover rounded mr-4">
                    @else
                    <div class="w-16 h-16 bg-gray-200 rounded mr-4 flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>
                    @endif
                    
                    <div class="flex-1">
                        <h4 class="font-medium">{{ $item->product->name }}</h4>
                        <div class="flex items-center space-x-4 mt-1 text-sm text-gray-600">
                            <span><i class="fas fa-ruler mr-1"></i>Size: {{ $item->size }}</span>
                            <span><i class="fas fa-cubes mr-1"></i>SL: {{ $item->quantity }}</span>
                        </div>
                        @if($item->note)
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-note-sticky mr-1"></i>{{ $item->note }}
                        </p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Summary Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-24">
            <h3 class="text-lg font-semibold mb-4">Thống kê</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng loại SP:</span>
                    <span class="font-bold">{{ $import->items->count() }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Tổng số lượng:</span>
                    <span class="font-bold text-indigo-600">{{ number_format($import->total_quantity) }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Ngày tạo:</span>
                    <span class="text-sm">{{ $import->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <button onclick="copyImportCode()" 
                        class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fas fa-copy mr-2"></i>Copy mã đơn
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyImportCode() {
    const code = '{{ $import->import_code }}';
    navigator.clipboard.writeText(code).then(() => {
        alert('Đã copy mã đơn: ' + code);
    });
}
</script>
@endpush

@endsection
