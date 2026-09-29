# AGENTS.md

Laravel 10 (PHP `^8.1`) + Blade. HR/attendance app (BUMDESMA) layered on a public promo site. `README.md` is stock Laravel boilerplate — ignore it. App locale is `id` (`lang/id`: validation/auth/passwords/pagination + field-name `attributes`); `AppServiceProvider` syncs Carbon locale so `translatedFormat` prints Indonesian month/day names.

## Commands

```bash
php artisan serve                    # dev server (http://localhost:8000)
php artisan migrate --seed           # full setup; seeds accounts (see below)
php artisan migrate:fresh --seed     # clean reset
php artisan test                     # or: vendor/bin/phpunit
php artisan test --filter=ExampleTest # single test/class
./vendor/bin/pint --test             # lint check (no config file = defaults)
./vendor/bin/pint                    # autofix
```

No `composer test` / npm scripts, no CI, no `opencode.json`. `npm run dev|build` is dead weight (no view uses `@vite`; Bootstrap/CDN + inline `<style>`/`<script>` only). `Dockerfile` (Railway) skips `npm run build`, runs `migrate --force --seed` + `storage:link` on every start — seeders are idempotent (`updateOrCreate`/`firstOrCreate`), don't "optimize" that away.

## Database (Indonesian schema — 14 rename migrations `2026_09_28_000000`–`000013`, data-preserving `Schema::rename` + raw `CHANGE`, no `doctrine/dbal`; plus `2026_09_30_000000` which drops the removed `konten` table)

| Now | Was | Key columns now |
|---|---|---|
| `jabatan` | `positions` | `nama`, `deskripsi` |
| `hari_libur` | `holidays` | `tanggal`, `nama` |
| `jadwal_kerja` | `work_schedules` | `nama`, `jam_masuk`, `jam_pulang`, `toleransi_telat_menit`, `hari_kerja`, `aktif` |
| `kuota_cuti` | `leave_quotas` | `tahun`, `jatah_hari` (default 12) |
| `pengaturan_kehadiran` | `attendance_settings` (singleton `id=1`) | `jam_masuk`, `jam_pulang`, `toleransi_telat_menit`, `mulai_absen_pulang`, `akhir_absen_pulang`, `wajib_persetujuan_pulang`, `poin_otomatis_aktif`, `blok_menit_poin`, `poin_per_blok` |
| `lokasi_kehadiran` | `attendance_locations` | `nama`, `alamat`, `lintang`, `bujur`, `radius_meter`, `maks_akurasi_meter`, `wajib_radius`, `wajib_radius_pulang`, `aktif` |
| `karyawan` | `employees` | `pengguna_id→users`, `jabatan_id`, `jadwal_kerja_id`, `kode_karyawan` (UQ), `nama`, `telepon`, `email`, `foto`, `foto_diubah_pada`, `tanggal_masuk`, `gaji_pokok`, `tunjangan_kehadiran`, `aktif` + soft deletes |
| `kehadiran` | `attendances` | `karyawan_id`, `tanggal`, `waktu_masuk`, `waktu_pulang`, `foto/lintang/bujur/akurasi/jarak_*_meter/lokasi_*_valid` masuk+pulang, `menit_telat`, `menit_kerja`, `status`, `status_pulang`, `tipe_persetujuan_pulang`, `disetujui_oleh/pada`, `catatan` |
| `pengajuan_cuti` | `leave_requests` | `karyawan_id`, `jenis`, `tanggal_mulai/selesai`, `jumlah_hari`, `alasan`, `jalur_lampiran`, `status`, `diulas_oleh/pada`, `catatan_ulasan`, `alasan_penolakan` |
| `penggajian` | `payrolls` | `karyawan_id`, `periode_mulai/selesai`, `gaji_pokok`, `tunjangan_kehadiran`, `total_reward`, `potongan_telat/absen`, `total_hukuman`, `gaji_bersih`, `status` |
| `komponen_gaji` | `salary_components` | `karyawan_id`, `nama`, `jenis`, `nominal`, `aktif` |
| `penghargaan` | `rewards` | `karyawan_id`, `jenis`, `nominal`, `poin`, `judul`, `deskripsi`, `diberikan_pada` |
| `hukuman` | `punishments` | same + `otomatis`, `dijatuhkan_pada` |
| `kunjungan` | `visit_attendances` | `karyawan_id`, `judul`, `deskripsi`, `foto`, `lintang`, `bujur`, `akurasi`, `tanggal` |

