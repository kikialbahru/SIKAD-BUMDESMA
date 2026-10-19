# AGENTS.md

Laravel 10 (PHP `^8.1`) + Blade. HR/attendance app (BUMDESMA) on a public promo site. `README.md` is stock boilerplate — ignore. Locale `id`, timezone `Asia/Jakarta` (`config/app.php`); all attendance math uses server time. `AppServiceProvider` syncs Carbon locale + injects navbar notifications.

## Commands

```bash
php artisan serve                     # http://localhost:8000
php artisan migrate --seed            # full setup
php artisan migrate:fresh --seed      # clean reset
php artisan test                      # or vendor/bin/phpunit
php artisan test --filter=ExampleTest # single test
php vendor/bin/pint --test            # lint check (defaults, no config); omit --test to autofix
```

No `composer test`, no CI, no `opencode.json`. `npm run dev|build` unused (no `@vite`; Bootstrap CDN + inline style/script). `Dockerfile` skips `npm run build`, runs `migrate --force --seed` + `storage:link` every start — seeders are idempotent, keep them so.

## Database (Indonesian names — 14 rename migrations `2026_09_28_000000`–`000013` via `Schema::rename` + raw `CHANGE`, no `doctrine/dbal`; `2026_09_30_000000` drops dead `konten`)

`jabatan` (ex-`positions`), `hari_libur`, `jadwal_kerja` (`jam_masuk/jam_pulang/toleransi_telat_menit/hari_kerja/aktif`), `kuota_cuti` (`tahun/jatah_hari`=12), `pengaturan_kehadiran` (singleton `id=1`), `lokasi_kehadiran`, `karyawan` (`pengguna_id→users`, `jabatan_id`, `jadwal_kerja_id`, `kode_karyawan` UQ + SoftDeletes), `kehadiran`, `pengajuan_cuti`, `penggajian`, `komponen_gaji`, `penghargaan`, `hukuman`, `kunjungan` (ex-`visit_attendances`). `HomeController` uses hardcoded defaults — no CMS table.

