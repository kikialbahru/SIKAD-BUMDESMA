# Deploy SIKAD ke VPS (Docker + Caddy)

Panduan ini menjalankan aplikasi di satu VPS memakai Docker Compose:
`app` (Laravel), `db` (MySQL 8), `caddy` (HTTPS + reverse proxy), dan
`scheduler` (backup harian). Semua data persisten berada di volume Docker
atau folder `storage/` di server.

## 1. Siapkan VPS dan DNS

- VPS Ubuntu 22.04/24.04, minimal 2 GB RAM, 2 vCPU, 20 GB disk.
- Arahkan DNS `A` record domain (mis. `sikad.example.com`) ke IP VPS.
- Buka port `22`, `80`, `443` di firewall VPS.

Install Docker Engine + Compose plugin:

```bash
sudo apt update
sudo apt install -y ca-certificates curl git
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

## 2. Ambil kode dan konfigurasi

```bash
sudo mkdir -p /var/www
sudo chown "$USER":"$USER" /var/www
cd /var/www
git clone https://github.com/kikialbahru/SIKAD-BUMDESMA.git sikad
cd sikad
cp .env.vps.example .env
nano .env
```

Isi minimal di `.env`:

- `APP_KEY` — generate dengan:
  ```bash
  echo "base64:$(openssl rand -base64 32)"
  ```
- `APP_URL` dan `APP_DOMAIN` — domain asli.
- `DB_PASSWORD` dan `DB_ROOT_PASSWORD` — password kuat dan berbeda.
- `MAIL_*` — SMTP asli untuk fitur lupa password.

Jangan commit `.env`.

## 3. Migrasi database dari Railway (jika ada data lama)

Di komputer lokal yang punya `mysqldump`, buka tunnel ke MySQL Railway:

```bash
railway login
railway link
railway connect MySQL --tunnel-only -P 3307
```

Biarkan terminal itu terbuka. Di terminal lain, dump database memakai
kredensial yang dicetak oleh tunnel. Ganti `railway` dengan nama database
dari `railway variables --service MySQL` jika berbeda:

```bash
mysqldump -h 127.0.0.1 -P 3307 -u root -p \
  --single-transaction --routines --default-character-set=utf8mb4 \
  railway > railway.sql
```

Upload `railway.sql` ke VPS (mis. `scp railway.sql user@IP:/var/www/sikad/`).

## 4. Nyalakan database dan restore

```bash
cd /var/www/sikad
docker compose up -d db
docker compose ps
```

Tunggu sampai `db` berstatus `healthy`, lalu restore:

```bash
docker compose exec -T db sh -c \
  'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  < railway.sql
```

Jika ingin mulai dari database kosong, lewati langkah restore; aplikasi akan
menjalankan `migrate --seed` otomatis saat start.

## 5. Pindahkan foto lama (jika masih ada)

Foto di Railway tidak persisten. Sebelum service lama dihapus, salin yang
masih ada:

```bash
railway service files download /app/storage/app/public ./storage-backup \
  --service SIKAD-BUMDESMA --environment production --overwrite
```

Upload folder `storage-backup` ke VPS, lalu:

```bash
rsync -av ./storage-backup/ /var/www/sikad/storage/app/public/
```

## 6. Build dan jalankan aplikasi

```bash
cd /var/www/sikad
docker compose build
docker compose up -d
docker compose logs -f app
```

Caddy akan meminta sertifikat HTTPS otomatis setelah DNS mengarah ke VPS.
Cek:

```bash
curl -I https://sikad.example.com
docker compose logs -f caddy
```

Jika database masih kosong, buat admin pertama:

```bash
docker compose exec app php artisan admin:create \
  --name="Administrator" \
  --email="admin@example.com" \
  --password="password-kuat-minimal-8"
```

## 7. Cek scheduler dan backup

```bash
docker compose exec scheduler php artisan schedule:list
docker compose exec app php artisan db:backup --keep=7
ls -lh storage/app/backups
```

Backup harian berjalan pukul `01:00` waktu server (container diset
`Asia/Jakarta`). File backup tersimpan di `storage/app/backups` dan ikut
persisten.

## 8. Update aplikasi

```bash
cd /var/www/sikad
git pull
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

Kalau mengubah `.env`:

```bash
docker compose up -d --force-recreate
docker compose exec app php artisan optimize:clear
```

## Catatan produksi

- `db` tidak membuka port ke host; hanya bisa diakses dari network Compose.
- Jangan pernah commit `.env` atau membagikan `DB_PASSWORD`.
- Ganti password MySQL Railway yang pernah terekspos.
- `storage/app/public` berisi foto absen, profil, dan kunjungan — ikutkan
  folder ini dalam backup VPS.
- Untuk trafik besar, ganti `php artisan serve` dengan PHP-FPM + Nginx.
  Untuk skala BUMDESMA saat ini, setup ini cukup.
- Jika pindah dari Railway, hapus service aplikasi lama setelah database
  dan foto berhasil dimigrasi. Jangan hapus MySQL sebelum dump diverifikasi.
