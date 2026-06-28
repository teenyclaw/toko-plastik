<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use Illuminate\Http\Request;

class ModifierController extends Controller
{
    public function storeGroup(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->outlet_id === current_outlet_id(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'selection_type' => 'required|in:single,multiple',
            'min_select' => 'nullable|integer|min:0|max:10',
            'max_select' => 'nullable|integer|min:1|max:20',
        ]);

        $maxSelect = $data['selection_type'] === 'single'
            ? 1
            : ($data['max_select'] ?? 10);

        ModifierGroup::create([
            'menu_item_id' => $menuItem->id,
            'name' => $data['name'],
            'selection_type' => $data['selection_type'],
            'min_select' => $data['min_select'] ?? ($data['selection_type'] === 'single' ? 1 : 0),
            'max_select' => $maxSelect,
            'sort_order' => $menuItem->modifierGroups()->count(),
        ]);

        return back()->with('success', 'Grup varian ditambahkan.');
    }

    public function destroyGroup(ModifierGroup $group)
    {
        abort_unless($group->menuItem->outlet_id === current_outlet_id(), 403);

        $group->delete();

        return back()->with('success', 'Grup varian dihapus.');
    }

    public function storeOption(Request $request, ModifierGroup $group)
    {
        abort_unless($group->menuItem->outlet_id === current_outlet_id(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'price_adjustment' => 'nullable|integer',
            'is_default' => 'nullable|boolean',
        ]);

        ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name' => $data['name'],
            'price_adjustment' => $data['price_adjustment'] ?? 0,
            'is_default' => $request->boolean('is_default'),
            'sort_order' => $group->options()->count(),
        ]);

        return back()->with('success', 'Opsi varian ditambahkan.');
    }

    public function destroyOption(ModifierOption $option)
    {
        abort_unless($option->group->menuItem->outlet_id === current_outlet_id(), 403);

        $option->delete();

        return back()->with('success', 'Opsi varian dihapus.');
    }
}
