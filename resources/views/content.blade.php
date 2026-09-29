<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $page['judul'] }} BUMDESMA LKD TARUB">
    <title>{{ $page['judul'] }} | BUMDESMA LKD TARUB</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *{box-sizing:border-box}html{scroll-behavior:smooth;overflow-x:clip}body{margin:0;font-family:'Plus Jakarta Sans',Arial,sans-serif;color:#fff;background:#2e4a75;overflow-x:clip}
        img{height:auto;max-width:100%}
        a{text-decoration:none;color:inherit}
        a:focus-visible,.btn:focus-visible,.nav-toggle:focus-visible{outline:3px solid #3fb3f6;outline-offset:3px}
        .hero{position:relative;overflow:hidden;background:#2e4a75}
        .hero>*{position:relative;z-index:1}
        .orb{position:absolute;z-index:0;border-radius:50%;filter:blur(70px);opacity:.55;pointer-events:none}
        .orb.o1{width:420px;height:420px;left:-140px;top:-120px;background:#3fb3f6}
        .orb.o2{width:360px;height:360px;right:-120px;bottom:-140px;background:#1e5aa8}
        .nav{width:min(1200px,calc(100% - 80px));margin:0 auto;display:flex;align-items:center;gap:20px;padding:24px 0}
        .brand{display:inline-flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:15px;letter-spacing:.02em;white-space:nowrap}
        .brand img{width:42px;height:42px;object-fit:contain;background:#fff;border-radius:12px;padding:4px}
        .brand small{display:block;font-size:10px;font-weight:700;letter-spacing:.22em;color:#8dd0f7}
        .nav-links{display:flex;align-items:center;gap:28px;margin-left:auto}
        .nav-links a{font-size:15px;color:#e8eef6;padding:10px 2px;white-space:nowrap}
        .nav-links a.active{color:#4db8ff}
        .nav-links a.login-link{display:none;color:#3fb3f6;font-weight:700}
        .nav-cta{margin-left:8px;display:inline-block;padding:10px 26px;border:1px solid #3fb3f6;border-radius:999px;color:#fff;font-size:15px;font-weight:700;white-space:nowrap}
        .nav-toggle{display:none;margin-left:auto;background:none;border:1px solid rgba(255,255,255,.4);border-radius:10px;color:#fff;font-size:22px;padding:6px 12px;cursor:pointer}
        .hero-body{width:min(1200px,calc(100% - 80px));margin:0 auto;display:grid;grid-template-columns:1.05fr .95fr;align-items:center;gap:40px;padding:36px 0 72px}
        .eyebrow{display:inline-flex;align-items:center;gap:10px;color:#8dd0f7;font-size:12px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;margin:0 0 16px}
        .eyebrow::before{content:"";width:25px;height:2px;background:#3fb3f6}
        .hero-title{margin:0;font-size:clamp(34px,5.2vw,64px);line-height:1.05;font-weight:800;letter-spacing:-.02em;overflow-wrap:break-word}
        .hero-sub{margin:20px 0 0;color:#a9bedd;font-size:clamp(15px,2.4vw,19px);line-height:1.6;max-width:560px}
        .crumbs{display:flex;flex-wrap:wrap;gap:8px;margin-top:28px;font-size:13px;color:#a9bedd}
        .crumbs a{color:#e8eef6}.crumbs a:hover{color:#4db8ff}
        .btn-row{display:flex;flex-wrap:wrap;gap:12px;margin-top:32px}
        .btn{display:inline-block;padding:14px 34px;font-size:16px;font-weight:700;border-radius:999px;border:1px solid #3fb3f6;transition:.2s;min-height:52px}
        .btn.primary{background:#3fb3f6;color:#fff;box-shadow:0 8px 20px rgba(63,179,246,.35)}
        .btn.primary:hover{background:#2aa5ef;transform:translateY(-2px)}
        .btn.ghost{color:#fff;background:transparent}
        .btn.ghost:hover{background:rgba(255,255,255,.1)}
        .art{display:flex;justify-content:center;min-width:0}
        .frame{background:#e9f6f0;border-radius:28px;padding:22px;width:100%;max-width:520px;box-shadow:0 20px 50px rgba(0,0,0,.25)}
        .frame img{width:100%;max-height:340px;object-fit:cover;background:#fff;border-radius:18px}
        .frame figcaption{margin:14px 4px 0;color:#2e4a75;font-size:13px;font-weight:700}
        .section{background:#fff;color:#2e4a75;padding:84px 0}
        .wrap{width:min(1200px,calc(100% - 80px));margin:0 auto}
        .split{display:grid;grid-template-columns:.9fr 1.1fr;gap:56px;align-items:start}
        .split h2{margin:0;font-size:clamp(28px,4vw,44px);line-height:1.12}
        .split h2 em{font-style:normal;color:#1e5aa8}
        .copy p{color:#64748b;font-size:15px;line-height:1.7;margin:0 0 16px}
        .highlight{margin-top:64px;background:#f4f8fc;border:1px solid #dbe5f0;border-radius:20px;padding:40px}
        .highlight h3{margin:0 0 6px;font-size:22px}
        .highlight>p{margin:0 0 24px;color:#64748b;font-size:14px}
        .mini-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
        .mini{background:#fff;border:1px solid #dbe5f0;border-radius:16px;padding:24px}
        .mini-number{display:inline-grid;place-items:center;width:38px;height:38px;border-radius:12px;background:#eaf4ff;color:#1e5aa8;font-weight:800;font-size:15px}
        .mini strong{display:block;margin:14px 0 6px;font-size:16px}
        .mini p{margin:0;color:#64748b;font-size:13px;line-height:1.6}
        .back-row{margin-top:36px;display:flex;flex-wrap:wrap;gap:12px}
        .kontak{background:#243d63;padding:72px 0}
        .kontak-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}
        .kontak h2{margin:0 0 12px;font-size:clamp(26px,3.5vw,40px)}
        .kontak p{color:#a9bedd;margin:0 0 8px;font-size:15px}
        .kontak .line{display:flex;align-items:center;gap:12px;margin-top:14px;color:#e8eef6;font-size:14px}
        .kontak .line i{color:#3fb3f6;font-size:20px}
        footer{background:#1f3354;color:#8fa3c2;font-size:12px}
        footer .wrap{display:flex;justify-content:space-between;gap:12px;padding:18px 0;flex-wrap:wrap;width:min(1200px,calc(100% - 80px));margin:0 auto}
        .foot-links{display:flex;flex-wrap:wrap;gap:6px 18px}
        .foot-links a:hover{color:#4db8ff}
        @media(max-width:900px){
            .hero-body{grid-template-columns:1fr;gap:48px}
            .split{grid-template-columns:1fr;gap:28px}
            .kontak-grid{grid-template-columns:1fr}
            .mini-grid{grid-template-columns:1fr 1fr}
        }
        @media(max-width:680px){
            .nav{width:calc(100% - 40px);padding:18px 0}
            .nav-toggle{display:block}
            .nav-cta{display:none}
            .nav-links{position:absolute;top:70px;left:20px;right:20px;display:none;flex-direction:column;align-items:stretch;gap:0;background:#243d63;border:1px solid rgba(255,255,255,.14);border-radius:14px;padding:8px 18px;box-shadow:0 18px 40px rgba(0,0,0,.35);z-index:30;margin-left:0}
            .nav-links.open{display:flex}
            .nav-links a{padding:14px 4px;border-bottom:1px solid rgba(255,255,255,.1);font-size:16px}
            .nav-links a:last-child{border-bottom:0}
            .nav-links a.login-link{display:block}
            .hero-body{width:calc(100% - 40px);padding:12px 0 56px}
            .btn{width:100%;text-align:center}
            .frame{border-radius:20px;padding:14px}
            .frame img{max-height:230px}
            .section{padding:60px 0}
            .wrap{width:calc(100% - 40px)}
            .highlight{padding:24px;margin-top:44px}
            .mini-grid{grid-template-columns:1fr}
            .kontak{padding:56px 0}
            footer .wrap{width:calc(100% - 40px)}
        }
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
    </style>
</head>
<body>
    <div class="hero">
        <span class="orb o1" aria-hidden="true">
        </span>
        <span class="orb o2" aria-hidden="true">
        </span>
        <nav class="nav" aria-label="Navigasi utama">
            <a class="brand" href="{{ url('/') }}" aria-label="Kembali ke beranda">
                <img src="{{ asset('images/logo-bumdesma.png') }}" alt="Logo BUMDESMA LKD TARUB">
                <span>BUMDESMA<small>LKD TARUB</small>
                </span>
            </a>
            <div class="nav-links" id="navLinks">
                <a href="{{ url('/') }}">Beranda</a>
                <a
                    class="{{ in_array($pageKey, ['profil', 'tentang', 'profil-lkd']) ? 'active' : '' }}"
                    href="{{ url('/profil') }}">Profil</a>
                <a class="{{ $pageKey === 'kegiatan' ? 'active' : '' }}" href="{{ url('/kegiatan') }}">Kegiatan</a>
                <a class="{{ $pageKey === 'program' ? 'active' : '' }}" href="{{ url('/program') }}">Program</a>
                <a class="{{ $pageKey === 'potensi' ? 'active' : '' }}" href="{{ url('/potensi') }}">Potensi</a>
                <a class="{{ $pageKey === 'berita' ? 'active' : '' }}" href="{{ url('/berita') }}">Berita</a>
                <a class="{{ $pageKey === 'galeri' ? 'active' : '' }}" href="{{ url('/galeri') }}">Galeri</a>
                <a class="{{ $pageKey === 'kontak' ? 'active' : '' }}" href="{{ url('/kontak') }}">Kontak</a>
                <a class="login-link" href="{{ route('login') }}">Masuk →</a>
            </div>
            <a class="nav-cta" href="{{ route('login') }}">Masuk</a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="navLinks" id="navToggle">
                <i class="bi bi-list">
                </i>
                <span
                    class="sr-only"
                    style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Buka
                    navigasi</span>
            </button>
        </nav>
        <div class="hero-body">
            <div>
                <p class="eyebrow">{{ $page['label'] }}</p>
                <h1 class="hero-title">{{ $page['judul'] }}</h1>
                <p class="hero-sub">{{ $page['intro'] ?? $page['ringkasan'] }}</p>
                <nav class="crumbs" aria-label="Navigasi halaman">
                    <a href="{{ url('/') }}">Beranda</a>
                    <span>/</span>
                    <span>
                        {{ $page['label'] }}
                    </span>
                </nav>
                <div class="btn-row">
                    <a class="btn primary" href="{{ url('/') }}">Kembali ke Beranda</a>
                    <a class="btn ghost" href="{{ url('/kontak') }}">Hubungi Kami</a>
                </div>
            </div>
            <div class="art">
                <figure class="frame" style="margin:0">
                    <img src="{{ $page['gambar_url'] }}" alt="{{ $page['judul'] }}">
                    <figcaption>{{ $page['label'] }} — BUMDESMA LKD TARUB</figcaption>
                </figure>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="wrap">
            <div class="split">
                <div>
                    <p class="eyebrow" style="color:#1e5aa8">BUMDESMA LKD TARUB</p>
                    <h2>Ruang informasi<br>
                        <em>untuk semua.</em>
                    </h2>
                </div>
                <div class="copy">
                    <p>Website resmi ini menjadi pusat informasi, promosi, dan dokumentasi digital BUMDESMA LKD TARUB. Temukan cerita, program, dan potensi yang tumbuh dari masyarakat Tarub.</p>
                    <p>Halaman ini disiapkan sebagai ruang yang terus berkembang. Informasi kegiatan, berita, dan produk lokal dapat diperbarui mengikuti aktivitas terbaru BUMDESMA.</p>
                </div>
            </div>
            <div class="highlight">
                <h3>Mengapa halaman ini penting?</h3>
                <p>Tiga alasan ruang informasi ini dihadirkan untuk masyarakat.</p>
                <div class="mini-grid">
                    <div class="mini">
                        <span class="mini-number">01</span>
                        <strong>Informasi terpercaya</strong>
                        <p>Kabar dan pengumuman BUMDESMA untuk masyarakat.</p>
                    </div>
                    <div class="mini">
                        <span class="mini-number">02</span>
                        <strong>Potensi lokal</strong>
                        <p>Ruang untuk memperkenalkan karya dan usaha warga.</p>
                    </div>
                    <div class="mini">
                        <span class="mini-number">03</span>
                        <strong>Kolaborasi terbuka</strong>
                        <p>Menghubungkan desa dengan mitra dan kesempatan baru.</p>
                    </div>
                </div>
                <div class="back-row">
                    <a
                        class="btn primary"
                        style="border-color:#1e5aa8;background:#1e5aa8"
                        href="{{ url('/') }}">Jelajahi
                        Beranda</a>
                </div>
            </div>
        </div>
    </section>
    <section class="kontak" id="kontak">
        <div class="wrap kontak-grid">
            <div>
                <h2>Kontak Kami</h2>
                <p>Terbuka untuk informasi, kolaborasi, dan cerita baru dari masyarakat Tarub.</p>
                <div class="line">
                    <i class="bi bi-geo-alt">
                    </i>
                    <span>Tarub, Kabupaten Tegal, Jawa Tengah</span>
                </div>
                <div class="line">
                    <i class="bi bi-clock">
                    </i>
                    <span>Senin - Jumat, 08.00 - 15.00 WIB</span>
                </div>
                <div class="line">
                    <i class="bi bi-envelope">
                    </i>
                    <a href="mailto:info@bumdesmatarub.id">info@bumdesmatarub.id</a>
                </div>
            </div>
        </div>
    </section>
    <footer>
        <div class="wrap">
            <span>© <span id="yr">2026</span> BUMDESMA LKD TARUB. All Rights Reserved.</span>
            <span class="foot-links">
                <a href="{{ url('/profil') }}">Profil</a>
                <a href="{{ url('/program') }}">Program</a>
                <a href="{{ url('/kegiatan') }}">Kegiatan</a>
                <a href="{{ url('/berita') }}">Berita</a>
                <a href="{{ url('/galeri') }}">Galeri</a>
                <a href="{{ url('/kontak') }}">Kontak</a>
            </span>
        </div>
    </footer>
    <script>document.getElementById('yr').textContent = new Date().getFullYear();(function(){var t=document.getElementById('navToggle'),l=document.getElementById('navLinks');if(!t||!l)return;t.addEventListener('click',function(){var o=l.classList.toggle('open');t.setAttribute('aria-expanded',o?'true':'false')});l.addEventListener('click',function(e){if(e.target.closest('a')){l.classList.remove('open');t.setAttribute('aria-expanded','false')}})})();</script>
</body>
</html>
