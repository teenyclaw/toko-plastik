<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\User;
use App\Services\CurrentOutletService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = Outlet::query()->orderBy('name')->get();

        return view('admin.outlets.index', compact('outlets'));
    }

    public function create()
    {
        $cashiers = User::query()->where('role', 'cashier')->orderBy('name')->get();

        return view('admin.outlets.create', compact('cashiers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'required|string|max:80|alpha_dash|unique:outlets,slug',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'cashier_ids' => 'nullable|array',
            'cashier_ids.*' => 'integer|exists:users,id',
        ]);

        $outlet = Outlet::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $cashierIds = User::query()
            ->where('role', 'cashier')
            ->whereIn('id', $data['cashier_ids'] ?? [])
            ->pluck('id');
        $outlet->users()->sync($cashierIds);

        return redirect()->route('admin.outlets.edit', $outlet)
            ->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function edit(Outlet $outlet)
    {
        $catalogUrl = $outlet->catalogUrl();
        $cashiers = User::query()->where('role', 'cashier')->orderBy('name')->get();
        $assignedCashierIds = $outlet->users()->pluck('users.id')->all();

        return view('admin.outlets.edit', compact('outlet', 'catalogUrl', 'cashiers', 'assignedCashierIds'));
    }

    public function update(Request $request, Outlet $outlet)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('outlets', 'slug')->ignore($outlet->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'cashier_ids' => 'nullable|array',
            'cashier_ids.*' => 'integer|exists:users,id',
        ]);

        $outlet->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $cashierIds = User::query()
            ->where('role', 'cashier')
            ->whereIn('id', $data['cashier_ids'] ?? [])
            ->pluck('id');
        $outlet->users()->sync($cashierIds);

        return back()->with('success', 'Cabang diperbarui.');
    }

    public function destroy(Outlet $outlet)
    {
        if ($outlet->orders()->exists()) {
            $outlet->update(['is_active' => false]);

            return back()->with('success', 'Cabang dinonaktifkan karena sudah memiliki riwayat order.');
        }

        $outlet->users()->detach();
        $outlet->delete();

        if (session(CurrentOutletService::SESSION_KEY) == $outlet->id && auth()->check()) {
            app(CurrentOutletService::class)->setDefaultForUser(auth()->user());
        }

        return redirect()->route('admin.outlets.index')
            ->with('success', 'Cabang dihapus.');
    }
}