Keep English: `id`/timestamps, `users` + framework tables, dead `reward_rules`/`punishment_rules`/`qr_attendances` (+ `*_rule_id` fillables — don't build on them), all `status`/`jenis` enum values (`present|late|pending|draft|leave|sick|duty|...`), model class names (map via `$table`), route names/URLs (`/employees*`, `/holidays*`, `?type=`), DOM `id`s, storage folders (`employee-photos`, `leave-attachments`). Filter key is `karyawan_id` everywhere.

## Routes & roles

- `routes/web.php` is the whole app; `routes/api.php` is an unused Sanctum stub. Don't add API routes unless asked. `routes/console.php` is the default inspire stub only — scheduler lives in `app/Console/Kernel.php`.
- Catch-all `GET /{page}` (`tentang|profil-lkd|profil|kegiatan|program|potensi|berita|galeri|kontak`, `tentang`/`profil-lkd`→`profil`) **must stay last**.
- `admin` middleware (`Kernel.php`→`AdminMiddleware`, `User::isAdmin()` = `role==='admin'`, 403 otherwise). Admin-only: employee CRUD, payroll generate/status, recognition writes, attendance settings/locations/checkout-approval, `attendance.corrections.*`, `/holidays*` CRUD, `PATCH /leave-requests/{id}/review`. `/reports/*` is NOT admin-only (karyawan force-scoped to own id in queries + exports).
- `/employees*` + `/admin/karyawan*` are duplicates → same controller; use `/employees*` for new links.

## Attendance

- Admins get 403 on check-in/out — test as employee.
- Weekends off: Sat–Sun check-in/out rejected (`isWeekend` in `AttendanceController`, "tidak perlu absen"); dashboard shows `Hari libur` instead of absen buttons + hides admin `Belum absen` list (`isOffDay` from `DashboardController`). Workday counts already Mon–Fri only.
- Logic lives in `app/Services/` (not `app/Policies/`): `CheckoutPolicy::workTimes` (schedule overrides `AttendanceSetting`), `checkoutEligibility` (server time; in-window = no approval; early needs `approved` + `early|special` which also bypasses `too_late`; post-window without approval rejected). Check-in opens at `mulai_absen_masuk` (default 07:00, settings-editable; earlier rejected). `late` via `lateMinutes`: 0 within `toleransi_*`, full raw minutes beyond (08:00+tol 10 → 08:10 `present`/0, 08:11 `late`/11).
- Evidence both ways: selfie + GPS (`foto|lintang|bujur|akurasi`, ≤2 MB → `attendance/check-in|check-out/Y/m` on `public`). `AttendanceLocationService::check` (Haversine) vs active location; no active location = record-only (`lokasi_*_valid=null`). Enforced only if `wajib_radius` (seed on) for check-in / `wajib_radius_pulang` (default off) for check-out.
- Office seed: `Kantor BUMDESMA TARUB LKD` (-6.926433, 109.185844), 100 m radius, max accuracy 500 m (loose for laptop WiFi — intentional).
- `attendance.show` owner-or-admin (Leaflet map). Corrections (admin): recompute late/work via `workTimes`, reset checkout approval, never touch photo/GPS; one row per karyawan+tanggal; `present|late` requires `jam_masuk`; status set includes `duty`.
- Holidays excluded from workday counts — `ReportController::workdays` and `PayrollController::workdays` must stay in sync.

## Leave / visits / payroll / recognition

- Leave (`jenis=leave|sick|duty`): `tanggal_mulai` ≥ today on `store`/`update` (boleh dadakan — no H-7 rule); `leave` max 3 consecutive days per request (enforced on store, update, and approval); `duty` (dinas keluar) is quota-free with no 3-day cap (overlap guard only). Reject needs `alasan_penolakan` ≥10 chars; review only from `pending`. Quota 12/yr (`LeaveQuota::current()/usage()`, enforced on approval); approval auto-writes `kehadiran` rows with `status`=jenis (`leave`/`sick`/`duty`). Approved leave/sick/duty today blocks check-in and hides absen buttons (`todayLeave`) + excludes karyawan from admin `Belum absen` list. Owner edit (`PUT`) / hard-delete (`DELETE` + file) only while `pending` (`lockForUpdate`, 422 once processed, 403 non-owner). DB notifications only: `store`→all admins, `review`→employee (after commit); bell via `AppServiceProvider` composer, `notifications.read` allows internal `/...` paths only (else `/dashboard`, anti open-redirect).
- Visits (`MAX_PER_DAY=10` per `tanggal`, `tanggal` ≤ today, photo → `visit-attendances/Y/m` on `public`; index defaults to current month `?month=Y-m`): karyawan see own only; admin sees all but coordinates/map only with `?karyawan_id=`. `show`/`destroy` owner-or-admin (`destroy` deletes photo). `update` edits judul/deskripsi/tanggal + optional photo; GPS immutable; moving date re-checks cap.
- Payroll: basic + allowance + rewards − late (`menit_telat`×rate) − absence − punishments, floored 0. Rates from active `komponen_gaji` only. `duty` counts as non-absence (like leave/sick) in `generate` + slip. `generate` = `updateOrCreate`/month, resets `status`→`draft` even from `verified`/`paid`. Auto late points use `nominal=0` (no pay effect). `payroll.show` owner-or-admin; `payroll.pdf` dompdf A4 portrait `slip-gaji-{kode}-{Y-m}.pdf`.
- Auto points (`POST /recognition/auto-points`, admin): monthly `late` minutes → one `hukuman` (`points_deduction`, `otomatis=true`), `ceil(total/blok_menit_poin)*poin_per_blok` (defaults 15/1, flag `poin_otomatis_aktif` in `attendance.settings`). Idempotent (deletes prior auto rows only).
- Relations: `User -hasOne→ Employee`; `Employee` belongs to `User|Position|WorkSchedule`. Reports + employee index use `withTrashed()`. See `docs/erd-chen.png` (`docs/erd-chen-generator.py`).

## Reports

- `ReportController@index` handles all; `?type=all|attendance|leaves|payroll|reward-punishment|employees|visits`. No `/reports/visits` route (only `?type=visits`).
- `?period=monthly` default (legacy `?month&year` works) | `weekly` (`?week=Y-m-d`, Mon–Sun) | `yearly` (`?year`) | `custom` (`?start_date&end_date`, max 366d). Filenames/labels use `period_slug`/`period_label`. Non-monthly payroll pulls overlapping months; leaves use full-overlap.
- `/print` (view), `/pdf` (dompdf A4 landscape), `/excel` (maatwebsite `ReportExport`/`ReportSheet`, `type=all`=6 sheets + payroll TOTAL, attendance sheet has `Dinas` column with % format at `K`). Keep print/PDF CSS dompdf-safe (tables only). Punishment `Nilai` = `N poin` for `points_deduction` with points, else Rp.

## Seeders & accounts

- `DatabaseSeeder` (all idempotent): `AttendanceSeeder` (`Staff` + `Jam Kerja Reguler` Mon–Fri 08:00–15:00; `admin@bumdesma.test`/`password` skipped when `APP_ENV=production`), `AttendanceLocationSeeder`, `KaryawanBaruSeeder` (`KRY-007`–`KRY-013` in ALL envs, `12345678`: `zila|sus|andry|oka|ludi|zaeni|tamami@bumdesma.test`).
- `KRY-001`–`KRY-006` deleted — don't reintroduce. `php artisan demo:purge [--force]` deactivates + soft-deletes (history kept).
- Prod admin: `php artisan admin:create` (`--name/--email/--password`). `.env.example` is `APP_ENV=production`, so no demo login there.
- `php artisan db:backup --keep=7` (daily 01:00 via scheduler; needs external `schedule:run` — Railway = separate Cron service; mysql-only, needs `mysqldump`; `storage/app/backups`, persistent only under `/data`).

## Test / env gotchas

- Local DB is MySQL (`bumdesma_tarub`). No SQLite fallback; `phpunit.xml` sqlite lines are commented out → tests hit real MySQL unless overridden.
- Every Feature test except `ExampleTest`/`LogoutTest` uses `RefreshDatabase` — never run against dev DB; use scratch DB (PowerShell syntax, this is Windows): `$env:DB_DATABASE='bumdesma_test'; $env:DB_PASSWORD='...'; php artisan test --filter=...`. Only `ExampleTest` (`GET /`→200) and `LogoutTest` are DB-safe.
- Public pages fully static (no DB reads).
- Uploads: `public` disk `employee-photos` via `asset('storage/...')` — run `storage:link`; if 404, `public/storage` is a stale file/link to another project — delete and re-link. Leave attachments on `local` disk (`leave-attachments`), auth-gated download only. Profile photo has 1-week cooldown (`ProfileController::photoCooldown`) — intentional.

## Deploy

- Railway: set `APP_URL` to public domain; `TrustProxies::$proxies='*'`; attendance `fetch` uses relative URLs (`route(...,[],false)`) — keep. Volume at `/data` + `STORAGE_PATH=/data` (`bootstrap/app.php`; `storage:link` follows). Prod checklist in `.env.example`.
- Vercel (`vercel-php@0.6.2`, `api/index.php`→`public/index.php`; static `/build|images|favicon|robots`): baked `SESSION_DRIVER=cookie`, `LOG_CHANNEL=stderr`, `VIEW_COMPILED_PATH=/tmp/views`; `bootstrap/app.php` uses `/tmp/laravel-storage` when `VERCEL` set. Still set `APP_KEY/APP_URL/DB_*/MAIL_*`; one-off `migrate --force --seed` + `admin:create` from local. Uploads ephemeral, scheduler/backup don't run.
