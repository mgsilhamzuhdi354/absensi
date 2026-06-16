# API Documentation: Clearance Aset Pegawai Keluar

Dokumen ini menjelaskan endpoint, workflow, data model, validasi, dan response untuk fitur clearance aset saat pegawai keluar. Endpoint pada modul ini adalah Laravel web endpoints berbasis session, bukan stateless REST API. Semua request `POST` membutuhkan CSRF token.

## Ringkasan

Fitur ini memastikan pegawai yang keluar mengembalikan aset kantor yang masih dipegang, misalnya laptop, sebelum pengajuan `Pegawai Keluar` bisa di-approve.

Alur utama:

1. Admin membuka daftar `Pegawai Keluar`.
2. Admin masuk ke halaman clearance aset pegawai.
3. Sistem menampilkan aset yang masih dipegang pegawai berdasarkan transaksi inventory terakhir bertipe `keluar`.
4. Admin memproses pengembalian aset atau memberi pengecualian.
5. Jika dikembalikan, sistem membuat transaksi stok `masuk`, memperbarui kondisi/status aset, dan membuat BAST Pengembalian.
6. Pegawai, IT penerima, dan pihak mengetahui menandatangani BAST Pengembalian.
7. Approval `Pegawai Keluar` hanya berhasil jika semua aset sudah `Dikembalikan` atau `Dikecualikan`.

## Base URL dan Auth

Contoh base URL:

```text
Local:      http://127.0.0.1:8000
Production: https://absensi.indooceancrew.com
```

Authentication:

- Endpoint admin memakai middleware `admin`.
- Endpoint user tanda tangan memakai middleware `auth`.
- Request form `POST` harus mengirim `_token` CSRF Laravel.
- Non-admin yang mengakses route admin akan dialihkan oleh middleware admin.
- User yang bukan signer dokumen akan mendapat `404`.

## Data Model

### inventory_stock_transactions

Kolom baru:

| Field | Type | Keterangan |
| --- | --- | --- |
| `return_for_transaction_id` | FK nullable | ID transaksi `keluar` awal yang dikembalikan oleh transaksi `masuk`. |
| `pegawai_keluar_id` | FK nullable | ID pengajuan pegawai keluar yang memicu pengembalian. |

Transaksi pengembalian dibuat sebagai:

```text
jenis_transaksi = masuk
sumber_barang = Pengembalian dari {nama pegawai}
return_for_transaction_id = {id transaksi keluar awal}
pegawai_keluar_id = {id pegawai keluar}
```

### pegawai_keluar_asset_clearances

Menyimpan status clearance per aset.

| Field | Keterangan |
| --- | --- |
| `pegawai_keluar_id` | Pengajuan pegawai keluar. |
| `inventory_stock_transaction_id` | Transaksi `keluar` awal saat aset diserahkan ke pegawai. |
| `returned_inventory_stock_transaction_id` | Transaksi `masuk` hasil pengembalian. |
| `status` | `pending`, `returned`, atau `waived`. |
| `returned_at` | Waktu pengembalian diproses. |
| `waived_by_user_id` | Admin yang memberi pengecualian. |
| `waived_at` | Waktu pengecualian. |
| `waiver_reason` | Alasan pengecualian. |

Mapping label UI:

| Status DB | Label Tampilan |
| --- | --- |
| `pending` | Belum Kembali |
| `returned` | Dikembalikan |
| `waived` | Dikecualikan |

### inventory_return_documents

Menyimpan BAST Pengembalian.

| Field | Keterangan |
| --- | --- |
| `nomor_surat` | Format contoh: `001 / IT-BAST-PB / VI / 2026`. |
| `tanggal_surat` | Tanggal pengembalian. |
| `employee_user_id` | Pegawai yang mengembalikan aset. |
| `it_receiver_user_id` | IT/admin yang menerima aset. |
| `known_by_user_id` | Pihak mengetahui, misalnya Crewing/HRD/Manager. |
| `kondisi_kembali` | Kondisi aset saat dikembalikan. |
| `kelengkapan` | Kelengkapan aset saat dikembalikan. |
| `catatan` | Catatan tambahan. |
| `file_pdf` | Path PDF BAST Pengembalian di storage public. |

Role tanda tangan:

| Role | Pihak | Kolom waktu tanda tangan |
| --- | --- | --- |
| `employee` | Pegawai yang mengembalikan | `employee_signed_at` |
| `it_receiver` | IT/admin penerima | `it_receiver_signed_at` |
| `known` | Crewing/HRD/Manager | `known_signed_at` |

