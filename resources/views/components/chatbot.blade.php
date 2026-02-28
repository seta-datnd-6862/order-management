<div x-data="chatbot()" x-cloak>
    <!-- Floating Button -->
    <button @click="open = !open"
            class="fixed bottom-20 right-4 md:bottom-6 md:right-6 z-50 w-14 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all duration-200 active:scale-95">
        <i class="fas fa-comments text-xl" x-show="!open"></i>
        <i class="fas fa-times text-xl" x-show="open"></i>
    </button>

    <!-- Chat Panel -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed bottom-36 right-4 md:bottom-24 md:right-6 z-50 w-80 md:w-96 bg-white rounded-2xl shadow-2xl flex flex-col"
         style="height: 520px;">

        <!-- Header -->
        <div class="bg-indigo-600 text-white px-4 py-3 rounded-t-2xl flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot text-sm"></i>
                </div>
                <div>
                    <div class="font-semibold text-sm">Tạo nhanh</div>
                    <div class="text-xs text-indigo-200">Đơn hàng · Khách · Sản phẩm</div>
                </div>
            </div>
            <button @click="reset()" title="Bắt đầu lại" class="text-indigo-200 hover:text-white p-1">
                <i class="fas fa-redo-alt text-xs"></i>
            </button>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto px-3 py-3 space-y-2" id="chatbot-messages" x-ref="messages">
            <template x-for="(msg, idx) in messages" :key="idx">
                <div>
                    <!-- Bot message -->
                    <div x-show="msg.type === 'bot'" class="flex items-end gap-2 mb-1">
                        <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-robot text-indigo-500" style="font-size:10px"></i>
                        </div>
                        <div class="max-w-[75%]">
                            <div class="bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm px-3 py-2 text-sm" x-html="msg.text"></div>
                            <!-- Option chips -->
                            <div x-show="msg.options && msg.options.length && idx === messages.length - 1" class="mt-1.5 flex flex-wrap gap-1">
                                <template x-for="opt in (msg.options || [])" :key="opt.value">
                                    <button @click="handleOptionClick(opt)"
                                            :disabled="loading"
                                            class="px-2.5 py-1 text-xs bg-white border border-indigo-300 text-indigo-700 rounded-full hover:bg-indigo-50 active:bg-indigo-100 transition disabled:opacity-50">
                                        <span x-text="opt.label"></span>
                                    </button>
                                </template>
                            </div>
                            <!-- Size grid -->
                            <div x-show="msg.sizes && idx === messages.length - 1" class="mt-1.5">
                                <div class="grid grid-cols-5 gap-1">
                                    <template x-for="sz in (msg.sizes || [])" :key="sz">
                                        <button @click="selectSize(sz)"
                                                :disabled="loading"
                                                class="px-1 py-1 text-xs bg-white border border-gray-300 text-gray-700 rounded hover:bg-indigo-50 hover:border-indigo-400 active:bg-indigo-100 transition disabled:opacity-50 text-center"
                                                x-text="sz"></button>
                                    </template>
                                </div>
                            </div>
                            <!-- Search results -->
                            <div x-show="msg.searchType && idx === messages.length - 1 && searchResults.length > 0" class="mt-1.5 space-y-1">
                                <template x-for="result in searchResults" :key="result.id">
                                    <button @click="selectSearchResult(result)"
                                            class="w-full text-left px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 transition text-xs">
                                        <div class="font-medium text-gray-800" x-text="result.name"></div>
                                        <div class="text-gray-500" x-show="result.phone" x-text="result.phone"></div>
                                        <div class="text-gray-500" x-show="result.product_code" x-text="(result.product_code || '') + (result.default_price ? ' · ' + formatMoney(result.default_price) + 'đ' : '')"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- User message -->
                    <div x-show="msg.type === 'user'" class="flex justify-end mb-1">
                        <div class="max-w-[75%] bg-indigo-500 text-white rounded-2xl rounded-br-sm px-3 py-2 text-sm" x-html="msg.text"></div>
                    </div>
                </div>
            </template>

            <!-- Loading indicator -->
            <div x-show="loading" class="flex items-end gap-2">
                <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-indigo-500" style="font-size:10px"></i>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-bl-sm px-3 py-2">
                    <div class="flex gap-1">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0s"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:.15s"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:.3s"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-100 px-3 py-2 flex-shrink-0" x-show="needsInput()">
            <!-- Image preview -->
            <div x-show="pendingProduct.image_base64" class="mb-2">
                <div class="flex items-center gap-2 p-1.5 bg-gray-50 rounded-lg">
                    <img :src="pendingProduct.image_base64" class="w-10 h-10 object-cover rounded no-zoom">
                    <span class="text-xs text-gray-600 flex-1">Ảnh đã chọn</span>
                    <button @click="pendingProduct.image_base64 = null" class="text-gray-400 hover:text-red-500">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-2">
                <!-- Image input (hidden) -->
                <input type="file" x-ref="fileInput" accept="image/*" class="hidden" @change="handleImageSelect">

                <!-- Image button (only for product image step) -->
                <button x-show="step === 'order_new_prd_img' || step === 'product_image'"
                        @click="$refs.fileInput.click()"
                        class="flex-shrink-0 w-9 h-9 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg flex items-center justify-center transition">
                    <i class="fas fa-image text-sm"></i>
                </button>

                <!-- Text input -->
                <input x-show="inputType() === 'text'"
                       x-ref="textInput"
                       type="text"
                       x-model="inputValue"
                       @keydown.enter="handleTextSubmit()"
                       :placeholder="inputPlaceholder()"
                       class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-400">

                <!-- Number input -->
                <input x-show="inputType() === 'number'"
                       x-ref="numInput"
                       type="number"
                       x-model="inputValue"
                       @keydown.enter="handleTextSubmit()"
                       :placeholder="inputPlaceholder()"
                       min="0"
                       class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-400">

                <!-- Search input -->
                <input x-show="inputType() === 'search'"
                       x-ref="searchInput"
                       type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="doSearch()"
                       @keydown.enter="handleTextSubmit()"
                       :placeholder="inputPlaceholder()"
                       class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-400">

                <button @click="handleTextSubmit()"
                        :disabled="loading"
                        class="flex-shrink-0 w-9 h-9 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg flex items-center justify-center transition">
                    <i class="fas fa-paper-plane text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CHATBOT_SIZES = ['20','21','22','23','24','25','26','27','28','XS','S','M','L','XL','XXL','XXXL','66','73','80','90','100','110','120','130','140','150','160','170','180'];
