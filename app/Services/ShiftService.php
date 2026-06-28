<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\OrderPayment;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function currentShift(User $user, ?int $outletId = null): ?CashierShift
    {
        $outletId ??= auth()->check() ? current_outlet_id() : Outlet::query()->value('id');

        return CashierShift::query()
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->where('status', CashierShift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();
    }

    public function openShift(User $user, int $outletId, int $openingFloat = 0): CashierShift
    {
        if ($this->currentShift($user, $outletId)) {
            throw new \RuntimeException('Shift masih terbuka. Tutup shift dulu sebelum buka yang baru.');
        }

        return CashierShift::create([
            'outlet_id' => $outletId,
            'user_id' => $user->id,
            'opening_float' => $openingFloat,
            'status' => CashierShift::STATUS_OPEN,
            'opened_at' => now(),
        ]);
    }

    public function closeShift(CashierShift $shift, int $closingCash, ?string $notes = null): CashierShift
    {
        if (! $shift->isOpen()) {
            throw new \RuntimeException('Shift sudah ditutup.');
        }

        $summary = $this->shiftSummary($shift);
        $expected = $summary['expected_cash_in_drawer'];
        $difference = $closingCash - $expected;

        $shift->update([
            'closing_cash' => $closingCash,
            'expected_cash' => $expected,
            'cash_difference' => $difference,
            'notes' => $notes,
            'status' => CashierShift::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        return $shift->fresh();
    }

    public function shiftSummary(CashierShift $shift): array
    {
        $payments = OrderPayment::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('status', OrderPayment::STATUS_PAID)
            ->get();

        $cashTotal = (int) $payments->where('payment_method', 'cash')->sum('amount');
        $transferTotal = (int) $payments->where('payment_method', 'transfer')->sum('amount');
        $qrisTotal = (int) $payments->where('payment_method', 'qris')->sum('amount');
        $totalRevenue = (int) $payments->sum('amount');

        $expectedCash = $shift->opening_float + $cashTotal;

        return [
            'payment_count' => $payments->count(),
            'cash_total' => $cashTotal,
            'transfer_total' => $transferTotal,
            'qris_total' => $qrisTotal,
            'total_revenue' => $totalRevenue,
            'expected_cash_in_drawer' => $expectedCash,
        ];
    }

    public function requireOpenShift(User $user, ?int $outletId = null): void
    {
        if (! config('pos.require_open_shift', true)) {
            return;
        }

        if (! $this->currentShift($user, $outletId)) {
            throw new \RuntimeException('Buka shift kasir terlebih dahulu sebelum menerima pembayaran.');
        }
    }
}
