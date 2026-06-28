<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use Illuminate\Support\Collection;

class ModifierService
{
    /** @return array{modifiers: array<int, array{group:string,option:string,price:int}>, adjustment: int, option_ids: int[]} */
    public function resolve(MenuItem $item, array $optionIds): array
    {
        $item->loadMissing('modifierGroups.options');
        $groups = $item->modifierGroups;

        if ($groups->isEmpty()) {
            if (! empty($optionIds)) {
                throw new \RuntimeException('Menu ini tidak memiliki varian.');
            }

            return ['modifiers' => [], 'adjustment' => 0, 'option_ids' => []];
        }

        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
        $options = ModifierOption::query()
            ->whereIn('id', $optionIds)
            ->whereHas('group', fn ($q) => $q->where('menu_item_id', $item->id))
            ->with('group')
            ->get()
            ->keyBy('id');

        if (count($optionIds) !== $options->count()) {
            throw new \RuntimeException('Varian tidak valid.');
        }

        foreach ($groups as $group) {
            $selected = $options->filter(fn (ModifierOption $opt) => $opt->modifier_group_id === $group->id);

            if ($selected->count() < $group->min_select) {
                throw new \RuntimeException("Pilih minimal {$group->min_select} opsi untuk {$group->name}.");
            }

            if ($group->isSingle() && $selected->count() > 1) {
                throw new \RuntimeException("Hanya boleh pilih satu opsi untuk {$group->name}.");
            }

            if ($group->max_select > 0 && $selected->count() > $group->max_select) {
                throw new \RuntimeException("Maksimal {$group->max_select} opsi untuk {$group->name}.");
            }
        }

        $modifiers = $options->sortBy(fn (ModifierOption $opt) => [$opt->group->sort_order, $opt->sort_order])
            ->map(fn (ModifierOption $opt) => [
                'group' => $opt->group->name,
                'option' => $opt->name,
                'price' => (int) $opt->price_adjustment,
            ])
            ->values()
            ->all();

        return [
            'modifiers' => $modifiers,
            'adjustment' => (int) $options->sum('price_adjustment'),
            'option_ids' => $options->keys()->sort()->values()->all(),
        ];
    }

    /** @param array<int, array{group:string,option:string,price:int}> $modifiers */
    public function displayName(string $baseName, array $modifiers): string
    {
        if (empty($modifiers)) {
            return $baseName;
        }

        $labels = array_map(fn (array $m) => $m['option'], $modifiers);

        return $baseName . ' (' . implode(', ', $labels) . ')';
    }

    /** @param array<int, int> $optionIds */
    public function lineKey(int $menuItemId, array $optionIds, ?string $note = null): string
    {
        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
        sort($optionIds);

        return $menuItemId . '_' . md5(implode(',', $optionIds) . '|' . ($note ?? ''));
    }

    /** @return array{line_key:string,menu_item_id:int,name:string,display_name:string,price:int,qty:int,note:?string,option_ids:int[],modifiers:array} */
    public function buildCartLine(MenuItem $item, int $qty, array $optionIds, ?string $note = null): array
    {
        $resolved = $this->resolve($item, $optionIds);
        $unitPrice = $item->price + $resolved['adjustment'];
        $displayName = $this->displayName($item->name, $resolved['modifiers']);

        return [
            'line_key' => $this->lineKey($item->id, $resolved['option_ids'], $note),
            'menu_item_id' => $item->id,
            'name' => $item->name,
            'display_name' => $displayName,
            'price' => $unitPrice,
            'qty' => $qty,
            'note' => $note,
            'option_ids' => $resolved['option_ids'],
            'modifiers' => $resolved['modifiers'],
        ];
    }

    /** @param array<int, array{group:string,option:string,price:int}> $modifiers */
    public function modifierSignature(array $modifiers): string
    {
        if (empty($modifiers)) {
            return '';
        }

        return md5(json_encode($modifiers));
    }

    public function defaultOptionIds(MenuItem $item): array
    {
        $item->loadMissing('modifierGroups.options');

        return $item->modifierGroups
            ->flatMap(fn (ModifierGroup $group) => $group->options->where('is_default', true)->pluck('id'))
            ->values()
            ->all();
    }
}
