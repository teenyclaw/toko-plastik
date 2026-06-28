<div>
    <label class="text-sm">Nama cabang</label>
    <input type="text" name="name" value="{{ old('name', $outlet?->name) }}" required class="w-full border rounded-lg px-3 py-2 mt-1">
</div>
<div>
    <label class="text-sm">Slug URL</label>
    <input type="text" name="slug" value="{{ old('slug', $outlet?->slug) }}" required class="w-full border rounded-lg px-3 py-2 mt-1">
    <p class="text-xs text-slate-500 mt-1">Katalog pelanggan: /o/<em>slug</em></p>
</div>
<div>
    <label class="text-sm">Telepon</label>
    <input type="text" name="phone" value="{{ old('phone', $outlet?->phone) }}" class="w-full border rounded-lg px-3 py-2 mt-1">
</div>
<div>
    <label class="text-sm">Alamat</label>
    <textarea name="address" rows="2" class="w-full border rounded-lg px-3 py-2 mt-1">{{ old('address', $outlet?->address) }}</textarea>
</div>
<label class="inline-flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $outlet?->is_active ?? true) ? 'checked' : '' }}> Cabang aktif
</label>

@if($cashiers->isNotEmpty())
    <div class="pt-2 border-t">
        <div class="text-sm font-medium mb-2">Kasir yang boleh akses cabang ini</div>
        <div class="space-y-2">
            @foreach($cashiers as $cashier)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="cashier_ids[]" value="{{ $cashier->id }}"
                        @checked(in_array($cashier->id, old('cashier_ids', $assignedCashierIds)))>
                    {{ $cashier->name }} ({{ $cashier->email }})
                </label>
            @endforeach
        </div>
    </div>
@endif
