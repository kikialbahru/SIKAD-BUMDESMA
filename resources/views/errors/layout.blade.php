@php
    use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
    $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
    $pages = [
        404 => ['title' => 'Halaman tidak ditemukan', 'text' => 'Alamat yang Anda tuju tidak tersedia atau sudah dipindahkan. Periksa kembali URL atau kembali ke beranda.'],
        403 => ['title' => 'Akses ditolak', 'text' => 'Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator jika Anda merasa ini keliru.'],
        419 => ['title' => 'Sesi kedaluwarsa', 'text' => 'Sesi Anda telah berakhir. Silakan muat ulang halaman dan coba lagi.'],
        500 => ['title' => 'Terjadi gangguan sistem', 'text' => 'Gangguan telah tercatat di log sistem. Silakan coba beberapa saat lagi atau hubungi administrator.'],
        503 => ['title' => 'Sistem dalam perawatan', 'text' => 'Aplikasi sedang dalam pemeliharaan terjadwal. Silakan kembali beberapa saat lagi.'],
    ];
    $page = $pages[$code] ?? $pages[500];
    $code = isset($pages[$code]) ? $code : 500;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $page['title'] }} | BUMDESMA LKD TARUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --blue: #106fae; --blue-dark: #075487; --blue-light: #159bd0; --ink: #173247; }
        body { min-height: 100vh; background: #eef5fa; color: var(--ink); }
        .error-code { font-size: clamp(4rem, 12vw, 7rem); font-weight: 800; line-height: 1; background: linear-gradient(135deg, var(--blue-dark), var(--blue-light)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .btn-primary { background: linear-gradient(135deg, var(--blue-dark), var(--blue-light)); border: 0; }
    </style>
</head>
<body>
    <main class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
        <div class="text-center" style="max-width: 520px;">
            <div class="error-code">{{ $code }}</div>
            <h1 class="h3 fw-bold mt-2 mb-2">{{ $page['title'] }}</h1>
            <p class="text-secondary mb-4">{{ $page['text'] }}</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="btn btn-primary" href="{{ url('/') }}">
                    <i class="bi bi-house me-2">
                    </i>Kembali ke Beranda</a>
                <a class="btn btn-outline-secondary" href="{{ route('login') }}">
                    <i class="bi bi-box-arrow-in-right me-2">
                    </i>Halaman Login</a>
            </div>
            <p class="text-secondary small mt-4 mb-0">BUMDESMA LKD TARUB — Sistem Informasi Kehadiran</p>
        </div>
    </main>
</body>
</html>
