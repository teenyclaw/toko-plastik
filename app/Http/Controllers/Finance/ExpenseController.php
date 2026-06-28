<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $query = Expense::query()->with('user')->latest('date');

        if ($from) {
            $query->where('date', '>=', $from->startOfDay());
        }
        if ($to) {
            $query->where('date', '<=', $to->endOfDay());
        }

        $totalAmount = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->withQueryString();

        return view('finance.expenses.index', compact('expenses', 'from', 'to', 'totalAmount'));
    }

    public function create(): View
    {
        return view('finance.expenses.form', [
            'expense' => new Expense(['date' => now()]),
            'categories' => config('toko-data.expense_categories', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;

        Expense::query()->create($data);

        return redirect()->route('expenses.index')->with('success', 'Beban operasional ditambahkan.');
    }

    public function edit(Expense $expense): View
    {
        return view('finance.expenses.form', [
            'expense' => $expense,
            'categories' => config('toko-data.expense_categories', []),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Beban operasional diperbarui.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Beban operasional dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'category' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
