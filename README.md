# SIKAD TARUB — Sistem Informasi Kehadiran Karyawan BUMDesma Tarub LKD

Aplikasi web absensi + administrasi karyawan: absensi masuk/pulang (foto selfie + GPS + radius kantor),
cuti/sakit/dinas keluar, kunjungan kerja, penggajian + slip gaji, reward & punishment,
laporan (cetak/PDF/Excel), serta situs profil publik BUMDesma.

Stack: **Laravel 10 (PHP ^8.1) + Blade + MySQL**. Locale `id`, timezone `Asia/Jakarta`.
UI: Bootstrap 5 CDN + Chart.js (dashboard). Tanpa Vite/npm build.

## Fitur utama

- **Absensi:** check-in/out dengan selfie langsung dari kamera + GPS; validasi radius kantor (Haversine);
  jadwal kerja + toleransi telat; Sabtu–Minggu/libur tidak perlu absen; waktu memakai jam server.
  Admin tidak bisa absen; cuti/sakit/dinas yang disetujui memblokir absen hari itu.
- **Karyawan:** CRUD + jabatan, jadwal kerja, akun login; riwayat kehadiran; koreksi kehadiran oleh admin.
- **Cuti/sakit/dinas:** kuota 12 hari/tahun, cuti maks 3 hari beruntun per pengajuan; review admin; notifikasi dalam aplikasi.
- **Kunjungan:** maks 10/hari, foto wajib, GPS tidak dapat diubah.
- **Penggajian:** generate bulanan (gaji pokok + tunjangan + reward − potongan telat/absen − hukuman);
  status draft → verified → paid; slip + PDF.
- **Reward & punishment:** manual + poin otomatis keterlambatan per bulan.
- **Laporan:** kehadiran, cuti, penggajian, reward-punishment, karyawan, kunjungan;
  periode bulanan/mingguan/tahunan/kustom; cetak, PDF (A4 landscape), Excel.
- **Dashboard:** admin (tren 6 bulan, sering telat, belum absen + ingatkan via WA, antrean persetujuan);
  karyawan (absensi hari ini, ringkasan + grafik bulan ini).

## Cara jalan lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
# sesuaikan DB_* di .env (MySQL, mis. bumdesma_tarub)
php artisan migrate --seed
php artisan storage:link
php artisan serve   # http://localhost:8000
```

Akun demo (dibuat seeder, **non-production saja**): `admin@bumdesma.test` / `password`,
karyawan `zila|sus|andry|oka|ludi|zaeni|tamami@bumdesma.test` / `12345678`.

Perintah berguna:

```bash
php artisan test                      # butuh DB MySQL scratch, JANGAN DB dev
php vendor/bin/pint --test            # cek gaya kode
php artisan demo:purge --force        # nonaktifkan + soft-delete akun demo
php artisan db:backup --keep=7        # backup mysql (butuh mysqldump)
php artisan admin:create              # buat admin production
```

> Pengujian memakai `RefreshDatabase`, jadi gunakan DB kosong:
> `$env:DB_DATABASE='bumdesma_test'; php artisan test --filter=...` (PowerShell).

## Catatan operasional

- Absensi butuh **HTTPS** di production agar kamera + GPS browser diizinkan.
- Setelah deploy: set `APP_URL` domain asli, `SESSION_SECURE_COOKIE=true`, isi `MAIL_*`,
  jalankan `storage:link`, dan pastikan scheduler (`schedule:run`) berjalan untuk backup harian.
- Upload `attendance/check-in|check-out`, `visit-attendances`, `employee-photos` ada di disk `public`;
  lampiran cuti di disk `local` (unduh lewat route auth saja).

## SOP singkat

- **Karyawan:** login → Dashboard → Absen Masuk (izinkan kamera + lokasi, posisikan wajah, konfirmasi) →
  Absen Pulang di jam pulang. Ajukan cuti/sakit/dinas via menu Cuti & Sakit sebelum hari-H bila bisa;
  catat kunjungan via menu Kunjungan; lihat slip gaji di Penggajian.
- **Admin:** pantau Belum Absen → ingatkan via WA; tinjau cuti (setujui/tolak + alasan);
  setujui pulang awal bila perlu; koreksi data yang salah via menu Kehadiran;
  generate payroll tiap awal bulan → verifikasi → tandai paid; unduh laporan via menu Laporan.
