<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Owner Toko', 'email' => 'owner@toko.com', 'role' => UserRole::Owner],
            ['name' => 'Kasir Utama', 'email' => 'kasir@toko.com', 'role' => UserRole::Kasir],
            ['name' => 'Staff Gudang', 'email' => 'gudang@toko.com', 'role' => UserRole::Gudang],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'is_active' => true,
                ]
            );
        }

        $settings = [
            'store_name' => 'Toko Plastik & Bahan Kue Sejahtera',
            'store_address' => 'Jl. Pasar Induk No. 12, Jakarta',
            'store_whatsapp' => '6281234567890',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
        ];

        foreach ($settings as $key => $value) {
            DB::table('store_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        foreach (config('toko-data.default_units') as $unit) {
            Unit::query()->firstOrCreate(
                ['abbreviation' => $unit['abbreviation']],
                ['name' => $unit['name']]
            );
        }

        $unitMap = Unit::query()->pluck('id', 'abbreviation');

        foreach (config('toko-data.plastic_categories') as $name) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'type' => CategoryType::Plastik]
            );
        }

        foreach (config('toko-data.baking_categories') as $name) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'type' => CategoryType::BahanKue]
            );
        }

        $categories = Category::query()->get()->keyBy('slug');

        Customer::query()->firstOrCreate(
            ['name' => 'Toko Sederhana'],
            [
                'whatsapp' => '081234567890',
                'address' => 'Jakarta',
                'credit_limit' => 5000000,
                'balance' => 0,
            ]
        );

        Customer::query()->firstOrCreate(
            ['name' => 'Pelanggan Umum'],
            ['credit_limit' => 0, 'balance' => 0]
        );

        $supplierRecords = [
            ['name' => 'CV Plastik Jaya', 'contact' => '081234567890', 'address' => 'Jakarta Utara'],
            ['name' => 'PT Bahan Kue Nusantara', 'contact' => '081987654321', 'address' => 'Tangerang'],
            ['name' => 'UD Kemasan Makmur', 'contact' => '08111222333', 'address' => 'Bekasi'],
        ];

        $suppliers = [];
        foreach ($supplierRecords as $row) {
            $suppliers[] = Supplier::query()->firstOrCreate(['name' => $row['name']], $row);
        }

        $productsData = [
            ['code' => 'PLA00001', 'name' => 'Kantong Kresek Hitam Kecil', 'categorySlug' => 'kantong-kresek', 'unit' => 'Pcs', 'buy' => 3000, 'sell' => 5000, 'stock' => 500, 'min' => 100, 'supplier' => 0],
            ['code' => 'PLA00002', 'name' => 'Plastik Kiloan 24x35', 'categorySlug' => 'plastik-kiloan', 'unit' => 'Pack', 'buy' => 15000, 'sell' => 22000, 'stock' => 80, 'min' => 20, 'supplier' => 0],
            ['code' => 'PLA00003', 'name' => 'Mika Bulat Diameter 15cm', 'categorySlug' => 'mika', 'unit' => 'Pcs', 'buy' => 800, 'sell' => 1500, 'stock' => 200, 'min' => 50, 'supplier' => 2],
            ['code' => 'PLA00004', 'name' => 'Cup Plastik 12oz', 'categorySlug' => 'cup-plastik', 'unit' => 'Pcs', 'buy' => 350, 'sell' => 600, 'stock' => 1000, 'min' => 200, 'supplier' => 0],
            ['code' => 'PLA00005', 'name' => 'Sedotan Warna 100pcs', 'categorySlug' => 'sedotan', 'unit' => 'Pack', 'buy' => 5000, 'sell' => 8500, 'stock' => 60, 'min' => 15, 'supplier' => 2],
            ['code' => 'BAK00001', 'name' => 'Tepung Segitiga Biru 1Kg', 'categorySlug' => 'tepung', 'unit' => 'Kg', 'buy' => 12000, 'sell' => 15500, 'stock' => 45, 'min' => 10, 'supplier' => 1],
            ['code' => 'BAK00002', 'name' => 'Gula Pasir Premium', 'categorySlug' => 'gula', 'unit' => 'Kg', 'buy' => 14000, 'sell' => 17000, 'stock' => 100, 'min' => 25, 'supplier' => 1],
            ['code' => 'BAK00003', 'name' => 'Mentega Royal 200gr', 'categorySlug' => 'mentega', 'unit' => 'Pcs', 'buy' => 18000, 'sell' => 22000, 'stock' => 30, 'min' => 10, 'supplier' => 1],
            ['code' => 'BAK00004', 'name' => 'Coklat Batang Compound 1Kg', 'categorySlug' => 'coklat', 'unit' => 'Kg', 'buy' => 45000, 'sell' => 55000, 'stock' => 15, 'min' => 5, 'supplier' => 1],
            ['code' => 'BAK00005', 'name' => 'Baking Powder 100gr', 'categorySlug' => 'baking-powder', 'unit' => 'Pcs', 'buy' => 8000, 'sell' => 12000, 'stock' => 25, 'min' => 8, 'supplier' => 1],
        ];

        $owner = User::query()->where('email', 'owner@toko.com')->first();

        foreach ($productsData as $p) {
            $category = $categories->get($p['categorySlug']);
            if (! $category) {
                continue;
            }

            $barcodePrefix = str_starts_with($p['code'], 'PLA') ? '8991' : '8992';
            $barcode = $barcodePrefix.str_pad(substr($p['code'], 3), 8, '0', STR_PAD_LEFT);

            $product = Product::query()->updateOrCreate(
                ['code' => $p['code']],
                [
                    'barcode' => $barcode,
                    'name' => $p['name'],
                    'category_id' => $category->id,
                    'unit_id' => $unitMap[$p['unit']],
                    'buy_price' => $p['buy'],
                    'sell_price' => $p['sell'],
                    'stock' => $p['stock'],
                    'min_stock' => $p['min'],
                    'supplier_id' => $suppliers[$p['supplier']]->id ?? null,
                    'is_active' => true,
                ]
            );

            if ($owner && ! StockMovement::query()->where('product_id', $product->id)->exists()) {
                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $owner->id,
                    'type' => StockMovementType::In,
                    'quantity' => $p['stock'],
                    'stock_before' => 0,
                    'stock_after' => $p['stock'],
                    'notes' => 'Stok awal seeder',
                    'created_at' => now(),
                ]);
            }
        }
    }
}
