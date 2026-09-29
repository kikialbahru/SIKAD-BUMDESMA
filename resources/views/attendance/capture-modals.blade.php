@unless(auth()->user()->isAdmin())
@php($captureOffice = \App\Models\AttendanceLocation::active())
@foreach([['in', 'Masuk', 'masuk', route('attendance.check-in', [], false)], ['out', 'Pulang', 'pulang', route('attendance.check-out', [], false)]] as [$suffix, $label, $type, $url])
@php($modalOffice = $captureOffice ? ['name' => $captureOffice->nama, 'lat' => (float) $captureOffice->lintang, 'lng' => (float) $captureOffice->bujur, 'radius' => (int) $captureOffice->radius_meter, 'enforce' => $suffix === 'out' ? (bool) $captureOffice->wajib_radius_pulang : (bool) $captureOffice->wajib_radius] : null)
<div
    class="modal fade"
    id="check{{ ucfirst($suffix) }}Modal"
    tabindex="-1"
    data-url="{{ $url }}"
    data-type="{{ $type }}"
    data-office='@json($modalOffice)'>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-camera me-2">
                    </i>Absen {{ $label }}
                </h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup">
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" data-role="error">
                </div>
                <div data-role="step-camera">
                    <video
                        data-role="video"
                        class="w-100 rounded-3 bg-dark"
                        style="max-height:320px;object-fit:cover"
                        playsinline
                        muted>
                    </video>
                    <canvas data-role="canvas" class="d-none">
                    </canvas>
                    <div class="text-center small py-3" data-role="camera-hint">
                        <span class="text-secondary">Menyiapkan kamera...</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg" type="button" data-role="btn-capture" disabled>
                            <i class="bi bi-camera me-2">
                            </i>AMBIL FOTO</button>
                        <button class="btn btn-outline-secondary" type="button" data-role="btn-start">
                            <i class="bi bi-arrow-clockwise me-2">
                            </i>Coba Lagi Kamera</button>
                        <div
                            class="form-text text-center">Foto
                            wajib
                            diambil
                            langsung
                            dari
                            kamera
                            —
                            tidak
                            dapat
                            upload
                            file.</div>
                    </div>
                </div>
                <div class="d-none" data-role="step-preview">
                    <img
                        data-role="preview"
                        class="img-fluid rounded-3 border w-100 mb-3"
                        style="max-height:300px;object-fit:cover"
                        alt="Preview foto absensi">
                    <div class="p-3 rounded-3 bg-light small" data-role="gps-box">
                        <div data-role="gps-status">
                            <span class="spinner-border spinner-border-sm me-2">
                            </span>Sedang mendapatkan lokasi...</div>
                        <div class="text-secondary mt-1" data-role="gps-detail">
                        </div>
                        <div
                            class="form-text">Akurasi
                            membaik
                            setelah
                            beberapa
                            detik
                            —
                            tekan
                            tombol
                            di
                            bawah
                            untuk
                            memuat
                            ulang.</div>
                        <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-role="btn-gps">
                            <i class="bi bi-crosshair me-1">
                            </i>Perbarui Lokasi</button>
                    </div>
                    <div class="form-text mt-2">
                        <i class="bi bi-clock me-1">
                        </i>Waktu absensi dicatat otomatis oleh server (WIB).</div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-light border flex-fill" type="button" data-role="btn-retake">
                            <i class="bi bi-arrow-counterclockwise me-2">
                            </i>Ambil Ulang</button>
                        <button class="btn btn-success flex-fill" type="button" data-role="btn-confirm">
                            <i class="bi bi-check-circle me-2">
                            </i>KONFIRMASI ABSENSI</button>
                    </div>
                </div>
                <div class="d-none text-center" data-role="step-done">
                    <div class="display-4 text-success mb-2">
                        <i class="bi bi-check-circle-fill">
                        </i>
                    </div>
                    <h5 class="fw-bold">ABSENSI BERHASIL</h5>
                    <img
                        data-role="done-photo"
                        class="img-fluid rounded-3 border my-3"
                        style="max-height:220px;object-fit:cover"
                        alt="Bukti foto absensi">
                    <dl class="row small text-start mb-3">
                        <dt class="col-4 text-secondary">Jenis</dt>
                        <dd class="col-8" data-role="done-type">-</dd>
                        <dt class="col-4 text-secondary">Tanggal</dt>
                        <dd class="col-8" data-role="done-date">-</dd>
                        <dt class="col-4 text-secondary">Waktu</dt>
                        <dd class="col-8" data-role="done-time">-</dd>
                        <dt class="col-4 text-secondary">Lokasi</dt>
                        <dd class="col-8" data-role="done-location">-</dd>
                        <dt class="col-4 text-secondary">Koordinat</dt>
                        <dd class="col-8" data-role="done-coords">-</dd>
                        <dt class="col-4 text-secondary">Akurasi</dt>
                        <dd class="col-8" data-role="done-accuracy">-</dd>
                        <dt class="col-4 text-secondary">Status</dt>
                        <dd class="col-8" data-role="done-status">-</dd>
                    </dl>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" data-role="done-link" href="#">
                            <i class="bi bi-eye me-2">
                            </i>Lihat Detail</a>
                        <button
                            class="btn btn-primary"
                            type="button"
                            data-role="btn-reload">Tutup
                            &amp;
                            Muat
                            Ulang</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
