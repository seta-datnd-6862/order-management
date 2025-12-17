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
    <link href="{{ asset('components/chosen.css') }}"
        rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        
        /* Image hover effect */
        .image-zoom-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .image-zoom-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: inherit;
        }
        
        .image-zoom-container:hover .image-zoom-overlay {
            opacity: 1;
        }
        
        .image-zoom-icon {
            color: white;
            font-size: 2rem;
            pointer-events: none;
        }
        
        /* Modal styles */
        .image-modal {
            backdrop-filter: blur(4px);
        }
        
        .image-modal-content {
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="imageViewer()">
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
                        
                        <!-- Inventory Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" 
                                    class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('inventory.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }} flex items-center">
                                <i class="fas fa-warehouse mr-1"></i> Kho hàng
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            
                            <div x-show="open" 
                                 @click.away="open = false"
                                 x-transition
                                 class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                                <a href="{{ route('inventory.index') }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-chart-line mr-2"></i>Thống kê kho
                                </a>
                                <a href="{{ route('inventory.imports.index') }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-box-open mr-2"></i>Đơn nhập kho
                                </a>
                            </div>
                        </div>
                        
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
            <div class="grid grid-cols-5 py-2">
                <a href="{{ route('orders.index') }}" class="flex flex-col items-center px-2 py-2 {{ request()->routeIs('orders.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span class="text-xs mt-1">Đơn hàng</span>
                </a>
                <a href="{{ route('summary.index') }}" class="flex flex-col items-center px-2 py-2 {{ request()->routeIs('summary.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-chart-bar text-lg"></i>
                    <span class="text-xs mt-1">Tổng hợp</span>
                </a>
                <a href="{{ route('inventory.index') }}" class="flex flex-col items-center px-2 py-2 {{ request()->routeIs('inventory.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-warehouse text-lg"></i>
                    <span class="text-xs mt-1">Kho</span>
                </a>
                <a href="{{ route('customers.index') }}" class="flex flex-col items-center px-2 py-2 {{ request()->routeIs('customers.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-users text-lg"></i>
                    <span class="text-xs mt-1">Khách</span>
                </a>
                <a href="{{ route('products.index') }}" class="flex flex-col items-center px-2 py-2 {{ request()->routeIs('products.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <i class="fas fa-box text-lg"></i>
                    <span class="text-xs mt-1">SP</span>
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

    <!-- Image Viewer Modal -->
    <div x-show="showModal" 
         x-cloak
         @click="closeModal()"
         @keydown.escape.window="closeModal()"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-75 image-modal"
         style="display: none;">
        <div class="relative max-w-7xl max-h-screen p-4">
            <!-- Close button -->
            <button @click="closeModal()" 
                    class="absolute top-6 right-6 z-10 bg-white rounded-full p-3 shadow-lg hover:bg-gray-100 transition">
                <i class="fas fa-times text-gray-700 text-xl"></i>
            </button>
            
            <!-- Image -->
            <img :src="currentImage" 
                 @click.stop
                 class="image-modal-content rounded-lg shadow-2xl"
                 alt="Xem ảnh phóng to">
            
            <!-- Image info (optional) -->
            <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 bg-white bg-opacity-90 px-4 py-2 rounded-full shadow-lg">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-search-plus mr-2"></i>Nhấn ESC hoặc click bên ngoài để đóng
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('components/chosen.js') }}"></script>

    <!-- Image Viewer Script -->
    <script>
        function imageViewer() {
            return {
                showModal: false,
                currentImage: '',
                observer: null,
                
                init() {
                    // Wait for DOM to be ready
                    this.$nextTick(() => {
                        this.wrapImages();
                        this.setupObserver();
                    });
                },
                
                setupObserver() {
                    // Create a MutationObserver to watch for new images
                    this.observer = new MutationObserver((mutations) => {
                        let shouldWrap = false;
                        
                        mutations.forEach((mutation) => {
                            // Check if new nodes were added
                            if (mutation.addedNodes.length > 0) {
                                mutation.addedNodes.forEach((node) => {
                                    // Check if the added node is an image
                                    if (node.nodeName === 'IMG') {
                                        shouldWrap = true;
                                    }
                                    // Check if the added node contains images
                                    if (node.querySelectorAll) {
                                        const imgs = node.querySelectorAll('img');
                                        if (imgs.length > 0) {
                                            shouldWrap = true;
                                        }
                                    }
                                });
                            }
                            
                            // Check if src attribute changed on existing images
                            if (mutation.type === 'attributes' && 
                                mutation.attributeName === 'src' && 
                                mutation.target.nodeName === 'IMG') {
                                shouldWrap = true;
                            }
                        });
                        
                        // Wrap images if needed
                        if (shouldWrap) {
                            // Small delay to ensure image is fully rendered
                            setTimeout(() => {
                                this.wrapImages();
                            }, 50);
                        }
                    });
                    
                    // Start observing the document body for changes
                    this.observer.observe(document.body, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['src']
                    });
                },
                
                wrapImages() {
                    // Find all img elements that aren't already wrapped
                    const images = document.querySelectorAll('img:not(.no-zoom)');
                    
                    images.forEach(img => {
                        // Skip if already wrapped
                        if (img.parentElement.classList.contains('image-zoom-container')) {
                            return;
                        }
                        
                        // Skip if image doesn't have src or src is empty
                        if (!img.src || img.src === '' || img.src === window.location.href) {
                            return;
                        }
                        
                        // Skip placeholder images
                        if (img.src.includes('placeholder') || img.src.includes('data:image')) {
                            return;
                        }
                        
                        // Create wrapper
                        const wrapper = document.createElement('div');
                        wrapper.className = 'image-zoom-container';
                        
                        // Copy existing classes from img to wrapper if needed for layout
                        const imgClasses = img.className.split(' ').filter(c => 
                            c.includes('w-') || c.includes('h-') || c.includes('rounded') || 
                            c.includes('object-') || c.includes('mx-') || c.includes('my-')
                        );
                        if (imgClasses.length > 0) {
                            wrapper.className += ' ' + imgClasses.join(' ');
                            // Remove copied classes from img
                            img.className = img.className.split(' ').filter(c => !imgClasses.includes(c)).join(' ');
                        }
                        
                        // Create overlay
                        const overlay = document.createElement('div');
                        overlay.className = 'image-zoom-overlay';
                        overlay.innerHTML = '<i class="fas fa-search-plus image-zoom-icon"></i>';
                        
                        // Wrap image
                        img.parentNode.insertBefore(wrapper, img);
                        wrapper.appendChild(img);
                        wrapper.appendChild(overlay);
                        
                        // Add click event
                        wrapper.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            this.openModal(img.src);
                        });
                    });
                },
                
                openModal(imageSrc) {
                    this.currentImage = imageSrc;
                    this.showModal = true;
                    document.body.style.overflow = 'hidden';
                },
                
                closeModal() {
                    this.showModal = false;
                    document.body.style.overflow = 'auto';
                    this.currentImage = '';
                },
                
                destroy() {
                    // Clean up observer when component is destroyed
                    if (this.observer) {
                        this.observer.disconnect();
                    }
                }
            }
        }
        
        // Re-initialize image wrapping when Alpine components are loaded
        document.addEventListener('alpine:initialized', () => {
            // Small delay to ensure all content is rendered
            setTimeout(() => {
                const viewer = Alpine.$data(document.body);
                if (viewer && viewer.wrapImages) {
                    viewer.wrapImages();
                }
            }, 100);
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Hiển thị thông báo thành công
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2';
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Đã copy!';
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
            }).catch(err => {
                console.error('Không thể copy:', err);
            });
        }

        $(document).ready(function() {
            $('.chosen-select').chosen({
                width: '100%',
                no_results_text: 'Không tìm thấy kết quả',
                placeholder_text_single: '-- Chọn --',
                allow_single_deselect: true
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
