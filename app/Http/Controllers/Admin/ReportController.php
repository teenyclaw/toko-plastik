<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function index(Request $request): View|StreamedResponse
    {
        $type = $request->string('type', 'sales')->toString();
        $from = $request->date('from') ?? now()->startOfDay();
        $to = $request->date('to') ?? now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $report = $this->reportService->generate($type, $from, $to);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($report, $from, $to);
        }

        $tabs = [
            'sales' => 'Penjualan',
            'purchases' => 'Pembelian',
            'best-sellers' => 'Terlaris',
            'low-stock' => 'Stok Menipis',
            'profit-loss' => 'Laba Rugi',
        ];

        return view('admin.reports.index', compact('report', 'type', 'from', 'to', 'tabs'));
    }

    private function exportCsv(array $report, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = "laporan-{$report['type']}-{$from->format('Y-m-d')}.csv";

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            match ($report['type']) {
                'sales' => $this->csvSales($out, $report),
                'purchases' => $this->csvPurchases($out, $report),
                'best-sellers' => $this->csvBestSellers($out, $report),
                'low-stock' => $this->csvLowStock($out, $report),
                'profit-loss' => $this->csvProfitLoss($out, $report),
                default => fputcsv($out, ['Tidak ada data']),
            };

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvSales($out, array $report): void
    {
        fputcsv($out, ['Invoice', 'Tanggal', 'Pelanggan', 'Kasir', 'Metode', 'Total']);
        foreach ($report['rows'] as $row) {
            fputcsv($out, [
                $row['invoice'],
                $row['date']->format('d/m/Y H:i'),
                $row['party'],
                $row['cashier'],
                $row['method'],
                $row['total'],
            ]);
        }
    }

    private function csvPurchases($out, array $report): void
    {
        fputcsv($out, ['Invoice', 'Tanggal', 'Supplier', 'Metode', 'Total']);
        foreach ($report['rows'] as $row) {
            fputcsv($out, [
                $row['invoice'],
                $row['date']->format('d/m/Y H:i'),
                $row['party'],
                $row['method'],
                $row['total'],
            ]);
        }
    }

    private function csvBestSellers($out, array $report): void
    {
        fputcsv($out, ['Produk', 'Qty Terjual', 'Total Penjualan']);
        foreach ($report['rows'] as $row) {
            fputcsv($out, [$row['name'], $row['qty'], $row['total']]);
        }
    }

    private function csvLowStock($out, array $report): void
    {
        fputcsv($out, ['Kode', 'Produk', 'Kategori', 'Stok', 'Min', 'Satuan']);
        foreach ($report['rows'] as $row) {
            fputcsv($out, [$row['code'], $row['name'], $row['category'], $row['stock'], $row['min_stock'], $row['unit']]);
        }
    }

    private function csvProfitLoss($out, array $report): void
    {
        fputcsv($out, ['Pendapatan', $report['summary']['revenue']]);
        fputcsv($out, ['HPP', $report['summary']['cogs']]);
        fputcsv($out, ['Laba Kotor', $report['summary']['gross_profit']]);
        fputcsv($out, ['Beban Operasional', $report['summary']['expenses']]);
        fputcsv($out, ['Laba Bersih', $report['summary']['net_profit']]);
        fputcsv($out, []);
        fputcsv($out, ['Detail Beban', 'Kategori', 'Tanggal', 'Nominal', 'User']);
        foreach ($report['rows'] as $row) {
            fputcsv($out, [$row['title'], $row['category'], $row['date']->format('d/m/Y'), $row['amount'], $row['user']]);
        }
    }
}
