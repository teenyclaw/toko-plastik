<?php

namespace App\Http\Controllers;

use App\Services\CurrentOutletService;
use Illuminate\Http\Request;

class OutletSwitchController extends Controller
{
    public function __construct(private CurrentOutletService $currentOutlet) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'outlet_id' => 'required|integer|exists:outlets,id',
        ]);

        try {
            $this->currentOutlet->switch(auth()->user(), (int) $data['outlet_id']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cabang aktif diubah.');
    }
}
