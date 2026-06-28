<?php

namespace App\Services;

use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Outlet;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function settings(Outlet $outlet): LoyaltySetting
    {
        return LoyaltySetting::firstOrCreate(
            ['outlet_id' => $outlet->id],
            LoyaltySetting::defaultsFor($outlet->id)
        );
    }

    public function isEnabled(Outlet $outlet): bool
    {
        return $this->settings($outlet)->is_enabled;
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    public function isEligiblePhone(?string $phone): bool
    {
        if ($phone === null || $phone === '' || $phone === '-') {
            return false;
        }

        $normalized = $this->normalizePhone($phone);

        return strlen($normalized) >= 10 && strlen($normalized) <= 15;
    }

    public function findMember(Outlet $outlet, string $phone): ?Member
    {
        if (! $this->isEligiblePhone($phone)) {
            return null;
        }

        return Member::where('outlet_id', $outlet->id)
            ->where('phone', $this->normalizePhone($phone))
            ->first();
    }

    public function findOrCreateMember(Outlet $outlet, string $phone, ?string $name = null): Member
    {
        if (! $this->isEligiblePhone($phone)) {
            throw new \RuntimeException('Nomor telepon tidak valid untuk program loyalty.');
        }

        $normalized = $this->normalizePhone($phone);

        return Member::firstOrCreate(
            ['outlet_id' => $outlet->id, 'phone' => $normalized],
            ['name' => $name, 'points' => 0]
        );
    }

    public function calculateEarnPoints(int $amountPaid, LoyaltySetting $settings): int
    {
        if ($amountPaid < 1 || $settings->earn_amount_basis < 1) {
            return 0;
        }

        return (int) floor($amountPaid / $settings->earn_amount_basis) * (int) $settings->earn_points;
    }

    public function calculateDiscount(int $points, LoyaltySetting $settings): int
    {
        if ($points < 1) {
            return 0;
        }

        return $points * (int) $settings->redeem_rp_per_point;
    }

    public function maxRedeemPoints(Member $member, int $paymentSubtotal, LoyaltySetting $settings): int
    {
        if ($paymentSubtotal < 1 || $member->points < $settings->min_redeem_points) {
            return 0;
        }

        $capByPercent = (int) floor($paymentSubtotal * $settings->max_redeem_percent / 100);
        $maxByPercent = (int) floor($capByPercent / max(1, $settings->redeem_rp_per_point));

        return min($member->points, $maxByPercent);
    }

    /** @return array{points: int, discount: int} */
    public function validateRedemption(Order $order, int $requestedPoints, int $paymentSubtotal): array
    {
        $outlet = $order->outlet;
        $settings = $this->settings($outlet);

        if (! $settings->is_enabled) {
            throw new \RuntimeException('Program loyalty tidak aktif di cabang ini.');
        }

        if ($requestedPoints < 1) {
            return ['points' => 0, 'discount' => 0];
        }

        if (! $this->isEligiblePhone($order->customer_phone)) {
            throw new \RuntimeException('Order ini tidak punya nomor telepon member yang valid.');
        }

        $member = $this->findOrCreateMember($outlet, $order->customer_phone, $order->customer_name);

        if ($requestedPoints < $settings->min_redeem_points) {
            throw new \RuntimeException('Minimal redeem ' . $settings->min_redeem_points . ' poin.');
        }

        if ($requestedPoints > $member->points) {
            throw new \RuntimeException('Saldo poin tidak cukup. Tersedia: ' . $member->points . ' poin.');
        }

        $maxPoints = $this->maxRedeemPoints($member, $paymentSubtotal, $settings);

        if ($requestedPoints > $maxPoints) {
            throw new \RuntimeException('Maksimal redeem untuk transaksi ini: ' . $maxPoints . ' poin.');
        }

        $discount = $this->calculateDiscount($requestedPoints, $settings);

        if ($discount >= $paymentSubtotal) {
            throw new \RuntimeException('Diskon poin tidak boleh melebihi atau sama dengan total tagihan.');
        }

        return ['points' => $requestedPoints, 'discount' => $discount];
    }

    public function redeemForPayment(
        Order $order,
        OrderPayment $payment,
        int $points,
        int $discount
    ): void {
        if ($points < 1) {
            return;
        }

        if (LoyaltyTransaction::where('order_payment_id', $payment->id)
            ->where('type', LoyaltyTransaction::TYPE_REDEEM)
            ->exists()) {
            return;
        }

        $member = $this->findOrCreateMember($order->outlet, $order->customer_phone, $order->customer_name);

        DB::transaction(function () use ($member, $order, $payment, $points, $discount) {
            $locked = Member::query()->lockForUpdate()->findOrFail($member->id);

            if ($locked->points < $points) {
                throw new \RuntimeException('Saldo poin tidak cukup.');
            }

            $locked->points -= $points;
            $locked->save();

            LoyaltyTransaction::create([
                'member_id' => $locked->id,
                'type' => LoyaltyTransaction::TYPE_REDEEM,
                'points' => -$points,
                'amount_basis' => $discount,
                'order_id' => $order->id,
                'order_payment_id' => $payment->id,
                'user_id' => auth()->id(),
                'notes' => 'Redeem saat pembayaran',
                'balance_after' => $locked->points,
            ]);
        });
    }

    public function earnForPayment(OrderPayment $payment): void
    {
        $payment->loadMissing('order.outlet');
        $order = $payment->order;
        $settings = $this->settings($order->outlet);

        if (! $settings->is_enabled || ! $this->isEligiblePhone($order->customer_phone)) {
            return;
        }

        if (LoyaltyTransaction::where('order_payment_id', $payment->id)
            ->where('type', LoyaltyTransaction::TYPE_EARN)
            ->exists()) {
            return;
        }

        $earnBasis = max(0, (int) $payment->amount);
        $points = $this->calculateEarnPoints($earnBasis, $settings);

        if ($points < 1) {
            return;
        }

        $member = $this->findOrCreateMember($order->outlet, $order->customer_phone, $order->customer_name);

        DB::transaction(function () use ($member, $order, $payment, $points, $earnBasis) {
            $locked = Member::query()->lockForUpdate()->findOrFail($member->id);
            $locked->points += $points;
            $locked->save();

            LoyaltyTransaction::create([
                'member_id' => $locked->id,
                'type' => LoyaltyTransaction::TYPE_EARN,
                'points' => $points,
                'amount_basis' => $earnBasis,
                'order_id' => $order->id,
                'order_payment_id' => $payment->id,
                'notes' => 'Earn dari pembayaran',
                'balance_after' => $locked->points,
            ]);
        });
    }

    public function adjust(Member $member, int $newBalance, ?string $notes = null): Member
    {
        if ($newBalance < 0) {
            throw new \RuntimeException('Saldo poin tidak boleh negatif.');
        }

        return DB::transaction(function () use ($member, $newBalance, $notes) {
            $locked = Member::query()->lockForUpdate()->findOrFail($member->id);
            $diff = $newBalance - (int) $locked->points;

            if ($diff === 0) {
                return $locked;
            }

            $locked->points = $newBalance;
            $locked->save();

            LoyaltyTransaction::create([
                'member_id' => $locked->id,
                'type' => LoyaltyTransaction::TYPE_ADJUST,
                'points' => $diff,
                'user_id' => auth()->id(),
                'notes' => $notes,
                'balance_after' => $locked->points,
            ]);

            return $locked->fresh();
        });
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Member> */
    public function membersForOutlet(int $outletId, ?string $search = null)
    {
        return Member::query()
            ->where('outlet_id', $outletId)
            ->when($search, function ($q, $search) {
                $normalized = $this->normalizePhone($search);
                $q->where(function ($q2) use ($search, $normalized) {
                    $q2->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $normalized . '%');
                });
            })
            ->orderByDesc('points')
            ->orderBy('phone')
            ->limit(50)
            ->get();
    }
}