## Endpoint Admin

### 1. Halaman Clearance Aset

```http
GET /exit/{id}/assets
```

Middleware:

```text
admin
```

Controller:

```text
PegawaiKeluarController@assets
```

Parameter path:

| Parameter | Type | Keterangan |
| --- | --- | --- |
| `id` | integer | ID `pegawai_keluars`. |

Behavior:

- Memanggil sync clearance aset untuk pegawai tersebut.
- Mencari aset yang masih dipegang pegawai.
- Membuat record clearance `pending` jika belum ada.
- Menampilkan halaman `resources/views/pegawai-keluar/assets.blade.php`.

Response sukses:

```text
200 OK
HTML halaman Clearance Aset Pegawai Keluar
```

Error:

| Kondisi | Response |
| --- | --- |
| User bukan admin | Redirect `/absen` dari middleware admin. |
| Pengajuan tidak ditemukan | `404`. |

### 2. Proses Pengembalian Aset

```http
POST /exit/{exit}/assets/{transaction}/return
```

Middleware:

```text
admin
```

Controller:

```text
PegawaiKeluarController@returnAsset
```

Parameter path:

| Parameter | Type | Keterangan |
| --- | --- | --- |
| `exit` | integer | ID `pegawai_keluars`. |
| `transaction` | integer | ID transaksi inventory awal bertipe `keluar`. |

Form body:

| Field | Required | Validation | Keterangan |
| --- | --- | --- | --- |
| `_token` | yes | CSRF token | Token form Laravel. |
| `tanggal_kembali` | yes | `date` | Tanggal aset dikembalikan. Tidak boleh lebih awal dari tanggal serah terima. |
| `kondisi_barang` | yes | `string|max:255` | Kondisi aset saat dikembalikan. |
| `kelengkapan` | yes | `string|max:255` | Kelengkapan aset, contoh `Laptop dan charger lengkap`. |
| `status_barang` | no | `string|max:255` | Status aset setelah kembali, contoh `Tersedia` atau `Perlu Perbaikan`. |
| `lokasi_id` | no | `exists:lokasis,id` | Lokasi penyimpanan setelah kembali. |
| `it_receiver_user_id` | no | `exists:users,id` | User IT/admin penerima. Jika kosong memakai admin yang sedang login. |
| `known_by_user_id` | no | `exists:users,id` | User yang mengetahui, contoh HRD/Crewing/Manager. |
| `catatan` | no | `string` | Catatan tambahan. |

Contoh form data:

```text
_token=csrf_token
tanggal_kembali=2026-06-16
kondisi_barang=Rusak ringan
kelengkapan=Laptop dan charger lengkap
status_barang=Perlu Perbaikan
lokasi_id=1
it_receiver_user_id=5
known_by_user_id=8
catatan=Engsel perlu dicek.
```

Behavior sukses:

- Validasi bahwa transaksi adalah aset pegawai pada pengajuan keluar tersebut.
- Validasi transaksi adalah transaksi `keluar`.
- Validasi transaksi tersebut masih transaksi terakhir untuk aset itu.
- Membuat atau mengambil clearance aset.
- Membuat transaksi stok `masuk`.
- Menghubungkan transaksi `masuk` ke transaksi `keluar` awal.
- Menaikkan stok aset.
- Memperbarui `kondisi`, `status_barang`, dan `lokasi_id` inventory.
- Mengubah clearance menjadi `returned`.
- Membuat BAST Pengembalian di `inventory_return_documents`.
- Generate PDF BAST Pengembalian ke `storage/app/public/inventory/return-bast/{id}.pdf`.
- Mengirim notifikasi tanda tangan ke pegawai, IT penerima, dan known jika ada.

Response sukses:

```text
302 Redirect
Location: /exit/{exit}/assets
Flash success: Pengembalian aset berhasil diproses dan BAST Pengembalian dibuat: {nomor_surat}
```

Validation error:

```text
302 Redirect back
Session errors berisi detail validasi
```

Business rule error:

