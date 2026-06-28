<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::query()->withCount('products')->orderBy('name')->get();

        return view('catalog.units.index', compact('units'));
    }

    public function create(): View
    {
        return view('catalog.units.create', [
            'unit' => new Unit(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:20'],
        ]);

        Unit::query()->create($data);

        return redirect()->route('units.index')->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function edit(Unit $unit): View
    {
        return view('catalog.units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:20'],
        ]);

        $unit->update($data);

        return redirect()->route('units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'Satuan masih dipakai produk.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Satuan dihapus.');
    }
}
