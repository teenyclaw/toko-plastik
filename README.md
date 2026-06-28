# Toko Plastik — POS Laravel

Aplikasi POS untuk toko plastik & bahan kue. Stack: **Laravel 10 + MySQL + Blade + Breeze**.

Deploy compatible dengan shared hosting NusantaraHost (sama pola **qr-pos**).

## Fase 0 (selesai)

- [x] Laravel 10 + auth login
- [x] Role: Owner, Kasir, Gudang
- [x] Layout admin + sidebar per role
- [x] Database `toko_plastik` + seeder demo

## Fase 1 (selesai)

- [x] Master kategori, satuan, produk (CRUD)
- [x] Kasir POS — cari/scan barcode, keranjang, checkout
- [x] Pembayaran tunai & tempo (pelanggan)
- [x] Stok otomatis berkurang + riwayat `stock_movements`
- [x] Cetak struk thermal/browser
- [x] Dashboard statistik penjualan hari ini

## Fase 2 (selesai)

- [x] Master supplier (CRUD, saldo hutang)
- [x] Pembelian barang — stok masuk otomatis, update harga beli
- [x] Pembayaran tunai & tempo ke supplier
- [x] Halaman stok — filter menipis, penyesuaian (masuk/keluar/opname)
- [x] Riwayat pergerakan stok

## Fase 3 (selesai)

- [x] Halaman keuangan — piutang pelanggan & hutang supplier
- [x] Catat pelunasan piutang/hutang (tunai/transfer)
- [x] Riwayat pembayaran (`payments`)
- [x] Beban operasional (CRUD + filter tanggal)
- [x] Otomatis catat payment saat transaksi tempo

## Fase 4 (selesai)

- [x] Laporan penjualan, pembelian, terlaris, stok menipis, laba rugi
- [x] Filter tanggal + export CSV + cetak
- [x] Pengaturan toko (nama, alamat, WA, footer struk)
- [x] Manajemen user (CRUD, role, aktif/nonaktif)

## Fase 5 (selesai)

- [x] Panduan deploy [docs/HOSTING.md](docs/HOSTING.md) + [docs/INSTALLATION.md](docs/INSTALLATION.md)
- [x] Skrip deploy & diagnosa (`scripts/`)
- [x] Health check `/up`
- [x] Konfigurasi production (HTTPS, timezone Asia/Jakarta)

## Instalasi (Laragon)

```bash
cd c:\laragon\www\toko-plastik
copy .env.example .env
# Edit .env: DB_DATABASE=toko_plastik, APP_NAME="Toko Plastik"

php artisan key:generate
php artisan migrate:fresh --seed
```

Buat database MySQL:

```sql
CREATE DATABASE toko_plastik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Map virtual host: **toko-plastik.test** (Laragon → Map to toko-plastik.test)

Buka http://toko-plastik.test/login

## Akun demo

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@toko.com | password |
| Kasir | kasir@toko.com | password |
| Gudang | gudang@toko.com | password |

## Roadmap

| Fase | Modul |
|------|-------|
| 1 | POS, produk, kategori, satuan ✅ |
| 2 | Pembelian, stok, supplier ✅ |
| 3 | Piutang/hutang, beban ✅ |
| 4 | Laporan, settings, users ✅ |
| 5 | Deploy production ✅ |

Blueprint fitur & ERD: lihat project **pos-toko** (`docs/ERD.md`). Instalasi lokal: [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Deploy (shared hosting)

Ringkas — detail lengkap di **[docs/HOSTING.md](docs/HOSTING.md)**:

```bat
REM Di PC (Laragon)
scripts\prepare-upload.bat
REM Upload folder ke server, buat .env, set docroot ke public/

REM Di server (SSH / Terminal)
bash scripts/deploy-on-server.sh
php artisan db:seed --force   REM sekali saja, opsional
```

Health check: `https://domain-anda.com/up`

Document root → folder `public/`
