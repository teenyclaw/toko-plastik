<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private ShiftService $shiftService) {}

    public function show()
    {
        $outlet = current_outlet();
        $shift = $this->shiftService->currentShift(auth()->user(), $outlet->id);
        $summary = $shift ? $this->shiftService->shiftSummary($shift) : null;

        return view('pos.shift.index', compact('outlet', 'shift', 'summary'));
    }

    public function open(Request $request)
    {
        $outlet = current_outlet();

        $data = $request->validate([
            'opening_float' => 'nullable|integer|min:0',
        ]);

        try {
            $this->shiftService->openShift(
                auth()->user(),
                $outlet->id,
                $data['opening_float'] ?? 0
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Shift kasir dibuka.');
    }

    public function close(Request $request, CashierShift $shift)
    {
        abort_unless($shift->outlet_id === current_outlet_id(), 403);
        abort_unless($shift->user_id === auth()->id() && $shift->isOpen(), 403);

        $data = $request->validate([
            'closing_cash' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->shiftService->closeShift($shift, $data['closing_cash'], $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.shift.show')
            ->with('success', 'Shift ditutup. Selisih kas: Rp ' . number_format($shift->fresh()->cash_difference ?? 0, 0, ',', '.'));
    }
}
