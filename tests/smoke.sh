#!/usr/bin/env bash
# Tes end-to-end API memakai curl.
# Pakai: BASE_URL=http://localhost:8080 USERNAME=admin PASSWORD=rahasia123 bash tests/smoke.sh
# Butuh: curl, php (untuk membaca JSON). Membuat dan menghapus data uji sendiri.
set -u
BASE_URL=${BASE_URL:-http://localhost:8080}
USERNAME=${USERNAME:-admin}
PASSWORD=${PASSWORD:-rahasia123}
PASS=0
FAIL=0

json() { php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach(explode(".",$argv[1]) as $k){$d=$d[$k]??null;} echo is_bool($d)?($d?"true":"false"):(is_array($d)?json_encode($d):$d);' "$1"; }

# req METHOD PATH [BODY] -> mengisi $STATUS dan $BODY
req() {
  local args=(-s -o /tmp/smoke_body -w '%{http_code}' -X "$1" "$BASE_URL$2" -H 'Content-Type: application/json')
  [ -n "${TOKEN:-}" ] && args+=(-H "Authorization: Bearer $TOKEN")
  [ $# -ge 3 ] && args+=(-d "$3")
  STATUS=$(curl "${args[@]}")
  BODY=$(cat /tmp/smoke_body)
}

check() {
  if [ "$2" = "$3" ]; then PASS=$((PASS + 1)); echo "  ok   $1";
  else FAIL=$((FAIL + 1)); echo "  FAIL $1 (diharapkan '$3', didapat '$2')"; echo "       $BODY"; fi
}

echo "Menguji $BASE_URL"
req GET /health;                                   check "health" "$STATUS" 200

TOKEN=
req GET /products;                                 check "tanpa token ditolak" "$STATUS" 401
TOKEN=token-palsu
req GET /products;                                 check "token palsu ditolak" "$STATUS" 401
TOKEN=

req POST /auth/login "{\"username\":\"$USERNAME\",\"password\":\"salah-password\"}"
check "password salah" "$STATUS" 401
req POST /auth/login '{}';                         check "login tanpa data" "$STATUS" 422
req POST /auth/login "{\"username\":\"$USERNAME\",\"password\":\"$PASSWORD\"}"
check "login berhasil" "$STATUS" 200
TOKEN=$(echo "$BODY" | json token)

SKU="TEST-$RANDOM$RANDOM"
req POST /products "{\"sku\":\"$SKU\",\"name\":\"Laptop Uji\",\"category\":\"Elektronik\",\"price\":7500000,\"stock\":5,\"min_stock\":3}"
check "buat produk" "$STATUS" 201
ID=$(echo "$BODY" | json id)
check "stok awal" "$(echo "$BODY" | json stock)" 5

req POST /products "{\"sku\":\"$SKU\",\"name\":\"Duplikat\"}";  check "SKU duplikat ditolak" "$STATUS" 422
req POST /products '{"sku":"ab cd","name":"","price":-1}';       check "validasi produk" "$STATUS" 422

req PUT "/products/$ID" '{"name":"Laptop Uji v2","stock":999}'
check "update produk" "$STATUS" 200
check "update tidak mengubah stok" "$(echo "$BODY" | json stock)" 5
check "nama berubah" "$(echo "$BODY" | json name)" "Laptop Uji v2"

req POST "/products/$ID/stock-in" '{"quantity":10,"note":"Restock supplier"}'
check "stok masuk" "$STATUS" 201
check "stok setelah masuk" "$(echo "$BODY" | json product.stock)" 15

req POST "/products/$ID/stock-out" '{"quantity":13}'
check "stok keluar" "$STATUS" 201
check "stok setelah keluar" "$(echo "$BODY" | json product.stock)" 2

req POST "/products/$ID/stock-out" '{"quantity":3}'
check "stok tidak boleh minus" "$STATUS" 422
req POST "/products/$ID/stock-in" '{"quantity":0}'
check "jumlah 0 ditolak" "$STATUS" 422

req GET "/products/$ID"
check "stok tetap 2" "$(echo "$BODY" | json stock)" 2
check "tandai stok menipis" "$(echo "$BODY" | json is_low_stock)" true

req GET "/products/$ID/movements"
check "riwayat" "$STATUS" 200
check "jumlah riwayat" "$(echo "$BODY" | json _meta.totalCount)" 2
check "riwayat terbaru dulu" "$(echo "$BODY" | json items.0.type)" out

req GET /products/low-stock
check "daftar stok menipis" "$STATUS" 200
echo "$BODY" | grep -q "\"$SKU\""; check "produk ada di low-stock" "$?" 0

req GET "/products?q=$SKU"
check "pencarian" "$(echo "$BODY" | json _meta.totalCount)" 1

req DELETE "/products/$ID";                        check "hapus produk" "$STATUS" 204
req GET "/products/$ID";                           check "produk terhapus" "$STATUS" 404
req POST "/products/$ID/stock-in" '{"quantity":1}'; check "stok produk yang tidak ada" "$STATUS" 404

echo
echo "Lulus: $PASS, Gagal: $FAIL"
[ "$FAIL" -eq 0 ]