(function () {
    const CSRF = '{{ csrf_token() }}';

    function haversine(lat1, lng1, lat2, lng2) {
        const R = 6371000, rad = Math.PI / 180;
        const a = Math.sin((lat2 - lat1) * rad / 2) ** 2
            + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin((lng2 - lng1) * rad / 2) ** 2;
        return 2 * R * Math.asin(Math.min(1, Math.sqrt(a)));
    }

    function setup(modal) {
        const q = (sel) => modal.querySelector('[data-role="' + sel + '"]');
        const video = q('video'), canvas = q('canvas'), errorBox = q('error');
        const office = JSON.parse(modal.dataset.office || 'null');
        let stream = null, photoBlob = null, coords = null;

        function showError(msg) { errorBox.textContent = msg; errorBox.classList.remove('d-none'); }
        function hideError() { errorBox.classList.add('d-none'); }
        function showStep(name) {
            ['step-camera', 'step-preview', 'step-done'].forEach((s) => q(s).classList.toggle('d-none', s !== 'step-' + name));
        }
        function stopCamera() {
            if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
            video.srcObject = null;
        }

        async function startCamera() {
            hideError();
            q('camera-hint').innerHTML = '<span class="text-secondary">Menyiapkan kamera...</span>';
            q('btn-capture').disabled = true;
            stopCamera();
            if (!navigator.mediaDevices?.getUserMedia) {
                q('camera-hint').innerHTML = '<span class="text-danger">Perangkat tidak mendukung kamera — absensi lewat foto tidak dapat dilakukan dari perangkat ini.</span>';
                return;
            }
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                video.srcObject = stream;
                await video.play();
                q('camera-hint').innerHTML = '<span class="text-success">🟢 Kamera siap — posisikan wajah lalu tekan AMBIL FOTO.</span>';
                q('btn-capture').disabled = false;
            } catch (e) {
                q('camera-hint').innerHTML = '<span class="text-danger">Kamera diperlukan untuk mengambil foto absensi. Silakan izinkan akses kamera lalu tekan Coba Lagi Kamera.</span>';
            }
        }

        function requestGps() {
            const status = q('gps-status'), detail = q('gps-detail');
            status.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sedang mendapatkan lokasi...';
            detail.textContent = '';
            coords = null;
            if (!navigator.geolocation) {
                status.innerHTML = '<span class="text-danger">Perangkat tidak mendukung GPS.</span>';
                return;
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                coords = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: Math.round(pos.coords.accuracy || 0) };
                let extra = '';
                if (office) {
                    const d = Math.round(haversine(coords.lat, coords.lng, office.lat, office.lng));
                    coords.dist = d;
                    extra = !office.enforce
                        ? '<div class="text-info fw-semibold mt-1">ℹ️ Lokasi akan dicatat (±' + d + ' m dari ' + office.name + ')</div>'
                        : (d <= office.radius
                            ? '<div class="text-success fw-semibold mt-1">🟢 Dalam area absensi (±' + d + ' m dari ' + office.name + ')</div>'
                            : '<div class="text-danger fw-semibold mt-1">🔴 Di luar area absensi (±' + d + ' m, radius ' + office.radius + ' m)</div>');
                }
                status.innerHTML = '<span class="text-success fw-semibold">🟢 Lokasi ditemukan</span>' + extra;
                detail.textContent = 'Akurasi ±' + coords.acc + ' meter · ' + coords.lat.toFixed(6) + ', ' + coords.lng.toFixed(6);
            }, function (err) {
                status.innerHTML = '<span class="text-danger">Lokasi diperlukan untuk melakukan absensi. Silakan izinkan akses lokasi dan pastikan GPS aktif.</span>'
                    + (err && err.code === err.TIMEOUT ? ' <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-role="btn-gps-retry">Coba Lagi</button>' : '');
                const retry = modal.querySelector('[data-role="btn-gps-retry"]');
                retry?.addEventListener('click', requestGps);
            }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
        }

        function useBlob(blob, previewUrl) {
            photoBlob = blob;
            q('preview').src = previewUrl;
            stopCamera();
            showStep('preview');
            requestGps();
        }

        q('btn-capture').addEventListener('click', function () {
            if (!video.videoWidth) { showError('Kamera belum siap. Tunggu sebentar lalu coba lagi.'); return; }
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            canvas.toBlob(function (blob) {
                if (!blob) { showError('Foto gagal diproses. Silakan ambil foto kembali.'); return; }
                useBlob(blob, URL.createObjectURL(blob));
            }, 'image/jpeg', 0.85);
        });

        q('btn-retake').addEventListener('click', function () {
            photoBlob = null;
            showStep('camera');
            startCamera();
        });

        q('btn-confirm').addEventListener('click', async function () {
            hideError();
            if (!photoBlob) { showError('Ambil foto terlebih dahulu.'); return; }
            if (!coords) { showError('Lokasi tidak dapat ditemukan. Pastikan GPS aktif dan coba kembali.'); return; }
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan absensi...';
            try {
                const form = new FormData();
                form.append('foto', photoBlob, 'selfie.jpg');
                form.append('lintang', coords.lat);
                form.append('bujur', coords.lng);
                form.append('akurasi', coords.acc);
                const res = await fetch(modal.dataset.url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    credentials: 'same-origin',
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Absensi gagal disimpan. Coba lagi.');
                    throw new Error(msg);
                }
                modal.querySelector('[data-role="done-photo"]').src = URL.createObjectURL(photoBlob);
                modal.querySelector('[data-role="done-type"]').textContent = data.type === 'pulang' ? 'Absen Pulang' : 'Absen Masuk';
                modal.querySelector('[data-role="done-date"]').textContent = data.date || '-';
                modal.querySelector('[data-role="done-time"]').textContent = data.time || '-';
                modal.querySelector('[data-role="done-location"]').textContent = office ? office.name : 'Tercatat';
                modal.querySelector('[data-role="done-coords"]').textContent = data.lintang + ', ' + data.bujur;
                modal.querySelector('[data-role="done-accuracy"]').textContent = '±' + data.akurasi + ' meter';
                modal.querySelector('[data-role="done-status"]').textContent = data.status === 'late' ? 'Terlambat' : 'Hadir';
                modal.querySelector('[data-role="done-link"]').href = data.detail_url;
                showStep('done');
            } catch (e) {
                showError(e instanceof TypeError ? 'Tidak dapat terhubung ke server. Periksa koneksi internet lalu coba lagi.' : e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>KONFIRMASI ABSENSI';
            }
        });

        q('btn-reload').addEventListener('click', () => window.location.reload());
        q('btn-gps').addEventListener('click', requestGps);

        modal.addEventListener('shown.bs.modal', function () {
            hideError();
            photoBlob = null;
            showStep('camera');
            startCamera();
        });
        modal.addEventListener('hidden.bs.modal', stopCamera);
    }

    document.querySelectorAll('#checkInModal, #checkOutModal').forEach(setup);
})();
</script>
@endpush
@endunless
