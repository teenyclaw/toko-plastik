<?php



return [

    'navigation' => [

        ['label' => 'Dashboard', 'route' => 'dashboard', 'roles' => ['owner', 'kasir', 'gudang']],

        ['label' => 'Kasir POS', 'route' => 'pos.index', 'roles' => ['owner', 'kasir'], 'match' => 'pos.*'],

        ['label' => 'Produk', 'route' => 'products.index', 'roles' => ['owner', 'gudang'], 'match' => 'products.*'],

        ['label' => 'Kategori', 'route' => 'categories.index', 'roles' => ['owner', 'gudang'], 'match' => 'categories.*'],

        ['label' => 'Satuan', 'route' => 'units.index', 'roles' => ['owner', 'gudang'], 'match' => 'units.*'],

        ['label' => 'Supplier', 'route' => 'suppliers.index', 'roles' => ['owner', 'gudang'], 'match' => 'suppliers.*'],

        ['label' => 'Pembelian', 'route' => 'purchases.index', 'roles' => ['owner', 'gudang'], 'match' => 'purchases.*'],

        ['label' => 'Stok', 'route' => 'stock.index', 'roles' => ['owner', 'gudang'], 'match' => 'stock.*'],

        ['label' => 'Keuangan', 'route' => 'finance.index', 'roles' => ['owner'], 'match' => 'finance.*'],

        ['label' => 'Beban Operasional', 'route' => 'expenses.index', 'roles' => ['owner'], 'match' => 'expenses.*'],

        ['label' => 'Laporan', 'route' => 'reports.index', 'roles' => ['owner'], 'match' => 'reports.*'],

        ['label' => 'Pengguna', 'route' => 'users.index', 'roles' => ['owner'], 'match' => 'users.*'],

        ['label' => 'Pengaturan', 'route' => 'settings.index', 'roles' => ['owner'], 'match' => 'settings.*'],
    ],

    'store_settings' => [
        'store_name' => ['label' => 'Nama Toko', 'default' => 'Toko Plastik & Bahan Kue'],
        'store_address' => ['label' => 'Alamat', 'default' => ''],
        'store_whatsapp' => ['label' => 'WhatsApp (62...)', 'default' => ''],
        'receipt_footer' => ['label' => 'Footer Struk', 'default' => 'Terima kasih atas kunjungan Anda!'],
    ],
];

