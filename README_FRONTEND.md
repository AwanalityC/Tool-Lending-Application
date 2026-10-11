# PinjamAlat Flutter Frontend

Paket ini ditujukan untuk digabungkan ke proyek yang sudah dibuat melalui `flutter create` di `C:\pinjamalat\mobile`. ZIP berisi folder `mobile/`, sehingga ekstrak ke `C:\pinjamalat` (bukan ke `C:\pinjamalat\mobile`) agar file masuk ke lokasi yang tepat.

## Isi yang disediakan

- Login, pendaftaran peminjam, penyimpanan token, dan logout.
- Dashboard berdasarkan peran `admin`, `petugas`, dan `peminjam`.
- Katalog alat dengan pencarian dan keranjang peminjaman.
- Form pengajuan dengan tanggal pinjam, jatuh tempo, alasan, dan daftar item.
- Riwayat peminjaman, status dan tampilan keterlambatan.
- Daftar pengajuan staf, setujui, tolak, dan UI proses pengembalian.
- CRUD alat dan kategori serta pembuatan akun dari panel admin.
- UI responsif untuk Android dan Chrome.
- Konfigurasi API via `--dart-define=API_BASE_URL=...`.

## Cara memasukkan ZIP

Simpan unduhan sebagai `pinjamalat_flutter_frontend.zip` di folder Downloads. Jalankan PowerShell:

```powershell
Expand-Archive "$HOME\Downloads\pinjamalat_flutter_frontend.zip" -DestinationPath "C:\pinjamalat" -Force
cd C:\pinjamalat\mobile
flutter pub get
flutter analyze
```

Perintah ekstraksi menimpa file `mobile/lib`, `mobile/pubspec.yaml`, test, dan manifest Android yang berhubungan dengan paket ini. File lain dari proyek Flutter yang tidak ada di ZIP tidak dihapus.

## Jalankan backend Laravel

Di jendela PowerShell pertama:

```powershell
cd C:\pinjamalat\backend
php artisan serve --host=0.0.0.0 --port=8000
```

Pastikan konfigurasi database `.env` benar dan server bisa diakses. Jangan jalankan `migrate:fresh` hanya untuk menjalankan frontend.

## Jalankan UI di Chrome

Di jendela PowerShell kedua:

```powershell
cd C:\pinjamalat\mobile
flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

## Jalankan di Android Emulator

```powershell
cd C:\pinjamalat\mobile
flutter devices
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
```

`10.0.2.2` adalah alamat khusus agar Android Emulator dapat menjangkau komputer host. Jika menggunakan HP fisik, ganti `127.0.0.1`/`10.0.2.2` dengan IP LAN komputer, pastikan HP dan komputer berada di jaringan yang sama, dan izinkan port 8000 di Windows Firewall jika dibutuhkan.

## API yang digunakan

Konfigurasi saat ini mengikuti rute yang terlihat pada `routes/api.php` di ZIP Tahap 4 yang tersedia: `/login`, `/register`, `/logout`, `/me`, `/kategori`, `/alat`, `/peminjaman`, `/riwayat-peminjaman`, `/pengguna`, serta aksi persetujuan dan penolakan.

### Pengembalian Tahap 5

UI pengembalian tersedia, tetapi rute Tahap 5 dan format request-nya tidak ada di ZIP backend yang tersedia saat paket ini disusun. Aplikasi sengaja tidak menebak rute. Setelah cocokkan dengan `routes/api.php` milikmu, set `AppConfig.returnEndpointTemplate` di `lib/config/app_config.dart` atau jalankan dengan define, misalnya:

```powershell
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api --dart-define=RETURN_ENDPOINT_TEMPLATE=/peminjaman/{id}/pengembalian
```

Contoh route di atas hanya contoh bentuk konfigurasi, **bukan klaim bahwa itulah rute di backend milikmu**. Payload UI pengembalian menggunakan `items` dengan `detail_id`, `alat_id`, `jumlah_baik`, `jumlah_rusak_ringan`, dan `jumlah_rusak_berat`; sesuaikan dengan validasi endpoint Tahap 5 milikmu sebelum dipakai.

## Catatan penting

- Login mengharapkan token ada dalam `data.token`, `data.access_token`, atau `data.plain_text_token`, lalu mengambil profil dari `GET /api/me`. Jika `AuthController` milikmu menggunakan nama field respons berbeda, sesuaikan bagian `AppState.login`.
- Form alat mengirim `kode`, `nama`, `kategori_id`, `deskripsi`, `stok_total`, dan `aktif`; backend tetap menjadi sumber kebenaran untuk stok tersedia/dipinjam/rusak.
- Jika login atau data tidak tersambung, periksa alamat API yang tampil di halaman Profil, pastikan Laravel berjalan, dan baca pesan error dari aplikasi.
- Paket ini disusun dengan mengacu pada rute Tahap 4 yang dibagikan. Karena seluruh proyek backend Tahap 5 tidak disertakan, kompatibilitas endpoint tambahan belum bisa diverifikasi dari sini.