| Kondisi | Error |
| --- | --- |
| Aset tidak milik pegawai keluar | `Aset ini tidak tercatat sebagai aset pegawai yang keluar.` |
| Transaksi bukan `keluar` | `Hanya transaksi aset keluar yang bisa diproses sebagai pengembalian.` |
| Aset sudah tidak dipegang pegawai | `Aset ini sudah tidak tercatat sebagai aset yang masih dipegang pegawai tersebut.` |
| Clearance sudah selesai | `Clearance aset ini sudah selesai diproses.` |
| Tanggal kembali sebelum tanggal serah terima | `Tanggal pengembalian tidak boleh lebih awal dari tanggal serah terima aset.` |

### 3. Beri Pengecualian Clearance

```http
POST /exit/{exit}/assets/{transaction}/waive
```

Middleware:

```text
admin
```

Controller:

```text
PegawaiKeluarController@waiveAsset
```

Parameter path:

| Parameter | Type | Keterangan |
| --- | --- | --- |
| `exit` | integer | ID `pegawai_keluars`. |
| `transaction` | integer | ID transaksi inventory awal bertipe `keluar`. |

Form body:

| Field | Required | Validation | Keterangan |
| --- | --- | --- | --- |
| `_token` | yes | CSRF token | Token form Laravel. |
| `waiver_reason` | yes | `string|min:5` | Alasan pengecualian. |

Behavior sukses:

- Validasi transaksi aset milik pegawai keluar.
- Membuat clearance jika belum ada.
- Mengubah status clearance menjadi `waived`.
- Menyimpan admin yang memberi pengecualian, waktu, dan alasan.

Response sukses:

```text
302 Redirect
Location: /exit/{exit}/assets
Flash success: Aset berhasil diberi pengecualian clearance.
```

Error:

| Kondisi | Error |
| --- | --- |
| Alasan kosong | `Alasan pengecualian wajib diisi.` |
| Alasan terlalu pendek | `Alasan pengecualian minimal 5 karakter.` |
| Aset sudah dikembalikan | `Aset ini sudah dikembalikan dan tidak perlu dikecualikan.` |

### 4. Download BAST Pengembalian dari Admin

```http
GET /exit/asset-return/{document}/download
```

Middleware:

```text
admin
```

Controller:

```text
PegawaiKeluarController@downloadReturnDocument
```

Parameter path:

| Parameter | Type | Keterangan |
| --- | --- | --- |
| `document` | integer | ID `inventory_return_documents`. |

Behavior:

- Regenerate PDF dari data terbaru.
- Download file PDF.

Response sukses:

```text
200 OK
Content-Disposition: attachment; filename="bast-pengembalian-{nomor_surat}.pdf"
```

Error:

| Kondisi | Response |
| --- | --- |
| Dokumen tidak ditemukan | `404`. |
| File PDF gagal dibuat atau tidak ada | `404`. |

## Endpoint User Tanda Tangan

### 5. Daftar BAST Pengembalian Saya

```http
GET /my-inventory-return-bast
```

Middleware:

```text
auth
```

Controller:

```text
PegawaiKeluarController@myReturnBastDocuments
```

Behavior:

- Menampilkan dokumen yang menugaskan user login sebagai `employee`, `it_receiver`, atau `known`.
- Menampilkan badge dokumen yang masih membutuhkan tanda tangan user.

Response sukses:

```text
200 OK
HTML daftar BAST Pengembalian Aset
```

View:

```text
resources/views/inventory/my_return_bast_index.blade.php
```

### 6. Detail BAST Pengembalian Saya

```http
GET /my-inventory-return-bast/{id}
```

Middleware:

```text
auth
```

Controller:

```text
PegawaiKeluarController@showMyReturnBastDocument
```

Parameter path:

| Parameter | Type | Keterangan |
| --- | --- | --- |
| `id` | integer | ID `inventory_return_documents`. |

Behavior:

- Menampilkan detail dokumen.
- Menampilkan data aset.
- Menampilkan status tanda tangan.
- Menampilkan canvas tanda tangan hanya untuk role yang sesuai dengan user login dan belum ditandatangani.

Response sukses:

```text
200 OK
HTML detail BAST Pengembalian
```

View:

```text
resources/views/inventory/my_return_bast_show.blade.php
```

Error:

| Kondisi | Response |
| --- | --- |
| User bukan signer dokumen | `404`. |
| Dokumen tidak ditemukan | `404`. |

### 7. Tanda Tangan BAST Pengembalian

```http
POST /my-inventory-return-bast/{id}/sign/{role}
```

Middleware:

```text
auth
```

Controller:

```text
PegawaiKeluarController@signMyReturnBastDocument
```

Parameter path:

| Parameter | Type | Keterangan |
| --- | --- | --- |
| `id` | integer | ID `inventory_return_documents`. |
| `role` | string | `employee`, `it_receiver`, atau `known`. |

Form body:

| Field | Required | Validation | Keterangan |
| --- | --- | --- | --- |
| `_token` | yes | CSRF token | Token form Laravel. |
| `agreement` | yes | `accepted` | Checkbox persetujuan tanda tangan elektronik. |
| `signature_data` | yes | `regex:/^data:image\/png;base64,/` | Payload gambar tanda tangan dari canvas. |

Contoh form data:

```text
_token=csrf_token
agreement=1
signature_data=data:image/png;base64,iVBORw0KGgo...
```

Behavior sukses:

- Validasi role.
- Validasi user login memang signer untuk role tersebut.
- Simpan gambar tanda tangan ke `storage/app/public/inventory/return-bast/signatures`.
- Isi kolom tanda tangan role terkait.
- Regenerate PDF BAST Pengembalian.

Response sukses:

```text
302 Redirect
Location: /my-inventory-return-bast/{id}
Flash success: {label role} berhasil ditandatangani dan PDF sudah diperbarui.
```

Error:

| Kondisi | Response |
| --- | --- |
| Role tidak valid | `404`. |
| User bukan signer role tersebut | `404`. |
| Agreement tidak dicentang | Redirect back dengan error validasi. |
| Signature kosong atau format salah | Redirect back dengan error validasi. |

### 8. Download BAST Pengembalian dari User

```http
GET /my-inventory-return-bast/{id}/download
```

Middleware:

```text
auth
```

Controller:

```text
PegawaiKeluarController@downloadMyReturnBastDocument
```

Behavior:

- User hanya bisa download dokumen yang terkait dengan dirinya.
- Regenerate PDF sebelum download.

Response sukses:

```text
200 OK
Content-Disposition: attachment; filename="bast-pengembalian-{nomor_surat}.pdf"
```

Error:

| Kondisi | Response |
| --- | --- |
| User bukan signer dokumen | `404`. |
| Dokumen tidak ditemukan | `404`. |
| File PDF gagal dibuat atau tidak ada | `404`. |

## Endpoint Terkait

### Approval Pegawai Keluar

```http
POST /exit/approval/{id}
```

Middleware:

```text
auth
```

Controller:

```text
PegawaiKeluarController@approval
```

Payload:

| Field | Required | Validation | Keterangan |
| --- | --- | --- | --- |
| `_token` | yes | CSRF token | Token form Laravel. |
| `status` | yes | `APPROVED` atau `REJECTED` | Status approval. |
| `notes` | no | `string` | Catatan approver. |

Behavior khusus fitur clearance:

- Jika `status=APPROVED`, sistem mengecek clearance aset.
- Jika masih ada clearance `pending`, approval ditolak.
- Jika semua clearance `returned` atau `waived`, approval berhasil.
- Jika berhasil approve, `users.masa_berlaku` diisi dengan tanggal keluar.

Response jika masih ada aset belum clear:

```text
302 Redirect
Location: /exit/{id}/assets   jika approver admin
Location: /exit               jika approver bukan admin
Flash error: Approval belum bisa diproses. Aset berikut belum dikembalikan atau dikecualikan: {daftar aset}
```

Response sukses:

```text
302 Redirect
Location: /exit
Flash success: Data Berhasil Diupdate
```

### Detail Aset Kantor

```http
GET /inventory/{id}/detail
```

Middleware:

```text
admin
```

Behavior terkait fitur ini:

- Menampilkan link `Download BAST Pengembalian` di riwayat stok jika transaksi `masuk` adalah pengembalian aset.
- Aman walaupun migration BAST Pengembalian belum dijalankan. Jika tabel belum ada, halaman tetap render tanpa relasi BAST Pengembalian.
- Transaksi pengembalian tidak boleh dihapus dari riwayat stok jika tabel BAST Pengembalian sudah tersedia.

## Workflow Detail

### A. Normal Return Flow

1. Admin membuka `/exit`.
2. Admin klik icon laptop pada baris pegawai.
3. Admin membuka `/exit/{id}/assets`.
4. Sistem membuat clearance `pending` untuk aset yang masih dipegang pegawai.
5. Admin mengisi form `Proses Pengembalian`.
6. Sistem membuat transaksi `masuk`, membuat dokumen BAST, dan mengubah clearance menjadi `returned`.
7. Pegawai membuka `/my-inventory-return-bast/{id}` dan tanda tangan sebagai `employee`.
8. IT penerima tanda tangan sebagai `it_receiver`.
9. HRD/Crewing/Manager tanda tangan sebagai `known`.
10. Admin kembali approve pegawai keluar.

