# Instalasi Lokal (Laragon)

Panduan setup **toko-plastik** di komputer development.

## Persyaratan

| Komponen | Versi |
|----------|-------|
| PHP | 8.1+ (Laragon 8.x) |
| MySQL | 5.7+ / 8.0 |
| Composer | 2.x |

## Langkah

```bash
cd c:\laragon\www\toko-plastik
copy .env.example .env
```

Edit `.env`:

```env
APP_URL=http://toko-plastik.test
DB_DATABASE=toko_plastik
DB_USERNAME=root
DB_PASSWORD=
```

Buat database:

```sql
CREATE DATABASE toko_plastik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Jalankan:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
```

## Virtual host

Laragon → **Menu → Apache → Sites Directory** → pastikan folder `toko-plastik` ada.

Laragon → **Menu → Quick add → Map to toko-plastik.test**

Buka: http://toko-plastik.test/login

## Akun demo (seeder)

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@toko.com | password |
| Kasir | kasir@toko.com | password |
| Gudang | gudang@toko.com | password |

## Perintah berguna

```bash
php artisan serve                    # alternatif tanpa vhost
php artisan migrate:fresh --seed     # reset database
php scripts/diagnose-500.php         # cek error bootstrap
php scripts/verify-deploy.php        # cek kelengkapan file deploy
```

## Deploy production

Lihat [HOSTING.md](./HOSTING.md).