Tabel `konten` (`contents`) sudah dihapus (`2026_09_30_000000_drop_konten_table`) — halaman publik memakai default bawaan `HomeController`, tidak ada CMS.

Kept English on purpose: `id`/`created_at`/`updated_at`/`deleted_at`, `users` + `personal_access_tokens`/`password_reset_tokens`/`failed_jobs` (framework), dead `reward_rules`/`punishment_rules`/`qr_attendances` (+ `*_rule_id` fillables), all `status`/`jenis` enum VALUES (`present|late|pending|draft|leave|sick|...`), model class names (`Employee`, `Attendance`... map via `$table`), route names/URLs (`/employees*`, `/holidays*`, `?type=`), DOM `id`s in Blade (JS hooks), storage folders (`employee-photos`, `leave-attachments`). Shared request filter key is now `karyawan_id` everywhere.

## Routes & roles

- `routes/web.php` is the whole app; `routes/api.php` is an unused Sanctum stub (`GET /user`). Don't add API routes unless asked.
- Public (`HomeController@index|page`): `/`, `/{page}`. HR (`middleware('auth')`): `/dashboard`, `/attendance`, `/leave-requests`, `/payroll`, `/recognition`, `/reports/*`, `/employees*`.
- Catch-all `GET /{page}` (`whereIn`: `tentang`, `profil-lkd`, `profil`, `kegiatan`, `program`, `potensi`, `berita`, `galeri`, `kontak`) **must stay last**. `tentang`/`profil-lkd` alias to the `profil` slug (`HomeController@page`).
- Role gate: `admin` alias (`Kernel.php` → `AdminMiddleware`) aborts 403 unless `User::isAdmin()` (`role === 'admin'`). Admin-only: employee CRUD, payroll generate/status, recognition writes, attendance settings + locations + early-checkout approval, `attendance.corrections.*`, `/holidays*` CRUD, `PATCH /leave-requests/{leaveRequest}/review`. Reports (`/reports/*`) are NOT admin-only (karyawan force-scoped to own `karyawan_id`).
- Employee routes are duplicated (`/employees*` + `/admin/karyawan*` → same controller). Prefer `/employees*` for new links.
- Master data (admin-only, one `Master Data` nav entry with tabs): `/positions` (nama unique; delete blocked while referenced by any karyawan incl. soft-deleted), `/work-schedules` (`jam_pulang` must be after `jam_masuk` — no overnight shifts; `hari_kerja` min 1 day, stored but informational; one `aktif` schedule must always remain), `/salary-components` (per-karyawan `late_deduction`/`absence_deduction` rates, unique jenis per karyawan; unset/inactive = defaults Rp1000/min + Rp0/day). `Position::employees()`/`WorkSchedule::employees()` use explicit FKs (`jabatan_id`/`jadwal_kerja_id`) — don't drop them.

## Attendance

- Admins are blocked from check-in/out (403) — test attendance flows as an employee user.
- Check-in: `late` for any minute past start (`AttendanceController::checkIn` via `CheckoutPolicy::workTimes`). `tolerance` is resolved but unconsumed — don't invent grace-period logic.
- Checkout rule (`CheckoutPolicy::checkoutEligibility`, server time only; `WorkSchedule` overrides `AttendanceSetting`): in-window needs no approval; early (before window) needs admin approval (`approved` + type `early|special` bypasses the window, incl. `too_late`); post-window without approval is rejected.
- Evidence: selfie + GPS required both ways (`foto|lintang|bujur|akurasi`, ≤2 MB → `attendance/check-in|check-out/Y/m` on `public` disk). Radius enforced server-side by `AttendanceLocationService::check` (Haversine) vs active `AttendanceLocation`; no active location = record-only (`lokasi_*_valid = null`). Check-in enforces only when `wajib_radius` is on (seed default on); check-out is record-only unless `wajib_radius_pulang` is on (migration default off).
- Office (`AttendanceLocationSeeder`): `Kantor BUMDESMA TARUB LKD`, Tarub Tegal (-6.926433, 109.185844), radius 100 m, max accuracy 500 m (loose on purpose for laptop WiFi GPS).
- `attendance.show` is owner-or-admin only (has Leaflet map).
- Manual corrections (`AttendanceCorrectionController`, admin): recomputes late/work minutes via `CheckoutPolicy::workTimes`, resets checkout approval, never touches photo/GPS evidence. One row per karyawan+tanggal (unique); `present|late` requires `jam_masuk`.
- `/holidays*` dates are excluded from workday counts in both `ReportController::workdays` and `PayrollController::workdays` (weekdays minus holidays — keep the two implementations in sync).

