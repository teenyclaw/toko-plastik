<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftHistoryController extends Controller
{
    public function __construct(private ShiftService $shiftService) {}

    public function index(Request $request)
    {
        $outlet = current_outlet();

        $shifts = CashierShift::where('outlet_id', $outlet->id)
            ->with('user')
            ->when($request->get('status') === 'open', fn ($q) => $q->where('status', CashierShift::STATUS_OPEN))
            ->when($request->get('status') === 'closed', fn ($q) => $q->where('status', CashierShift::STATUS_CLOSED))
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.shifts.index', compact('outlet', 'shifts'));
    }

    public function show(CashierShift $shift)
    {
        abort_unless($shift->outlet_id === current_outlet_id(), 403);

        $shift->load(['user', 'outlet', 'payments.order']);
        $summary = $this->shiftService->shiftSummary($shift);

        return view('admin.shifts.show', compact('shift', 'summary'));
    }
}
