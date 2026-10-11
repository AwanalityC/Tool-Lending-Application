# PinjamAlat Tahap 5 backend integration patch

Patch ini menambahkan API pengembalian dan memasang routine SQL yang saat pemeriksaan database belum ada. Migration/tabel existing tidak diubah.

## Files included
- `database/sql/tahap5_routines.sql`: `fn_hitung_denda_terlambat`, `sp_setujui_peminjaman`, and status-log triggers.
- `app/Http/Requests/StorePengembalianRequest.php`
- `app/Http/Controllers/Api/PengembalianController.php`
- `app/Services/PengembalianService.php`
- `app/Models/Pengembalian.php`
- Updated `routes/api.php`, `PeminjamanService.php`, `Peminjaman.php`, and `PeminjamanResource.php`.

## Installation on XAMPP / PowerShell
1. Extract this ZIP to `C:\pinjamalat\backend` (merge the folders). Do not delete other files.
2. Before testing approval again, install the SQL routine file into the application database:

   ```powershell
   cmd.exe /c '"C:\xampp\mysql\bin\mysql.exe" -u root -D pinjamalat < "C:\pinjamalat\backend\database\sql\tahap5_routines.sql"' 
   ```

   If the `root` account uses a password, add `-p` immediately after `-u root`.
3. Verify it installed:

   ```powershell
   & "C:\xampp\mysql\bin\mysql.exe" -u root -D pinjamalat -e "SELECT ROUTINE_TYPE, ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='pinjamalat'; SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='pinjamalat';"
   ```
4. Check route/autoload, then restart Laravel:

   ```powershell
   cd C:\pinjamalat\backend
   composer dump-autoload
   php artisan route:list --path=api
   ```

   Expected new route: `POST api/peminjaman/{peminjaman}/pengembalian`.
5. Start the Laravel server again:

   ```powershell
   php artisan serve --host=0.0.0.0 --port=8000
   ```
6. Launch Flutter with the matching return endpoint:

   ```powershell
   cd C:\pinjamalat\mobile
   flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000/api --dart-define=RETURN_ENDPOINT_TEMPLATE=/peminjaman/{id}/pengembalian
   ```

## Behavior
- Only `admin` / `petugas` can process returns.
- Request body matches the frontend's `items` list and validates `detail_id`, `jumlah_baik`, `jumlah_rusak_ringan`, and `jumlah_rusak_berat`.
- A return requires an approved loan and a complete per-detail quantity split.
- Good items return to available stock. Light/heavy damaged items move from borrowed stock to damaged stock.
- Late fines use `fn_hitung_denda_terlambat`, snapshot the current daily rate, and combine with damage fines.
- The unique `pengembalian.peminjaman_id` plus row lock prevents a second return for the same loan.
- The SQL routine script drops/recreates only these named routine/trigger objects; it does not delete application data. It does not seed `pengaturan_denda`; that table must contain at least one row.

## Important testing note
`sp_setujui_peminjaman` manages its own transaction by design. Install its routine on the database used for feature tests too, if those tests execute the procedure. If a test `migrate:fresh` drops/recreates `peminjaman`, run the SQL routine script after migrations to recreate table triggers. Do not run `migrate:fresh` against the live `pinjamalat` database unless you intend to erase its data.
