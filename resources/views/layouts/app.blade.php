<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý đơn hàng')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('orders.index') }}" class="text-xl font-bold text-indigo-600">
                        <i class="fas fa-store mr-2"></i>QL Đơn Hàng
                    </a>
                    <div class="hidden md:flex space-x-4">
                        <a href="{{ route('orders.index') }}" 
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('orders.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-shopping-cart mr-1"></i> Đơn hàng
                        </a>
                        <a href="{{ route('summary.index') }}" 
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('summary.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-chart-bar mr-1"></i> Tổng hợp
                        </a>
                        <a href="{{ route('customers.index') }}" 
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('customers.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-users mr-1"></i> Khách hàng
                        </a>
                        <a href="{{ route('products.index') }}" 
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('products.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-box mr-1"></i> Sản phẩm
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="md:hidden border-t">
            <div class="flex justify-around py-2">
                <a href="{{ route('orders.index') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('orders.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span class="text-xs mt-1">Đơn hàng</span>
                </a>
                <a href="{{ route('summary.index') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('summary.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-chart-bar text-lg"></i>
                    <span class="text-xs mt-1">Tổng hợp</span>
                </a>
                <a href="{{ route('customers.index') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('customers.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-users text-lg"></i>
                    <span class="text-xs mt-1">Khách hàng</span>
                </a>
                <a href="{{ route('products.index') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('products.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-box text-lg"></i>
                    <span class="text-xs mt-1">Sản phẩm</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="fixed top-20 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed top-20 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
