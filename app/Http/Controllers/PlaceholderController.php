<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function __invoke(string $module): View
    {
        $titles = [
            'pos' => 'Kasir POS',
            'products' => 'Produk',
            'purchases' => 'Pembelian',
            'stock' => 'Stok',
            'finance' => 'Keuangan',
            'reports' => 'Laporan',
            'users' => 'Pengguna',
            'settings' => 'Pengaturan',
        ];

        $phases = [
            'pos' => 1,
            'products' => 1,
            'purchases' => 2,
            'stock' => 2,
            'finance' => 3,
            'reports' => 4,
            'users' => 4,
            'settings' => 4,
        ];

        return view('placeholder', [
            'title' => $titles[$module] ?? ucfirst($module),
            'phase' => $phases[$module] ?? null,
            'module' => $module,
        ]);
    }
}
