<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use Illuminate\Http\Request;

class DiningTableController extends Controller
{
    public function index()
    {
        $outlet = current_outlet();
        $tables = DiningTable::where('outlet_id', $outlet->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.tables.index', compact('outlet', 'tables'));
    }

    public function store(Request $request)
    {
        $outlet = current_outlet();

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DiningTable::create([
            'outlet_id' => $outlet->id,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Meja ditambahkan.');
    }

    public function update(Request $request, DiningTable $table)
    {
        abort_unless($table->outlet_id === current_outlet_id(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $table->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Meja diperbarui.');
    }

    public function destroy(DiningTable $table)
    {
        abort_unless($table->outlet_id === current_outlet_id(), 403);

        if ($table->activeBill()) {
            return back()->with('error', 'Meja masih memiliki bill terbuka.');
        }

        $table->delete();

        return back()->with('success', 'Meja dihapus.');
    }

    public function regenerateToken(DiningTable $table)
    {
        abort_unless($table->outlet_id === current_outlet_id(), 403);

        $table->update(['token' => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8))]);

        return back()->with('success', 'QR meja diperbarui.');
    }
}