const CHATBOT_CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function chatbot() {
    return {
        open: false,
        step: 'welcome',
        messages: [],
        searchQuery: '',
        searchResults: [],
        orderData: {
            customer: null,
            items: [],
            currentItem: {},
            deposit: 0,
            discount: 0,
            note: ''
        },
        pendingCustomer: {},
        pendingProduct: {},
        inputValue: '',
        loading: false,
        _returnStep: null, // step to return to after inline create

        init() {
            this.showWelcome();
        },

        showWelcome() {
            this.step = 'welcome';
            this.messages = [];
            this.searchResults = [];
            this.inputValue = '';
            this.searchQuery = '';
            this.sendBotMessage('Xin chào! Bạn muốn làm gì?', [
                { label: '📦 Tạo đơn hàng', value: 'start_order' },
                { label: '👤 Thêm khách hàng', value: 'start_customer' },
                { label: '🎁 Thêm sản phẩm', value: 'start_product' },
            ]);
        },

        sendBotMessage(text, options, extra) {
            this.messages.push({ type: 'bot', text, options: options || null, ...extra });
            this.$nextTick(() => this.scrollToBottom());
        },

        sendUserMessage(text) {
            this.messages.push({ type: 'user', text });
            this.$nextTick(() => this.scrollToBottom());
        },

        scrollToBottom() {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        },

        handleOptionClick(opt) {
            this.sendUserMessage(opt.label);
            this.searchResults = [];

            switch (opt.value) {
                // Welcome options
                case 'start_order':
                    this.orderData = { customer: null, items: [], currentItem: {}, deposit: 0, discount: 0, note: '' };
                    this.goToStep('order_customer');
                    break;
                case 'start_customer':
                    this.pendingCustomer = {};
                    this.goToStep('customer_name');
                    break;
                case 'start_product':
                    this.pendingProduct = {};
                    this.goToStep('product_name');
                    break;

                // Order: inline create customer
                case 'order_new_customer':
                    this.pendingCustomer = {};
                    this._returnStep = 'order_add_product';
                    this.goToStep('order_new_cus_name');
                    break;

                // Order: inline create product
                case 'order_new_product':
                    this.pendingProduct = {};
                    this._returnStep = 'order_product_size';
                    this.goToStep('order_new_prd_name');
                    break;

                // Order: skip optional fields
                case 'skip_phone':
                    this.pendingCustomer.phone = null;
                    if (this._returnStep) {
                        this.goToStep('order_new_cus_addr');
                    } else {
                        this.goToStep('customer_address');
                    }
                    break;
                case 'skip_address':
                    this.pendingCustomer.address = null;
                    if (this._returnStep) {
                        this.doCreateCustomerInline();
                    } else {
                        this.goToStep('customer_confirm');
                    }
                    break;
                case 'skip_price':
                    this.pendingProduct.default_price = null;
                    if (this._returnStep === 'order_product_size') {
                        this.goToStep('order_new_prd_img');
                    } else {
                        this.goToStep('product_image');
                    }
                    break;
                case 'skip_image':
                    this.pendingProduct.image_base64 = null;
                    if (this._returnStep === 'order_product_size') {
                        this.doCreateProductInline();
                    } else {
                        this.goToStep('product_confirm');
                    }
                    break;

                // Order more items
                case 'add_more_items':
                    this.orderData.currentItem = {};
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.goToStep('order_add_product');
                    break;
                case 'done_items':
                    this.goToStep('order_deposit');
                    break;

                // Order no deposit/discount
                case 'no_deposit':
                    this.orderData.deposit = 0;
                    this.goToStep('order_discount');
                    break;
                case 'no_discount':
                    this.orderData.discount = 0;
                    this.goToStep('order_note');
                    break;
                case 'skip_note':
                    this.orderData.note = '';
                    this.goToStep('order_confirm');
                    break;

                // Order confirm
                case 'confirm_order':
                    this.doCreateOrder();
                    break;
                case 'restart':
                    this.reset();
                    break;

                // Customer confirm
                case 'confirm_customer':
                    this.doCreateCustomer();
                    break;

                // Product confirm
                case 'confirm_product':
                    this.doCreateProduct();
                    break;

                // Quick qty
                case 'qty_1': case 'qty_2': case 'qty_3': case 'qty_5': case 'qty_10':
                    const qty = parseInt(opt.value.split('_')[1]);
                    this.orderData.currentItem.quantity = qty;
                    this.sendUserMessage(qty + ' cái');
                    this.goToStep('order_product_price');
                    break;

                // Use default price
                case 'use_default_price':
                    this.orderData.currentItem.price = this.orderData.currentItem.product.default_price || 0;
                    this.sendUserMessage(this.formatMoney(this.orderData.currentItem.price) + 'đ');
                    this.finalizeItem();
                    break;
            }
        },

        handleTextSubmit() {
            const val = this.inputType() === 'search' ? this.searchQuery.trim() : this.inputValue.trim();
            if (!val && !['order_new_prd_img', 'product_image'].includes(this.step)) return;

            switch (this.step) {
                // Order flow
                case 'order_customer':
                    // If search, just keep searching
                    break;
                case 'order_add_product':
                    break;

                // Inline customer create (within order)
                case 'order_new_cus_name':
                    if (!val) return;
                    this.pendingCustomer.name = val;
                    this.sendUserMessage(val);
                    this.inputValue = '';
                    this.goToStep('order_new_cus_phone');
                    break;
                case 'order_new_cus_phone':
                    this.pendingCustomer.phone = val || null;
                    this.sendUserMessage(val || 'Bỏ qua');
                    this.inputValue = '';
                    this.goToStep('order_new_cus_addr');
                    break;
                case 'order_new_cus_addr':
                    this.pendingCustomer.address = val || null;
                    this.sendUserMessage(val || 'Bỏ qua');
                    this.inputValue = '';
                    this.doCreateCustomerInline();
                    break;

                // Inline product create (within order)
                case 'order_new_prd_name':
                    if (!val) return;
                    this.pendingProduct.name = val;
                    this.sendUserMessage(val);
                    this.inputValue = '';
                    this.goToStep('order_new_prd_price');
                    break;
                case 'order_new_prd_price':
                    this.pendingProduct.default_price = val ? parseFloat(val) : null;
                    this.sendUserMessage(val ? this.formatMoney(val) + 'đ' : 'Bỏ qua');
                    this.inputValue = '';
                    this.goToStep('order_new_prd_img');
                    break;
                case 'order_new_prd_img':
                    // Only proceed from button clicks (skip or image select)
                    if (!this.pendingProduct.image_base64) {
                        this.pendingProduct.image_base64 = null;
                    }
                    this.sendUserMessage('Bỏ qua');
                    this.doCreateProductInline();
                    break;

                // Product qty
                case 'order_product_qty':
                    if (!val) return;
                    this.orderData.currentItem.quantity = parseInt(val);
                    this.sendUserMessage(val + ' cái');
                    this.inputValue = '';
                    this.goToStep('order_product_price');
                    break;

                // Product price
                case 'order_product_price':
                    if (!val) return;
                    this.orderData.currentItem.price = parseFloat(val);
                    this.sendUserMessage(this.formatMoney(val) + 'đ');
                    this.inputValue = '';
                    this.finalizeItem();
                    break;

                // Order deposit/discount/note
                case 'order_deposit':
                    this.orderData.deposit = val ? parseFloat(val) : 0;
                    this.sendUserMessage(val ? this.formatMoney(val) + 'đ' : '0đ');
                    this.inputValue = '';
                    this.goToStep('order_discount');
                    break;
                case 'order_discount':
                    this.orderData.discount = val ? parseFloat(val) : 0;
                    this.sendUserMessage(val ? this.formatMoney(val) + 'đ' : '0đ');
                    this.inputValue = '';
                    this.goToStep('order_note');
                    break;
                case 'order_note':
                    this.orderData.note = val || '';
                    this.sendUserMessage(val || 'Không có ghi chú');
                    this.inputValue = '';
                    this.goToStep('order_confirm');
                    break;

                // Standalone customer flow
                case 'customer_name':
                    if (!val) return;
                    this.pendingCustomer.name = val;
                    this.sendUserMessage(val);
                    this.inputValue = '';
                    this.goToStep('customer_phone');
                    break;
                case 'customer_phone':
                    this.pendingCustomer.phone = val || null;
                    this.sendUserMessage(val || 'Bỏ qua');
                    this.inputValue = '';
                    this.goToStep('customer_address');
                    break;
                case 'customer_address':
                    this.pendingCustomer.address = val || null;
                    this.sendUserMessage(val || 'Bỏ qua');
                    this.inputValue = '';
                    this.goToStep('customer_confirm');
                    break;

                // Standalone product flow
                case 'product_name':
                    if (!val) return;
                    this.pendingProduct.name = val;
                    this.sendUserMessage(val);
                    this.inputValue = '';
                    this.goToStep('product_price');
                    break;
                case 'product_price':
                    this.pendingProduct.default_price = val ? parseFloat(val) : null;
                    this.sendUserMessage(val ? this.formatMoney(val) + 'đ' : 'Bỏ qua');
                    this.inputValue = '';
                    this.goToStep('product_image');
                    break;
                case 'product_image':
                    // skip image
                    this.sendUserMessage('Bỏ qua');
                    this.pendingProduct.image_base64 = null;
                    this.goToStep('product_confirm');
                    break;
            }
        },

        goToStep(step) {
            this.step = step;
            this.searchResults = [];

            switch (step) {
                case 'order_customer':
                    this.searchQuery = '';
                    this.sendBotMessage('Tìm khách hàng (nhập tên hoặc SĐT):', [
                        { label: '+ Tạo khách mới', value: 'order_new_customer' },
                    ], { searchType: 'customer' });
                    this.$nextTick(() => this.$refs.searchInput && this.$refs.searchInput.focus());
                    break;

                case 'order_new_cus_name':
                    this.sendBotMessage('Tên khách hàng?');
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;
                case 'order_new_cus_phone':
                    this.sendBotMessage('Số điện thoại?', [
                        { label: 'Bỏ qua', value: 'skip_phone' }
                    ]);
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;
                case 'order_new_cus_addr':
                    this.sendBotMessage('Địa chỉ?', [
                        { label: 'Bỏ qua', value: 'skip_address' }
                    ]);
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;

                case 'order_add_product':
                    this.searchQuery = '';
                    this.sendBotMessage('Tìm sản phẩm (nhập tên):', [
                        { label: '+ Tạo sản phẩm mới', value: 'order_new_product' },
                    ], { searchType: 'product' });
                    this.$nextTick(() => this.$refs.searchInput && this.$refs.searchInput.focus());
                    break;

                case 'order_new_prd_name':
                    this.sendBotMessage('Tên sản phẩm?');
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;
                case 'order_new_prd_price':
                    this.sendBotMessage('Giá gốc (đ)?', [
                        { label: 'Bỏ qua', value: 'skip_price' }
                    ]);
                    this.$nextTick(() => this.$refs.numInput && this.$refs.numInput.focus());
                    break;
                case 'order_new_prd_img':
                    this.sendBotMessage('Ảnh sản phẩm?', [
                        { label: 'Bỏ qua', value: 'skip_image' }
                    ]);
                    break;

                case 'order_product_size':
                    this.sendBotMessage('Chọn size:', null, { sizes: CHATBOT_SIZES });
                    break;

                case 'order_product_qty':
                    this.sendBotMessage('Số lượng?', [
                        { label: '1', value: 'qty_1' },
                        { label: '2', value: 'qty_2' },
                        { label: '3', value: 'qty_3' },
                        { label: '5', value: 'qty_5' },
                        { label: '10', value: 'qty_10' },
                    ]);
                    this.$nextTick(() => this.$refs.numInput && this.$refs.numInput.focus());
                    break;

                case 'order_product_price': {
                    const defPrice = this.orderData.currentItem.product?.default_price;
                    const opts = defPrice
                        ? [{ label: 'Dùng giá gốc: ' + this.formatMoney(defPrice) + 'đ', value: 'use_default_price' }]
                        : [];
                    this.sendBotMessage('Giá bán (đ)?', opts);
                    this.$nextTick(() => this.$refs.numInput && this.$refs.numInput.focus());
                    break;
                }

                case 'order_more_items': {
                    const item = this.orderData.currentItem;
                    const summary = `✅ <strong>${item.product.name}</strong> · Size ${item.size} · ${item.quantity} cái · ${this.formatMoney(item.price)}đ`;
                    this.sendBotMessage(summary, [
                        { label: '+ Thêm sản phẩm khác', value: 'add_more_items' },
                        { label: '✓ Xong, tiếp tục', value: 'done_items' },
                    ]);
                    break;
                }

                case 'order_deposit':
                    this.sendBotMessage('Tiền cọc (đ)?', [
                        { label: 'Không cọc', value: 'no_deposit' }
                    ]);
                    this.$nextTick(() => this.$refs.numInput && this.$refs.numInput.focus());
                    break;
                case 'order_discount':
                    this.sendBotMessage('Giảm giá (đ)?', [
                        { label: 'Không giảm', value: 'no_discount' }
                    ]);
                    this.$nextTick(() => this.$refs.numInput && this.$refs.numInput.focus());
                    break;
                case 'order_note':
                    this.sendBotMessage('Ghi chú đơn hàng?', [
                        { label: 'Bỏ qua', value: 'skip_note' }
                    ]);
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;

                case 'order_confirm': {
                    const total = this.orderTotal();
                    const remain = total - this.orderData.deposit - this.orderData.discount;
                    let itemLines = this.orderData.items.map(i =>
                        `• ${i.product.name} · ${i.size} · ${i.quantity}× · ${this.formatMoney(i.price)}đ`
                    ).join('<br>');
                    const summary = `<strong>👤 ${this.orderData.customer.name}</strong>${this.orderData.customer.phone ? ' · ' + this.orderData.customer.phone : ''}<br><br>${itemLines}<br><br>Tổng: <strong>${this.formatMoney(total)}đ</strong><br>Cọc: ${this.formatMoney(this.orderData.deposit)}đ<br>Giảm: ${this.formatMoney(this.orderData.discount)}đ<br>Còn lại: <strong>${this.formatMoney(remain)}đ</strong>${this.orderData.note ? '<br>Ghi chú: ' + this.orderData.note : ''}`;
                    this.sendBotMessage(summary, [
                        { label: '✅ Tạo đơn hàng', value: 'confirm_order' },
                        { label: '🔄 Bắt đầu lại', value: 'restart' },
                    ]);
                    break;
                }

                // Standalone customer
                case 'customer_name':
                    this.sendBotMessage('Tên khách hàng?');
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;
                case 'customer_phone':
                    this.sendBotMessage('Số điện thoại?', [
                        { label: 'Bỏ qua', value: 'skip_phone' }
                    ]);
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;
                case 'customer_address':
                    this.sendBotMessage('Địa chỉ?', [
                        { label: 'Bỏ qua', value: 'skip_address' }
                    ]);
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;
                case 'customer_confirm': {
                    const c = this.pendingCustomer;
                    this.sendBotMessage(
                        `Xác nhận thêm khách hàng:<br><strong>${c.name}</strong>${c.phone ? '<br>SĐT: ' + c.phone : ''}${c.address ? '<br>Địa chỉ: ' + c.address : ''}`,
                        [
                            { label: '✅ Thêm khách hàng', value: 'confirm_customer' },
                            { label: '🔄 Bắt đầu lại', value: 'restart' },
                        ]
                    );
                    break;
                }

                // Standalone product
                case 'product_name':
                    this.sendBotMessage('Tên sản phẩm?');
                    this.$nextTick(() => this.$refs.textInput && this.$refs.textInput.focus());
                    break;
                case 'product_price':
                    this.sendBotMessage('Giá gốc (đ)?', [
                        { label: 'Bỏ qua', value: 'skip_price' }
                    ]);
                    this.$nextTick(() => this.$refs.numInput && this.$refs.numInput.focus());
                    break;
                case 'product_image':
                    this.sendBotMessage('Ảnh sản phẩm?', [
                        { label: 'Bỏ qua', value: 'skip_image' }
                    ]);
                    break;
                case 'product_confirm': {
                    const p = this.pendingProduct;
                    this.sendBotMessage(
                        `Xác nhận thêm sản phẩm:<br><strong>${p.name}</strong>${p.default_price ? '<br>Giá: ' + this.formatMoney(p.default_price) + 'đ' : ''}${p.image_base64 ? '<br><img src="' + p.image_base64 + '" class="w-16 h-16 object-cover rounded mt-1 no-zoom">' : ''}`,
                        [
                            { label: '✅ Thêm sản phẩm', value: 'confirm_product' },
                            { label: '🔄 Bắt đầu lại', value: 'restart' },
                        ]
                    );
                    break;
                }
            }
        },

        selectSize(size) {
            this.orderData.currentItem.size = size;
            this.sendUserMessage('Size ' + size);
            this.goToStep('order_product_qty');
        },

        selectSearchResult(result) {
            if (this.step === 'order_customer') {
                this.orderData.customer = result;
                this.searchResults = [];
                this.searchQuery = '';
                this.sendUserMessage(result.name + (result.phone ? ' · ' + result.phone : ''));
                this.goToStep('order_add_product');
            } else if (this.step === 'order_add_product') {
                this.orderData.currentItem = { product: result, size: null, quantity: 1, price: result.default_price || 0 };
                this.searchResults = [];
                this.searchQuery = '';
                this.sendUserMessage(result.name);
                this.goToStep('order_product_size');
            }
        },

        finalizeItem() {
            const item = this.orderData.currentItem;
            this.orderData.items.push({
                product: item.product,
                product_id: item.product.id,
                size: item.size,
                quantity: item.quantity,
                price: item.price,
            });
            this.goToStep('order_more_items');
        },

        doSearch() {
            const q = this.searchQuery.trim();
            if (!q) {
                this.searchResults = [];
                return;
            }
            const type = this.step === 'order_customer' ? 'customers' : 'products';
            fetch(`/chatbot/search-${type}?q=${encodeURIComponent(q)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => { this.searchResults = data; })
            .catch(() => {});
        },

        async doCreateCustomerInline() {
            this.loading = true;
            try {
                const res = await fetch('/chatbot/create-customer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CHATBOT_CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.pendingCustomer),
                });
                const data = await res.json();
                if (data.success) {
                    this.orderData.customer = data.customer;
                    this.sendBotMessage(`✅ Đã tạo khách hàng: <strong>${data.customer.name}</strong>`);
                    this._returnStep = null;
                    this.goToStep('order_add_product');
                } else {
                    this.sendBotMessage('❌ Lỗi tạo khách hàng!');
                }
            } catch (e) {
                this.sendBotMessage('❌ Có lỗi xảy ra!');
            } finally {
                this.loading = false;
            }
        },

        async doCreateProductInline() {
            this.loading = true;
            try {
                const res = await fetch('/chatbot/create-product', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CHATBOT_CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.pendingProduct),
                });
                const data = await res.json();
                if (data.success) {
                    this.orderData.currentItem = {
                        product: data.product,
                        size: null,
                        quantity: 1,
                        price: data.product.default_price || 0,
                    };
                    this.sendBotMessage(`✅ Đã tạo sản phẩm: <strong>${data.product.name}</strong>`);
                    this._returnStep = null;
                    this.goToStep('order_product_size');
                } else {
                    this.sendBotMessage('❌ Lỗi tạo sản phẩm!');
                }
            } catch (e) {
                this.sendBotMessage('❌ Có lỗi xảy ra!');
            } finally {
                this.loading = false;
            }
        },

        async doCreateCustomer() {
            this.loading = true;
            try {
                const res = await fetch('/chatbot/create-customer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CHATBOT_CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.pendingCustomer),
                });
                const data = await res.json();
                if (data.success) {
                    this.sendBotMessage(
                        `✅ Đã thêm khách hàng <strong>${data.customer.name}</strong>! <a href="/customers" class="text-indigo-600 underline">Xem danh sách khách hàng →</a>`,
                        [{ label: '🔄 Tiếp tục', value: 'restart' }]
                    );
                    this.step = 'done';
                } else {
                    this.sendBotMessage('❌ Lỗi: ' + (data.message || 'Không thể tạo khách hàng.'));
                }
            } catch (e) {
                this.sendBotMessage('❌ Có lỗi xảy ra!');
            } finally {
                this.loading = false;
            }
        },

        async doCreateProduct() {
            this.loading = true;
            try {
                const res = await fetch('/chatbot/create-product', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CHATBOT_CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.pendingProduct),
                });
                const data = await res.json();
                if (data.success) {
                    this.sendBotMessage(
                        `✅ Đã thêm sản phẩm <strong>${data.product.name}</strong> (${data.product.product_code})! <a href="/products" class="text-indigo-600 underline">Xem danh sách sản phẩm →</a>`,
                        [{ label: '🔄 Tiếp tục', value: 'restart' }]
                    );
                    this.step = 'done';
                } else {
                    this.sendBotMessage('❌ Lỗi: ' + (data.message || 'Không thể tạo sản phẩm.'));
                }
            } catch (e) {
                this.sendBotMessage('❌ Có lỗi xảy ra!');
            } finally {
                this.loading = false;
            }
        },

        async doCreateOrder() {
            this.loading = true;
            try {
                const payload = {
                    customer_id: this.orderData.customer.id,
                    deposit_amount: this.orderData.deposit,
                    discount_amount: this.orderData.discount,
                    note: this.orderData.note,
                    items: this.orderData.items.map(i => ({
                        product_id: i.product_id,
                        size: i.size,
                        quantity: i.quantity,
                        price: i.price,
                    })),
                };
                const res = await fetch('/chatbot/create-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CHATBOT_CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    this.sendBotMessage(
                        `🎉 Đã tạo đơn hàng thành công! <a href="${data.order_url}" class="text-indigo-600 underline font-semibold">Xem đơn hàng #${data.order_id} →</a>`,
                        [{ label: '📦 Tạo đơn mới', value: 'restart' }]
                    );
                    this.step = 'done';
                } else {
                    this.sendBotMessage('❌ Lỗi: ' + (data.message || 'Không thể tạo đơn hàng.'));
                }
            } catch (e) {
                this.sendBotMessage('❌ Có lỗi xảy ra!');
            } finally {
                this.loading = false;
            }
        },

        handleImageSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.compressAndSetImage(file);
        },

        compressAndSetImage(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const MAX = 800;
                    let w = img.width, h = img.height;
                    if (w > MAX || h > MAX) {
                        if (w > h) { h = Math.round(h * MAX / w); w = MAX; }
                        else { w = Math.round(w * MAX / h); h = MAX; }
                    }
                    canvas.width = w;
                    canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    const base64 = canvas.toDataURL('image/jpeg', 0.8);
                    this.pendingProduct.image_base64 = base64;
                    this.sendUserMessage(`<img src="${base64}" class="w-16 h-16 object-cover rounded no-zoom">`);

                    // Auto-proceed
                    if (this.step === 'order_new_prd_img') {
                        this.doCreateProductInline();
                    } else if (this.step === 'product_image') {
                        this.goToStep('product_confirm');
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
            // Reset input
            event.target.value = '';
        },

        inputType() {
            const searchSteps = ['order_customer', 'order_add_product'];
            const numberSteps = ['order_new_prd_price', 'order_product_qty', 'order_product_price',
                                 'order_deposit', 'order_discount', 'product_price'];
            if (searchSteps.includes(this.step)) return 'search';
            if (numberSteps.includes(this.step)) return 'number';
            return 'text';
        },

        needsInput() {
            const noInputSteps = ['welcome', 'order_product_size', 'order_more_items',
                                  'order_confirm', 'customer_confirm', 'product_confirm', 'done'];
            return !noInputSteps.includes(this.step);
        },

        inputPlaceholder() {
            const map = {
                'order_customer': 'Tìm tên hoặc SĐT...',
                'order_add_product': 'Tìm tên sản phẩm...',
                'order_new_cus_name': 'Nhập tên khách hàng...',
                'order_new_cus_phone': 'Nhập số điện thoại...',
                'order_new_cus_addr': 'Nhập địa chỉ...',
                'order_new_prd_name': 'Nhập tên sản phẩm...',
                'order_new_prd_price': 'Nhập giá (đ)...',
                'order_product_qty': 'Nhập số lượng...',
                'order_product_price': 'Nhập giá bán (đ)...',
                'order_deposit': 'Nhập tiền cọc (đ)...',
                'order_discount': 'Nhập giảm giá (đ)...',
                'order_note': 'Nhập ghi chú...',
                'customer_name': 'Nhập tên khách hàng...',
                'customer_phone': 'Nhập số điện thoại...',
                'customer_address': 'Nhập địa chỉ...',
                'product_name': 'Nhập tên sản phẩm...',
                'product_price': 'Nhập giá (đ)...',
            };
            return map[this.step] || 'Nhập...';
        },

        orderTotal() {
            return this.orderData.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
        },

        formatMoney(n) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(n));
        },

        reset() {
            this.orderData = { customer: null, items: [], currentItem: {}, deposit: 0, discount: 0, note: '' };
            this.pendingCustomer = {};
            this.pendingProduct = {};
            this.inputValue = '';
            this.searchQuery = '';
            this.searchResults = [];
            this._returnStep = null;
            this.loading = false;
            this.showWelcome();
        },
    };
}
</script>
