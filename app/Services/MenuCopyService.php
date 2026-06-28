<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Outlet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuCopyService
{
    /**
     * @return array{categories: int, items: int, updated: int, skipped: int, modifiers: int}
     */
    public function copy(
        Outlet $from,
        Outlet $to,
        bool $overwrite = false,
        bool $includeModifiers = true,
        bool $copyStock = false
    ): array {
        if ($from->id === $to->id) {
            throw new \RuntimeException('Cabang sumber dan tujuan tidak boleh sama.');
        }

        return DB::transaction(function () use ($from, $to, $overwrite, $includeModifiers, $copyStock) {
            $stats = [
                'categories' => 0,
                'items' => 0,
                'updated' => 0,
                'skipped' => 0,
                'modifiers' => 0,
            ];

            $categoryMap = $this->copyCategories($from, $to, $overwrite, $stats);

            MenuItem::query()
                ->where('outlet_id', $from->id)
                ->with(['modifierGroups.options'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(function (MenuItem $item) use ($to, $categoryMap, $overwrite, $includeModifiers, $copyStock, &$stats) {
                    $this->copyMenuItem($item, $to, $categoryMap, $overwrite, $includeModifiers, $copyStock, $stats);
                });

            return $stats;
        });
    }

    /** @param array{categories: int, items: int, updated: int, skipped: int, modifiers: int} $stats */
    private function copyCategories(Outlet $from, Outlet $to, bool $overwrite, array &$stats): array
    {
        $map = [];

        Category::query()
            ->where('outlet_id', $from->id)
            ->orderBy('sort_order')
            ->get()
            ->each(function (Category $category) use ($to, $overwrite, &$stats, &$map) {
                $existing = Category::query()
                    ->where('outlet_id', $to->id)
                    ->where('name', $category->name)
                    ->first();

                if ($existing) {
                    if ($overwrite) {
                        $existing->update([
                            'sort_order' => $category->sort_order,
                            'is_active' => $category->is_active,
                        ]);
                    }
                    $map[$category->id] = $existing->id;

                    return;
                }

                $created = Category::create([
                    'outlet_id' => $to->id,
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                ]);

                $map[$category->id] = $created->id;
                $stats['categories']++;
            });

        return $map;
    }

    /** @param array<int, int> $categoryMap */
    /** @param array{categories: int, items: int, updated: int, skipped: int, modifiers: int} $stats */
    private function copyMenuItem(
        MenuItem $item,
        Outlet $to,
        array $categoryMap,
        bool $overwrite,
        bool $includeModifiers,
        bool $copyStock,
        array &$stats
    ): void {
        $targetCategoryId = $item->category_id ? ($categoryMap[$item->category_id] ?? null) : null;

        $existing = MenuItem::query()
            ->where('outlet_id', $to->id)
            ->where('name', $item->name)
            ->when(
                $targetCategoryId,
                fn ($q) => $q->where('category_id', $targetCategoryId),
                fn ($q) => $q->whereNull('category_id')
            )
            ->first();

        if ($existing && ! $overwrite) {
            $stats['skipped']++;

            return;
        }

        $attributes = [
            'outlet_id' => $to->id,
            'category_id' => $targetCategoryId,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $item->price,
            'sort_order' => $item->sort_order,
            'is_available' => $item->is_available,
            'track_stock' => $copyStock ? $item->track_stock : false,
            'stock_qty' => $copyStock ? $item->stock_qty : 0,
            'low_stock_threshold' => $copyStock ? $item->low_stock_threshold : 5,
        ];

        if ($existing && $overwrite) {
            if ($existing->photo && $existing->photo !== $item->photo) {
                Storage::disk('public')->delete($existing->photo);
            }
            $attributes['photo'] = $this->duplicatePhoto($item->photo);
            $existing->update($attributes);
            $targetItem = $existing;
            $stats['updated']++;
        } else {
            $attributes['photo'] = $this->duplicatePhoto($item->photo);
            $targetItem = MenuItem::create($attributes);
            $stats['items']++;
        }

        if ($includeModifiers) {
            $stats['modifiers'] += $this->copyModifiers($item, $targetItem, (bool) $existing && $overwrite);
        }
    }

    private function copyModifiers(MenuItem $source, MenuItem $target, bool $replaceExisting): int
    {
        if ($replaceExisting) {
            ModifierGroup::query()
                ->where('menu_item_id', $target->id)
                ->each(function (ModifierGroup $group) {
                    $group->options()->delete();
                    $group->delete();
                });
        }

        $count = 0;

        foreach ($source->modifierGroups as $group) {
            $newGroup = ModifierGroup::create([
                'menu_item_id' => $target->id,
                'name' => $group->name,
                'selection_type' => $group->selection_type,
                'min_select' => $group->min_select,
                'max_select' => $group->max_select,
                'sort_order' => $group->sort_order,
            ]);

            foreach ($group->options as $option) {
                ModifierOption::create([
                    'modifier_group_id' => $newGroup->id,
                    'name' => $option->name,
                    'price_adjustment' => $option->price_adjustment,
                    'is_default' => $option->is_default,
                    'sort_order' => $option->sort_order,
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function duplicatePhoto(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $newPath = 'menu/' . Str::uuid() . ($extension ? '.' . $extension : '');

        Storage::disk('public')->copy($path, $newPath);

        return $newPath;
    }
}
