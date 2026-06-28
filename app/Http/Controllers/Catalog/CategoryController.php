<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('catalog.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('catalog.categories.create', [
            'category' => new Category(),
            'types' => CategoryType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:plastik,bahan_kue'],
        ]);

        Category::query()->create($data);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        return view('catalog.categories.edit', [
            'category' => $category,
            'types' => CategoryType::cases(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:plastik,bahan_kue'],
        ]);

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Kategori masih dipakai produk.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori dihapus.');
    }
}
