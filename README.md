# inventory-api

REST API inventori barang yang dibangun dengan **Yii2**. API ini mengelola data produk, mencatat stok masuk dan keluar beserta riwayatnya, memberi peringatan stok menipis, dan memakai autentikasi token Bearer.

## Fitur
- CRUD produk dengan validasi: SKU unik, harga dan stok tidak boleh negatif
- Pencarian `?q=`, filter `?category=`, sorting `?sort=-stock`, dan paginasi
- Stok masuk/keluar memakai satu `UPDATE` bersyarat di dalam transaksi, sehingga stok tidak pernah minus walau ada request bersamaan
- Stok hanya bisa diubah lewat endpoint stok, jadi riwayatnya selalu cocok dengan stok saat ini
- Riwayat pergerakan stok per produk, lengkap dengan user yang mencatat
- Daftar produk dengan stok menipis (`stock <= min_stock`)
- Login dengan token yang disimpan dalam bentuk hash SHA-256 dan punya masa berlaku
- Dokumentasi OpenAPI di [`docs/openapi.yaml`](docs/openapi.yaml), bisa dibuka di [Swagger Editor](https://editor.swagger.io/)
- Database default SQLite, bisa diganti MySQL lewat environment variable

## Menjalankan
Butuh PHP 8.1 atau lebih baru dengan extension `pdo_sqlite` (atau `pdo_mysql`), dan Composer.
```bash
git clone https://github.com/wisnu-agustya2/inventory-api.git
cd inventory-api
composer install
php yii migrate --interactive=0
php yii user/create admin rahasia123
php -S localhost:8080 -t web web/index.php
```

Untuk memakai MySQL:
```bash
export DB_DSN="mysql:host=127.0.0.1;dbname=inventory" DB_USER=root DB_PASS=secret
php yii migrate --interactive=0
```
Set `YII_DEBUG=1` untuk melihat detail error saat development.

## Endpoint
| Method | Path | Keterangan |
|---|---|---|
| GET | `/health` | Cek server (tanpa token) |
| POST | `/auth/login` | `{ "username", "password" }` → token |
| GET | `/products` | Daftar produk (`q`, `category`, `sort`, `page`, `per-page`) |
| POST | `/products` | Tambah produk |
| GET | `/products/low-stock` | Produk dengan stok menipis |
| GET | `/products/{id}` | Detail produk |
| PUT | `/products/{id}` | Ubah produk (kecuali stok) |
| DELETE | `/products/{id}` | Hapus produk beserta riwayatnya |
| POST | `/products/{id}/stock-in` | `{ "quantity", "note" }` |
| POST | `/products/{id}/stock-out` | `{ "quantity", "note" }`, ditolak jika stok kurang |
| GET | `/products/{id}/movements` | Riwayat stok |

## Contoh
```bash
TOKEN=$(curl -s -X POST localhost:8080/auth/login -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"rahasia123"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')

curl -X POST localhost:8080/products -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"sku":"LPT-001","name":"Laptop Pro 14","category":"Elektronik","price":15499000,"stock":5,"min_stock":3}'

curl -X POST localhost:8080/products/1/stock-out -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"quantity":2,"note":"Penjualan"}'
```

## Tes
Smoke test end-to-end dengan curl (30 pemeriksaan). Jalankan saat server hidup:
```bash
BASE_URL=http://localhost:8080 USERNAME=admin PASSWORD=rahasia123 bash tests/smoke.sh
```

## Struktur
```
commands/      perintah console (user/create)
config/        konfigurasi web, console, database
controllers/   AuthController, ProductController
migrations/    skema database
models/        User, Product, StockMovement, StockForm, LoginForm
docs/          OpenAPI spec
tests/         smoke test
```

## Lisensi
MIT
