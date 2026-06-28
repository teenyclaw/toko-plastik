<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\MenuCopyService;
use Illuminate\Http\Request;

class MenuCopyController extends Controller
{
    public function __construct(private MenuCopyService $menuCopy) {}

    public function index(Request $request)
    {
        $outlets = Outlet::query()->orderBy('name')->get();
        $currentOutlet = current_outlet();
        $defaultFromId = $request->integer('from') ?: $currentOutlet?->id;

        return view('admin.menu-copy.index', compact('outlets', 'currentOutlet', 'defaultFromId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_outlet_id' => 'required|exists:outlets,id',
            'to_outlet_id' => 'required|exists:outlets,id|different:from_outlet_id',
            'overwrite' => 'nullable|boolean',
            'include_modifiers' => 'nullable|boolean',
            'copy_stock' => 'nullable|boolean',
        ]);

        $from = Outlet::findOrFail($data['from_outlet_id']);
        $to = Outlet::findOrFail($data['to_outlet_id']);

        try {
            $stats = $this->menuCopy->copy(
                $from,
                $to,
                $request->boolean('overwrite'),
                $request->boolean('include_modifiers', true),
                $request->boolean('copy_stock')
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = sprintf(
            'Menu disalin dari %s ke %s: %d kategori baru, %d menu baru, %d diperbarui, %d dilewati, %d opsi varian.',
            $from->name,
            $to->name,
            $stats['categories'],
            $stats['items'],
            $stats['updated'],
            $stats['skipped'],
            $stats['modifiers']
        );

        return back()->with('success', $message);
    }
}