## Leave / visits / payroll / recognition

- Leave (`LeaveRequestController`): `jenis=leave` must be filed ≥7 calendar days ahead (sick exempt); rejection requires `alasan_penolakan` ≥10 chars; review locked once processed (`pending` only). Annual quota default 12 days (`LeaveQuota::current()/usage()`, enforced on approval). Approval auto-writes `kehadiran` rows for each date in range (`leave`/`sick` status). Owner can edit (`PUT leave.update`, same validation incl. 7-day rule, lampiran replace deletes old file) or cancel (`DELETE leave.destroy`, hard delete + attachment file) only while `pending` — both use `lockForUpdate` and 422 once processed; non-owners get 403. Notifications (DB channel only, no email): `store` notifies all admins (`LeaveRequestSubmitted`), `review` notifies the employee (`LeaveRequestReviewed`, fired after the transaction commits); bell in navbar (`layouts/app`, via `AppServiceProvider` composer) links to `notifications.read` (marks read + redirects to internal `/leave-requests/{id}` only) and `notifications.read-all`.
- Visits (`VisitAttendanceController`, `MAX_PER_DAY = 10` enforced in `store`, `tanggal` must be ≤ today, photo → `visit-attendances/Y/m` on `public` disk): photo + GPS like attendance; karyawan see only their own, admins see all but coordinates/map points only when `?karyawan_id=` is set. `show`/`destroy` are owner-or-admin; `destroy` also deletes the photo file. Owner-or-admin can edit via `PUT visits.update` (judul/deskripsi/tanggal ≤ today + optional photo replace; GPS immutable; moving `tanggal` re-checks the daily cap).
- Payroll money math: basic + allowance + rewards − late (`menit_telat × rate`) − absence − punishments, floored at 0. Rates come from `komponen_gaji` (only `aktif` rows; `late_deduction` default Rp1000/min, `absence_deduction` default 0 — managed at `/salary-components`). `generate` is `updateOrCreate` per karyawan+month — re-running overwrites and resets `status` to `draft` (even `verified`/`paid`). Auto late points don't touch it (`nominal=0`). Slip per karyawan: `payroll.show` (owner-or-admin, itemized rewards/punishments + attendance summary, rates derived from stored values) and `payroll.pdf` (dompdf A4 portrait, `slip-gaji-{kode}-{Y-m}.pdf`).
- Auto late points (`RecognitionController@generateAutoPoints`, `POST /recognition/auto-points`, admin): monthly `late` minutes → one `hukuman` row (`jenis=points_deduction`, `otomatis=true`), ceil rounding via `pengaturan_kehadiran` (`poin_otomatis_aktif`, `blok_menit_poin` default 15, `poin_per_blok` default 1). Idempotent per month (deletes prior auto rows, manual untouched). Rule edited in `attendance.settings`.
- Dead schema, no code behind it — don't build on it: `reward_rules`, `punishment_rules` (+ `*_rule_id` fillables), `qr_attendances`.
- Models: `User -(hasOne)-> Employee`; `Employee` belongs to `User|Position|WorkSchedule`, uses `SoftDeletes` (reports + employee index query `withTrashed()`). See `docs/erd-sistem-absensi.drawio` (+`.png`) for relations (dead tables struck through); `docs/erd-chen.png` is the Chen render; `docs/usecase-*.png` = admin vs karyawan flows; `docs/activity-diagram-{login,kehadiran,cuti,penggajian}.mmd/.png/.drawio` = 4 UML-style activity diagrams (Mermaid source + rendered PNG + editable draw.io, swimlane Pengguna/Karyawan|Admin|Sistem; old `activity-diagram-kunjungan.drawio` kept as archive).

## Reports

- `ReportController@index` is the real handler; `?type=` = `all|attendance|leaves|payroll|reward-punishment|employees|visits`. `/reports/attendance|leaves|payroll|reward-punishment|employees` just force `type` (no `/reports/visits` route — visits only via `?type=visits`). Karyawan force-scoped to own id in queries + exports.
- Period `?period=`: `monthly` default (legacy `?month=&year=` still works), `weekly` via `?week=Y-m-d` (Mon–Sun), `yearly` via `?year=`, `custom` via `?start_date=&end_date=` (max 366 days). Filenames/labels use `period_slug`/`period_label`, not `Y-m`.
- Payroll rows are per-month: non-monthly periods pull overlapping months; leaves use full-overlap (`start <= end AND end >= start`).
- `/print` renders `reports.print`; `/pdf` downloads it via dompdf (A4 landscape); `/excel` via maatwebsite/excel (`ReportExport`/`ReportSheet`, one styled sheet per section, `type=all` = 6 sheets + payroll TOTAL row). Keep print/PDF CSS dompdf-safe (tables, no flex/grid). Punishment `Nilai` shows `N poin` for `points_deduction` rows with points, else Rp.

