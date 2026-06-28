<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function collectReceivable(Customer $customer, float $amount, PaymentMethod $method, ?string $notes = null): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal harus lebih dari 0.']);
        }

        if ((float) $customer->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal melebihi piutang (saldo: '.format_rupiah($customer->balance).').',
            ]);
        }

        DB::transaction(function () use ($customer, $amount, $method, $notes) {
            $customer->decrement('balance', $amount);

            Payment::query()->create([
                'type' => PaymentType::Receivable,
                'amount' => -$amount,
                'method' => $method,
                'date' => now(),
                'notes' => $notes ?? 'Pelunasan piutang',
                'customer_id' => $customer->id,
                'created_at' => now(),
            ]);
        });
    }

    public function collectPayable(Supplier $supplier, float $amount, PaymentMethod $method, ?string $notes = null): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal harus lebih dari 0.']);
        }

        if ((float) $supplier->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal melebihi hutang (saldo: '.format_rupiah($supplier->balance).').',
            ]);
        }

        DB::transaction(function () use ($supplier, $amount, $method, $notes) {
            $supplier->decrement('balance', $amount);

            Payment::query()->create([
                'type' => PaymentType::Payable,
                'amount' => -$amount,
                'method' => $method,
                'date' => now(),
                'notes' => $notes ?? 'Pelunasan hutang supplier',
                'supplier_id' => $supplier->id,
                'created_at' => now(),
            ]);
        });
    }
}
