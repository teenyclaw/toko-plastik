<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    public function index()
    {
        $outlet = current_outlet();
        $items = MenuItem::where('outlet_id', $outlet->id)
            ->with(['category', 'modifierGroups'])
            ->orderBy('sort_order')
            ->get();
        $categories = Category::where('outlet_id', $outlet->id)->orderBy('sort_order')->get();

        return view('admin.menu-items.index', compact('outlet', 'items', 'categories'));
    }

    public function store(Request $request)
    {
        $outlet = current_outlet();

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'price' => 'required|integer|min:0',
            'photo' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'is_available' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'stock_qty' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $photo = $request->file('photo')
            ? $request->file('photo')->store('menu', 'public')
            : null;

        $trackStock = $request->boolean('track_stock');

        MenuItem::create([
            'outlet_id' => $outlet->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'photo' => $photo,
            'sort_order' => $data['sort_order'] ?? 0,
            'track_stock' => $trackStock,
            'stock_qty' => $trackStock ? (int) ($data['stock_qty'] ?? 0) : 0,
            'low_stock_threshold' => $trackStock ? (int) ($data['low_stock_threshold'] ?? 5) : 5,
            'is_available' => $trackStock
                ? ((int) ($data['stock_qty'] ?? 0) > 0)
                : $request->boolean('is_available', true),
        ]);

        return back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit(MenuItem $menuItem)
    {
        abort_unless($menuItem->outlet_id === current_outlet_id(), 403);
        $menuItem->load(['category', 'modifierGroups.options']);

        return view('admin.menu-items.edit', compact('menuItem'));
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->outlet_id === current_outlet_id(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'price' => 'required|integer|min:0',
            'photo' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'is_available' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'stock_qty' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('photo')) {
            if ($menuItem->photo) {
                Storage::disk('public')->delete($menuItem->photo);
            }
            $menuItem->photo = $request->file('photo')->store('menu', 'public');
        }

        $trackStock = $request->boolean('track_stock');

        $menuItem->update([
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'sort_order' => $data['sort_order'] ?? 0,
            'track_stock' => $trackStock,
            'stock_qty' => $trackStock ? (int) ($data['stock_qty'] ?? $menuItem->stock_qty) : 0,
            'low_stock_threshold' => $trackStock ? (int) ($data['low_stock_threshold'] ?? 5) : 5,
            'is_available' => $trackStock
                ? ((int) ($data['stock_qty'] ?? $menuItem->stock_qty) > 0)
                : $request->boolean('is_available'),
        ]);

        return back()->with('success', 'Menu diperbarui.');
    }

    public function destroy(MenuItem $menuItem)
    {
        abort_unless($menuItem->outlet_id === current_outlet_id(), 403);

        if ($menuItem->photo) {
            Storage::disk('public')->delete($menuItem->photo);
        }

        $menuItem->delete();

        return back()->with('success', 'Menu dihapus.');
    }
}
