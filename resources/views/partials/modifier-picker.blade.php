@php
    $item->loadMissing('modifierGroups.options');
    $hasModifiers = $item->hasModifiers();
    $formAction = $formAction ?? '#';
    $compact = $compact ?? false;
@endphp

@if($hasModifiers)
    <div x-data="{ open: false, itemId: {{ $item->id }} }"
         @modifier-picker-open.window="open = ($event.detail === itemId)"
         @keydown.escape.window="open = false"
         class="{{ $compact ? '' : 'mt-2' }}">
        <button type="button"
            @click="$dispatch('modifier-picker-open', itemId)"
            class="{{ $compact ? 'shrink-0 bg-orange-500 text-white text-sm px-3 py-1.5 rounded-lg' : 'w-full bg-orange-500 text-white text-sm px-3 py-2 rounded-lg font-medium' }}">
            Pilih Varian
        </button>

        <div x-show="open" x-cloak
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/50"
            @click.self="open = false">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md max-h-[85vh] overflow-y-auto p-4 shadow-xl">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold">{{ $item->name }}</h3>
                        <p class="text-sm text-stone-500">Dari {{ $item->formattedPrice() }}</p>
                    </div>
                    <button type="button" @click="open = false" class="text-stone-400 text-xl leading-none">&times;</button>
                </div>

                <form method="POST" action="{{ $formAction }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="menu_item_id" value="{{ $item->id }}">

                    @foreach($item->modifierGroups as $group)
                        <fieldset>
                            <legend class="text-sm font-semibold mb-2">
                                {{ $group->name }}
                                @if($group->min_select > 0)<span class="text-red-500">*</span>@endif
                            </legend>
                            <div class="space-y-2">
                                @foreach($group->options as $opt)
                                    <label class="flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm cursor-pointer hover:border-orange-300">
                                        <span class="flex items-center gap-2">
                                            <input
                                                type="{{ $group->isSingle() ? 'radio' : 'checkbox' }}"
                                                name="option_ids[]"
                                                value="{{ $opt->id }}"
                                                @checked($opt->is_default)
                                                @if($group->isSingle() && $group->min_select > 0) required @endif
                                            >
                                            {{ $opt->name }}
                                        </span>
                                        @if($opt->price_adjustment !== 0)
                                            <span class="text-stone-500 text-xs">{{ $opt->formattedAdjustment() }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-stone-500">Qty</label>
                            <input type="number" name="qty" value="1" min="1" max="99" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs text-stone-500">Catatan</label>
                            <input type="text" name="note" placeholder="Opsional" class="w-full border rounded-lg px-3 py-2">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-orange-500 text-white font-semibold py-3 rounded-xl">Tambah ke Keranjang</button>
                </form>
            </div>
        </div>
    </div>
@else
    <form method="POST" action="{{ $formAction }}" class="{{ $compact ? 'flex gap-2 items-center' : 'mt-2 flex gap-2 items-center' }}">
        @csrf
        <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
        <input type="number" name="qty" value="1" min="1" max="99" class="w-14 border rounded-lg px-2 py-1 text-sm">
        @unless($compact)
            <input type="text" name="note" placeholder="Catatan" class="flex-1 border rounded-lg px-2 py-1 text-sm min-w-0">
        @endunless
        <button type="submit" class="{{ $compact ? 'shrink-0 bg-orange-500 text-white text-sm px-3 py-1.5 rounded-lg' : 'shrink-0 bg-orange-500 text-white text-sm px-3 py-1.5 rounded-lg' }}">+</button>
    </form>
@endif