### B. Waive Flow

1. Admin membuka `/exit/{id}/assets`.
2. Admin mengisi alasan pengecualian.
3. Sistem mengubah clearance menjadi `waived`.
4. Approval pegawai keluar bisa lanjut walau aset tidak dibuatkan transaksi return.

### C. Approval Block Flow

1. Approver memilih `APPROVED`.
2. Sistem sync clearance aset.
3. Jika ada clearance `pending`, approval ditolak.
4. Admin diarahkan ke halaman clearance aset.

## Contoh cURL

Endpoint ini memakai session dan CSRF, jadi cURL hanya praktis jika sudah punya cookie login dan token CSRF.

Contoh proses return:

```bash
curl -X POST "http://127.0.0.1:8000/exit/12/assets/30/return" \
  -H "Cookie: laravel_session=SESSION_COOKIE" \
  -H "X-CSRF-TOKEN: CSRF_TOKEN" \
  -F "tanggal_kembali=2026-06-16" \
  -F "kondisi_barang=Baik" \
  -F "kelengkapan=Laptop dan charger lengkap" \
  -F "status_barang=Tersedia" \
  -F "lokasi_id=1" \
  -F "it_receiver_user_id=5" \
  -F "known_by_user_id=8" \
  -F "catatan=Diterima lengkap"
```

Contoh tanda tangan:

```bash
curl -X POST "http://127.0.0.1:8000/my-inventory-return-bast/4/sign/employee" \
  -H "Cookie: laravel_session=SESSION_COOKIE" \
  -H "X-CSRF-TOKEN: CSRF_TOKEN" \
  -F "agreement=1" \
  -F "signature_data=data:image/png;base64,iVBORw0KGgo..."
```

## File Implementasi

Controller:

```text
app/Http/Controllers/PegawaiKeluarController.php
app/Http/Controllers/InventoryController.php
```

Service:

```text
app/Services/PegawaiKeluarAssetService.php
```

Model:

```text
app/Models/PegawaiKeluarAssetClearance.php
app/Models/InventoryReturnDocument.php
app/Models/InventoryStockTransaction.php
app/Models/PegawaiKeluar.php
```

View:

```text
resources/views/pegawai-keluar/assets.blade.php
resources/views/inventory/my_return_bast_index.blade.php
resources/views/inventory/my_return_bast_show.blade.php
resources/views/inventory/return_bast_pdf.blade.php
resources/views/inventory/detail.blade.php
resources/views/pegawai-keluar/index.blade.php
```

Migration:

```text
database/migrations/2026_06_15_000001_add_return_links_to_inventory_stock_transactions_table.php
database/migrations/2026_06_15_000002_create_pegawai_keluar_asset_clearances_table.php
database/migrations/2026_06_15_000003_create_inventory_return_documents_table.php
```

Test:

```text
tests/Feature/PegawaiKeluarTest.php
tests/Feature/InventoryQrStockTest.php
```

## Deployment Checklist

Jalankan di server setelah `git pull`:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan storage link ada:

```bash
php artisan storage:link
```

Migration yang wajib ada:

```text
2026_06_15_000001_add_return_links_to_inventory_stock_transactions_table
2026_06_15_000002_create_pegawai_keluar_asset_clearances_table
2026_06_15_000003_create_inventory_return_documents_table
```

Cek status migration:

```bash
php artisan migrate:status | grep 2026_06_15
```

## Test Plan

Jalankan:

```bash
php artisan test --filter=PegawaiKeluarTest
php artisan test --filter=InventoryQrStockTest
php artisan test
```

Coverage utama:

- Pegawai keluar dengan aset masih dipegang tidak bisa di-approve.
- Admin memproses pengembalian aset.
- Stok aset naik kembali.
- Kondisi/status barang berubah sesuai data pengembalian.
- BAST Pengembalian dibuat dan PDF tersedia.
- Aset bisa diberi pengecualian dengan alasan wajib.
- User hanya bisa tanda tangan role yang ditugaskan.
- Existing BAST serah terima aset tetap berjalan.
- Detail inventory tetap render walaupun migration BAST Pengembalian belum dijalankan.

