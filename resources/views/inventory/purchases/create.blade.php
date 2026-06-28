@extends('layouts.app')

@section('title', 'Pembelian Baru')

@section('content')
<div x-data="purchaseApp()" x-init="loadProducts()" class="flex flex-col min-h-[70vh]">
    <h1 class="text-2xl font-bold mb-4">Pembelian Baru</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-5 gap-4 flex-1">
        <div class="lg:col-span-3 bg-white rounded-xl border flex flex-col">
            <div class="p-4 border-b space-y-3">
                <div>
                    <label class="text-xs text-slate-600">Supplier</label>
                    <select x-model="supplierId" @change="loadProducts()" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">— Pilih supplier —</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="search" x-model="query" @input.debounce.300ms="loadProducts()" :disabled="!supplierId"
                       placeholder="Cari produk..."
                       class="w-full border rounded-lg px-3 py-2 text-sm disabled:bg-slate-100">
            </div>
            <div class="flex-1 overflow-y-auto p-3 grid sm:grid-cols-2 gap-2 content-start max-h-96">
                <template x-for="p in products" :key="p.id">
                    <button type="button" @click="addItem(p)" :disabled="!supplierId"
                            class="text-left border rounded-lg p-3 hover:border-blue-500 hover:bg-blue-50 text-sm disabled:opacity-50">
                        <div class="font-medium" x-text="p.name"></div>
                        <div class="text-xs text-slate-500" x-text="p.code"></div>
                        <div class="mt-1 flex justify-between">
                            <span x-text="formatMoney(p.buy_price)"></span>
                            <span class="text-slate-500" x-text="'Stok: '+p.stock"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border flex flex-col">
            <div class="p-4 border-b font-semibold">Daftar Barang</div>
            <div class="flex-1 overflow-y-auto divide-y">
                <template x-for="(item, index) in cart" :key="item.product_id">
                    <div class="p-3 text-sm">
                        <div class="font-medium" x-text="item.name"></div>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div>
                                <label class="text-xs">Qty</label>
                                <input type="number" min="0.001" step="0.001" x-model.number="item.quantity" @change="recalc()"
                                       class="w-full border rounded px-2 py-1 text-sm">
                            </div>
                            <div>
                                <label class="text-xs">Harga beli</label>
                                <input type="number" min="0" x-model.number="item.unit_price" @change="recalc()"
                                       class="w-full border rounded px-2 py-1 text-sm">
                            </div>
                        </div>
                        <div class="flex justify-between mt-2">
                            <button type="button" @click="cart.splice(index,1)" class="text-red-600 text-xs">Hapus</button>
                            <span class="font-medium" x-text="formatMoney(item.quantity * item.unit_price)"></span>
                        </div>
                    </div>
                </template>
                <div x-show="cart.length === 0" class="p-8 text-center text-slate-500 text-sm">Belum ada barang.</div>
            </div>

            <form method="POST" action="{{ route('purchases.store') }}" class="p-4 border-t bg-slate-50 space-y-3 rounded-b-xl">
                @csrf
                <input type="hidden" name="supplier_id" :value="supplierId">
                <template x-for="(item, index) in cart" :key="'in-'+item.product_id">
                    <div>
                        <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                        <input type="hidden" :name="'items['+index+'][quantity]'" :value="item.quantity">
                        <input type="hidden" :name="'items['+index+'][unit_price]'" :value="item.unit_price">
                    </div>
                </template>

                <div class="flex justify-between text-sm"><span>Subtotal</span><span class="font-semibold" x-text="formatMoney(subtotal)"></span></div>
                <div>
                    <label class="text-xs">Diskon (Rp)</label>
                    <input type="number" name="discount" x-model.number="discount" min="0" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="flex justify-between font-bold"><span>Total</span><span x-text="formatMoney(total)"></span></div>
                <div>
                    <label class="text-xs">Metode Bayar</label>
                    <select name="payment_method" x-model="paymentMethod" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="cash">Tunai</option>
                        <option value="tempo">Tempo (hutang supplier)</option>
                    </select>
                </div>
                <div x-show="paymentMethod === 'cash'">
                    <label class="text-xs">Dibayar (Rp)</label>
                    <input type="number" name="paid" x-model.number="paid" min="0" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs">Catatan</label>
                    <input type="text" name="notes" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <button type="submit" :disabled="!supplierId || cart.length === 0"
                        class="w-full py-3 bg-blue-700 text-white rounded-lg font-semibold disabled:opacity-50">
                    Simpan Pembelian
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function purchaseApp() {
    return {
        supplierId: '',
        query: '',
        products: [],
        cart: [],
        discount: 0,
        paid: 0,
        paymentMethod: 'cash',

        async loadProducts() {
            if (!this.supplierId) { this.products = []; return; }
            const res = await fetch(`{{ route('purchases.products') }}?supplier_id=${this.supplierId}&q=${encodeURIComponent(this.query)}`);
            this.products = (await res.json()).data;
        },

        addItem(p) {
            const ex = this.cart.find(i => i.product_id === p.id);
            if (ex) { ex.quantity += 1; } else {
                this.cart.push({ product_id: p.id, name: p.name, quantity: 1, unit_price: p.buy_price });
            }
            this.recalc();
        },

        get subtotal() { return this.cart.reduce((s, i) => s + i.quantity * i.unit_price, 0); },
        get total() { return Math.max(0, this.subtotal - (this.discount || 0)); },
        recalc() { this.paid = this.total; },
        formatMoney(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0)); },
    };
}
</script>
@endpush