## Seeders & accounts

- `DatabaseSeeder` runs 3 seeders, all idempotent: `AttendanceSeeder` (`Staff` position + `Jam Kerja Reguler` Mon–Fri 08:00–15:00; `admin@bumdesma.test` / `password` skipped when `APP_ENV=production`), `AttendanceLocationSeeder`, `KaryawanBaruSeeder` (permanent `KRY-007`–`KRY-013` in ALL envs, password `12345678`: `zila|sus|andry|oka|ludi|zaeni|tamami@bumdesma.test`).
- `KRY-001`–`KRY-006` were deleted — do not reintroduce. Purge leftovers via `php artisan demo:purge` (`--force` skips confirm; deactivates + soft-deletes, history kept).
- Prod admin: `php artisan admin:create` (interactive, `--name/--email/--password`). `.env.example` defaults to `APP_ENV=production`, so don't expect the demo admin login until `APP_ENV=local`.
- Backups: `php artisan db:backup --keep=7` (daily 01:00; needs cron `schedule:run` — on Railway a separate Cron service, web container doesn't run the scheduler; mysql-only, needs `mysqldump` on PATH; lands in `storage/app/backups`, survives redeploys only inside `/data`).

## Env / test gotchas

- Local DB is MySQL (`DB_DATABASE=bumdesma_tarub` in `.env`, gitignored). No SQLite fallback.
- `phpunit.xml` has sqlite `DB_CONNECTION`/`DB_DATABASE` commented out — tests hit real MySQL unless overridden. `RefreshDatabase` is used by every Feature test except `ExampleTest` (`AttendanceEvidence`, `AttendanceManagement`, `AutoLatePoints`, `LeaveNotification`, `LeaveRequestRule`, `MasterData`, `PayrollSlip`, `PayrollWorkdays`, `VisitAttendance`) — never run them against the dev DB; point at an empty scratch DB first (e.g. `DB_DATABASE=bumdesma_test DB_PASSWORD=... php artisan test --filter=...`). Only `ExampleTest` (`GET /` → 200) and `LogoutTest` (no DB writes) are safe without that.
- Public pages (`/`, `/{page}` hero: label/judul/ringkasan/gambar_url) render from hardcoded defaults in `HomeController` — no CMS, no DB reads. `/` homepage is fully static.
- Uploads: employee photos on `public` disk (`employee-photos`, via `asset('storage/...')` — run `storage:link` after fresh setup; if 404 persists, check `public/storage` is a symlink to *this* project's `storage/app/public`, not a stale folder/link — delete and re-run). Leave attachments use `local` disk (`leave-attachments`, auth-gated `leave.attachment` download only). Profile photo changes have a 1-week cooldown (`ProfileController::photoCooldown`) — don't remove it as a bug.

## Deploy

- Railway: set `APP_URL` to the public `https://...` domain (reset emails, `detail_url` JSON). `TrustProxies::$proxies = '*'`. Attendance modal `fetch` uses relative URLs (`route(..., [], false)`) — keep it that way. Attach a Volume at `/data` + set `STORAGE_PATH=/data` (`bootstrap/app.php` redirects storage there; `storage:link` on boot follows). Full prod checklist in `.env.example` (`APP_KEY`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `MAIL_*`).
- Vercel (serverless `vercel-php@0.6.2`): entry `api/index.php` → `public/index.php`; `vercel.json` routes `/build|images|favicon|robots` to static, rest to the function; safe defaults baked in (`SESSION_DRIVER=cookie`, `LOG_CHANNEL=stderr`, `VIEW_COMPILED_PATH=/tmp/views`). `bootstrap/app.php` uses `/tmp/laravel-storage` when `VERCEL` is set. Still required in dashboard: `APP_KEY`, `APP_URL`, `DB_*` (external MySQL), `MAIL_*`; run `migrate --force --seed` + `admin:create` once from local. Limits: uploads ephemeral in `/tmp`, scheduler/backup don't run.
