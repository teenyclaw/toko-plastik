@extends('layouts.app')

@section('title', 'Kasir POS')

@section('content')
<div x-data="posApp()" x-init="loadProducts()" class="h-[calc(100vh-3rem)] flex flex-col">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Kasir POS</h1>
        <button type="button" @click="clearCart()" class="text-sm text-red-600 hover:underline" x-show="cart.length">Kosongkan keranjang</button>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex-1 grid lg:grid-cols-5 gap-4 min-h-0">
        {{-- Product panel --}}
        <div class="lg:col-span-3 bg-white rounded-xl border flex flex-col min-h-0">
            <div class="p-4 border-b">
                <input type="search" x-model="query" @input.debounce.300ms="loadProducts()"
                       placeholder="Cari produk atau scan barcode..."
                       class="w-full border rounded-lg px-3 py-2 text-sm" autofocus>
            </div>
            <div class="flex-1 overflow-y-auto p-3 grid sm:grid-cols-2 gap-2 content-start">
                <template x-for="p in products" :key="p.id">
                    <button type="button" @click="addToCart(p)"
                            class="text-left border rounded-lg p-3 hover:border-blue-500 hover:bg-blue-50 transition"
                            :class="p.stock <= 0 ? 'opacity-50 cursor-not-allowed' : ''"
                            :disabled="p.stock <= 0">
                        <div class="font-medium text-sm" x-text="p.name"></div>
                        <div class="text-xs text-slate-500 mt-1" x-text="p.code + ' · ' + p.category"></div>
                        <div class="flex justify-between mt-2 text-sm">
                            <span class="font-semibold text-blue-700" x-text="formatMoney(p.sell_price)"></span>
                            <span class="text-slate-500" x-text="'Stok: ' + p.stock + ' ' + p.unit"></span>
                        </div>
                    </button>
                </template>
                <div x-show="!loading && products.length === 0" class="col-span-2 text-center text-slate-500 py-8 text-sm">
                    Produk tidak ditemukan.
                </div>
            </div>
        </div>

        {{-- Cart panel --}}
        <div class="lg:col-span-2 bg-white rounded-xl border flex flex-col min-h-0">
            <div class="p-4 border-b font-semibold">Keranjang</div>
            <div class="flex-1 overflow-y-auto divide-y">
                <template x-for="(item, index) in cart" :key="item.product_id">
                    <div class="p-3 text-sm">
                        <div class="font-medium" x-text="item.name"></div>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" @click="changeQty(index, -1)" class="w-7 h-7 border rounded">−</button>
                            <input type="number" min="0.001" step="0.001" x-model.number="item.quantity" @change="recalc()"
                                   class="w-16 border rounded text-center py-1">
                            <button type="button" @click="changeQty(index, 1)" class="w-7 h-7 border rounded">+</button>
                            <span class="text-slate-500" x-text="item.unit"></span>
                            <button type="button" @click="removeItem(index)" class="ml-auto text-red-600 text-xs">Hapus</button>
                        </div>
                        <div class="text-right mt-1 font-medium" x-text="formatMoney(lineTotal(item))"></div>
                    </div>
                </template>
                <div x-show="cart.length === 0" class="p-8 text-center text-slate-500 text-sm">Keranjang kosong.</div>
            </div>

            <form method="POST" action="{{ route('pos.sales.store') }}" class="p-4 border-t space-y-3 bg-slate-50 rounded-b-xl">
                @csrf
                <template x-for="(item, index) in cart" :key="'input-'+item.product_id">
                    <div>
                        <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                        <input type="hidden" :name="'items['+index+'][quantity]'" :value="item.quantity">
                        <input type="hidden" :name="'items['+index+'][unit_price]'" :value="item.unit_price">
                    </div>
                </template>

                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span class="font-semibold" x-text="formatMoney(subtotal)"></span>
                </div>

                <div>
                    <label class="text-xs text-slate-600">Diskon (Rp)</label>
                    <input type="number" name="discount" x-model.number="discount" min="0" step="1" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span x-text="formatMoney(total)"></span>
                </div>

                <div>
                    <label class="text-xs text-slate-600">Metode Bayar</label>
                    <select name="payment_method" x-model="paymentMethod" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="cash">Tunai</option>
                        <option value="tempo">Tempo</option>
                    </select>
                </div>

                <div x-show="paymentMethod === 'tempo'">
                    <label class="text-xs text-slate-600">Pelanggan</label>
                    <select name="customer_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">— Pilih pelanggan —</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="paymentMethod === 'cash'">
                    <label class="text-xs text-slate-600">Bayar (Rp)</label>
                    <input type="number" name="paid" x-model.number="paid" min="0" step="1" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <div class="flex justify-between text-sm mt-1" x-show="paid >= total">
                        <span>Kembalian</span>
                        <span x-text="formatMoney(Math.max(0, paid - total))"></span>
                    </div>
                </div>

                <button type="submit" :disabled="cart.length === 0"
                        class="w-full py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Bayar & Cetak Struk
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        query: '',
        products: [],
        cart: [],
        loading: false,
        discount: 0,
        paid: 0,
        paymentMethod: 'cash',

        async loadProducts() {
            this.loading = true;
            const res = await fetch(`{{ route('pos.products') }}?q=${encodeURIComponent(this.query)}`);
            const json = await res.json();
            this.products = json.data;
            this.loading = false;

            if (this.query && this.products.length === 1) {
                const exact = this.products.find(p =>
                    p.barcode === this.query || p.code === this.query
                );
                if (exact) {
                    this.addToCart(exact);
                    this.query = '';
                    this.loadProducts();
                }
            }
        },

        addToCart(product) {
            if (product.stock <= 0) return;
            const existing = this.cart.find(i => i.product_id === product.id);
            if (existing) {
                if (existing.quantity + 1 > product.stock) return;
                existing.quantity += 1;
            } else {
                this.cart.push({
                    product_id: product.id,
                    name: product.name,
                    unit: product.unit,
                    unit_price: product.sell_price,
                    quantity: 1,
                    stock: product.stock,
                });
            }
            this.paid = this.total;
        },

        changeQty(index, delta) {
            const item = this.cart[index];
            const next = item.quantity + delta;
            if (next <= 0) {
                this.removeItem(index);
                return;
            }
            if (next > item.stock) return;
            item.quantity = next;
            this.recalc();
        },

        removeItem(index) {
            this.cart.splice(index, 1);
            this.recalc();
        },

        clearCart() {
            this.cart = [];
            this.discount = 0;
            this.paid = 0;
        },

        lineTotal(item) {
            return item.quantity * item.unit_price;
        },

        get subtotal() {
            return this.cart.reduce((s, i) => s + this.lineTotal(i), 0);
        },

        get total() {
            return Math.max(0, this.subtotal - (this.discount || 0));
        },

        recalc() {
            this.paid = this.total;
        },

        formatMoney(n) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
        },
    };
}
</script>
@endpush
