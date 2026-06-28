# Panduan Hosting — Toko Plastik (Laravel)

Deploy aplikasi **POS Toko Plastik & Bahan Kue** ke internet.

Stack: **Laravel 10 + PHP + MySQL** — cocok untuk **shared hosting** NusantaraHost / cPanel (sama pola **qr-pos**).

> **Catatan:** Project Next.js **pos-toko** membutuhkan Node.js. Hosting shared **gowatallo** tidak support Node. Gunakan **toko-plastik** (Laravel) untuk domain tersebut.

---

## Persyaratan server

| Komponen | Minimum |
|----------|---------|
| PHP | 8.1+ dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo` |
| MySQL | 5.7+ / MariaDB 10.3+ |
| Composer | Di server (SSH/Terminal) **atau** upload folder `vendor/` dari PC |
| Document root | Folder `public/` (disarankan) |

---

## Opsi A: Shared hosting (NusantaraHost / cPanel) — Recommended

### 1. Siapkan di komputer (Laragon)

```bat
cd c:\laragon\www\toko-plastik
scripts\prepare-upload.bat
```

Atau manual:

```bash
composer install --no-dev --optimize-autoloader --no-scripts
composer dump-autoload --optimize --no-scripts
```

### 2. Upload ke server

Upload **seluruh folder project** via FTP / File Manager / Git:

```
/home/username/toko-plastik/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← document root harus mengarah ke sini
├── resources/
├── routes/
├── storage/
├── vendor/          ← wajib ada (upload atau composer di server)
├── .env             ← buat di server, JANGAN commit
├── artisan
└── composer.json
```

**Jangan upload:** `.git/`, `node_modules/`, `.env` dari lokal (buat baru di server).

### 3. Buat database MySQL

Di cPanel → **MySQL Databases**:

1. Buat database: `username_tokoplasik`
2. Buat user + password
3. Assign user ke database (ALL PRIVILEGES)

### 4. File `.env` di server

Salin `.env.example` → `.env`, edit:

```env
APP_NAME="Toko Plastik"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...          # generate di server
APP_URL=https://toko.domain-anda.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_tokoplasik
DB_USERNAME=username_dbuser
DB_PASSWORD=password_db

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Generate `APP_KEY` (SSH / Terminal cPanel):

```bash
cd ~/toko-plastik
php artisan key:generate
```

### 5. Set document root ke `public/`

**cPanel → Domains → domain Anda → Document Root:**

```
/home/username/toko-plastik/public
```

Simpan. Tunggu 1–2 menit.

### 6. Permission folder

```bash
chmod -R 775 storage bootstrap/cache
```

### 7. Migrate & optimize

```bash
cd ~/toko-plastik
bash scripts/deploy-on-server.sh
```

Atau manual:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 8. Seed data (sekali saja, opsional)

```bash
php artisan db:seed --force
```

**Penting:** Ganti password demo setelah go-live (menu **Pengguna**).

### 9. Verifikasi

```bash
php scripts/verify-deploy.php
php scripts/diagnose-500.php
curl -s https://toko.domain-anda.com/up
```

Respon `/up` yang sehat:

```json
{"status":"ok","app":"Toko Plastik","checks":{"app":"ok","database":"ok"},"time":"..."}
```

Buka `/login` → login owner → test POS + laporan.

---

## Opsi B: Document root tidak bisa diubah

Jika hosting **hanya** mengizinkan `public_html/` sebagai root:

### B1 — Symlink (jika SSH allowed)

```bash
cd ~
rm -rf public_html
ln -s toko-plastik/public public_html
```

### B2 — Redirect index.php

1. Upload Laravel ke `~/toko-plastik/`
2. Copy `deploy/public_html-index.php.example` → `public_html/index.php`
3. Sesuaikan path `$laravelPublic`

### B3 — Rewrite ke subfolder public

Copy `deploy/public_html-htaccess.example` → root `.htaccess` (jika app di root domain folder).

---

## Opsi C: VPS (Ubuntu + Nginx)

Untuk kontrol penuh (DigitalOcean, Vultr, IDCloudHost VPS):

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl composer git

sudo mysql -e "CREATE DATABASE toko_plastik; CREATE USER 'toko'@'localhost' IDENTIFIED BY 'password_kuat'; GRANT ALL ON toko_plastik.* TO 'toko'@'localhost';"

cd /var/www
git clone <repo-url> toko-plastik
cd toko-plastik
cp .env.example .env
# edit .env ...
composer install --no-dev --optimize-autoloader --no-scripts
composer dump-autoload --optimize --no-scripts
php artisan key:generate
bash scripts/deploy-on-server.sh
php artisan db:seed --force

sudo chown -R www-data:www-data storage bootstrap/cache
```

Nginx server block (document root = `public/`):

```nginx
server {
    listen 80;
    server_name toko.domain.com;
    root /var/www/toko-plastik/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

SSL: `sudo certbot --nginx -d toko.domain.com`

---

## Update setelah deploy

```bash
cd ~/toko-plastik
git pull   # jika pakai git
composer install --no-dev --optimize-autoloader --no-scripts
composer dump-autoload --optimize --no-scripts --no-scripts
composer dump-autoload --optimize --no-scripts
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Backup database

```bash
mysqldump -u USER -p DATABASE > backup_$(date +%Y%m%d).sql
```

Restore:

```bash
mysql -u USER -p DATABASE < backup_20260628.sql
```

---

## Checklist go-live

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `APP_URL` = URL HTTPS final
- [ ] `APP_KEY` sudah di-generate
- [ ] Password demo diganti / user demo dihapus
- [ ] `/up` return status ok
- [ ] Login, POS, cetak struk, laporan berfungsi
- [ ] `storage/` dan `bootstrap/cache/` writable
- [ ] Backup database dijadwalkan (cPanel / cron)

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| HTTP 500 | `php scripts/diagnose-500.php`; cek `storage/logs/laravel.log` |
| Blank / 403 | Document root harus `public/`; cek `.htaccess` |
| `vendor/` missing | `composer install --no-dev --no-scripts` di server atau upload dari PC |
| Composer gagal `proc_open` | Pakai flag `--no-scripts`, lalu `composer dump-autoload --optimize --no-scripts`. File `bootstrap/cache/packages.php` sudah disertakan di repo |
| Login loop / session hilang | `APP_URL` harus exact match (http vs https); `SESSION_DRIVER=file` |
| CSS/JS 404 | Pastikan docroot = `public/`; jangan cache config sebelum `.env` benar |
| Database error | Cek kredensial DB di cPanel; host biasanya `localhost` |
| `No application encryption key` | `php artisan key:generate` |
| Permission denied storage | `chmod -R 775 storage bootstrap/cache` |
| Route not found setelah cache | `php artisan route:clear` lalu `route:cache` ulang |

### Mode maintenance

```bash
php artisan down --secret="token-rahasia"
# Akses: https://domain.com/token-rahasia
php artisan up
```

---

## Perbandingan dengan pos-toko (Next.js)

| | toko-plastik (Laravel) | pos-toko (Next.js) |
|--|------------------------|---------------------|
| Shared hosting PHP | ✅ | ❌ (butuh Node.js) |
| gowatallo / NusantaraHost | ✅ | ❌ |
| Vercel + Railway | ✅ (opsional VPS) | ✅ |

Untuk domain **pos-toko.ylabsdev1980.com** di gowatallo: deploy **toko-plastik**, bukan pos-toko.
