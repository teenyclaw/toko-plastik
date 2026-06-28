<?php

namespace App\Http\Controllers\Finance;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(private FinanceService $financeService)
    {
    }

    public function index(): View
    {
        $receivables = Customer::query()
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();

        $payables = Supplier::query()
            ->active()
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();

        $recentPayments = Payment::query()
            ->with(['customer', 'supplier'])
            ->latest('date')
            ->limit(20)
            ->get();

        $monthExpenses = Expense::query()
            ->where('date', '>=', now()->startOfMonth())
            ->sum('amount');

        return view('finance.index', [
            'receivables' => $receivables,
            'payables' => $payables,
            'recentPayments' => $recentPayments,
            'totalReceivables' => $receivables->sum('balance'),
            'totalPayables' => $payables->sum('balance'),
            'monthExpenses' => $monthExpenses,
        ]);
    }

    public function collect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'collect_type' => ['required', 'in:customer,supplier'],
            'customer_id' => ['required_if:collect_type,customer', 'nullable', 'integer', 'exists:customers,id'],
            'supplier_id' => ['required_if:collect_type,supplier', 'nullable', 'integer', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:cash,transfer'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $method = PaymentMethod::from($data['method']);

        if ($data['collect_type'] === 'customer') {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $this->financeService->collectReceivable($customer, (float) $data['amount'], $method, $data['notes'] ?? null);
        } else {
            $supplier = Supplier::query()->findOrFail($data['supplier_id']);
            $this->financeService->collectPayable($supplier, (float) $data['amount'], $method, $data['notes'] ?? null);
        }

        return back()->with('success', 'Pelunasan berhasil dicatat.');
    }
}
