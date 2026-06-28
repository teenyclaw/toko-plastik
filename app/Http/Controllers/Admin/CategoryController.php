<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $outlet = current_outlet();
        $categories = Category::where('outlet_id', $outlet->id)
            ->orderBy('sort_order')
            ->withCount('menuItems')
            ->get();

        return view('admin.categories.index', compact('outlet', 'categories'));
    }

    public function store(Request $request)
    {
        $outlet = current_outlet();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        Category::create([
            'outlet_id' => $outlet->id,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->outlet_id === current_outlet_id(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        abort_unless($category->outlet_id === current_outlet_id(), 403);

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
