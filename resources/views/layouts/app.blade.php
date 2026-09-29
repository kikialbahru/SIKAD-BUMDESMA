<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} | BUMDESMA LKD TARUB</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --blue: #106fae; --blue-soft: #eaf6ff; --ink: #173247; }
        body { background: #f5f9fc; color: var(--ink); }
        .sidebar { width: 255px; min-height: 100vh; background: linear-gradient(160deg, #075487, #159bd0); }
        .sidebar a { color: rgba(255,255,255,.78); border-radius: .65rem; } .sidebar a:hover, .sidebar a.active { color: #fff; background: rgba(255,255,255,.16); }
        .brand-dot { display: inline-grid; place-items: center; width: 38px; height: 38px; border: 1px solid #f5c34d; color: #f5c34d; }
        .brand-logo { width: 38px; height: 38px; object-fit: contain; }
        .stat-card { border: 0; border-left: 4px solid var(--blue); box-shadow: 0 8px 25px rgba(35,91,122,.08); } .stat-card .icon { color: var(--blue); font-size: 1.5rem; }
        .employee-thumb, .employee-avatar { display: grid; place-items: center; overflow: hidden; color: #106fae; background: #eaf6ff; font-weight: 700; }
        .employee-thumb { width: 42px; height: 42px; border-radius: 50%; }
        .employee-avatar { width: 150px; height: 150px; border-radius: 50%; font-size: 3rem; }
        .employee-thumb img, .employee-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .content { min-width: 0; } .mobile-nav { display: none; }
        .sidebar { transition: margin-left .25s ease; }
        body.sidebar-collapsed .sidebar { margin-left: -255px; }
        .mobile-drawer { width: 300px !important; max-width: 85vw; background: linear-gradient(160deg, #075487, #159bd0); border: 0; border-radius: 0 1.25rem 1.25rem 0; color: #fff; }
        .mobile-drawer .menu-label { color: #ffd75e; font-size: .72rem; letter-spacing: .14em; font-weight: 700; }
        .mnav-link { display: flex; align-items: center; gap: .7rem; padding: .55rem .6rem; border-radius: .7rem; color: rgba(255,255,255,.85) !important; text-decoration: none; font-weight: 500; border: 0; background: transparent; width: 100%; text-align: left; }
        .mnav-link:hover { background: rgba(255,255,255,.12); color: #fff !important; }
        .mnav-link.active { background: rgba(255,255,255,.16); color: #fff !important; font-weight: 700; }
        .mnav-ic { width: 36px; height: 36px; display: grid; place-items: center; background: rgba(255,255,255,.15); color: #fff; border-radius: .6rem; font-size: 1.05rem; flex-shrink: 0; }
        .mnav-link.active .mnav-ic { background: #fff; color: #075487; }
        .mnav-link.logout { background: #fff; color: #dc2626 !important; }
        .mnav-link.logout .mnav-ic { background: #fee2e2; color: #dc2626; }
        @media (max-width: 991.98px) { .sidebar { display: none; } .mobile-nav { display: block; } }
    </style>
</head>
<body>
<div class="d-flex min-vh-100">
    <aside class="sidebar d-none d-lg-block p-3 text-white flex-shrink-0">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none mb-4">@if (file_exists(public_path('images/logo-bumdesma.png')))<img src="{{ asset('images/logo-bumdesma.png') }}" alt="Logo BUMDESMA LKD TARUB" class="brand-logo">@else<span class="brand-dot">BT</span>@endif<span><strong class="d-block">BUMDESMA</strong><small class="text-warning">LKD TARUB</small></span></a>
        <small class="text-uppercase opacity-50">Menu utama</small>
        <nav class="nav flex-column gap-1 mt-2"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a><a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}"><i class="bi bi-calendar-check me-2"></i> Kehadiran</a><a class="nav-link {{ request()->routeIs('visits.*') ? 'active' : '' }}" href="{{ route('visits.index') }}"><i class="bi bi-geo-alt me-2"></i> Kunjungan</a><a class="nav-link {{ request()->routeIs('leave.*') ? 'active' : '' }}" href="{{ route('leave.index') }}"><i class="bi bi-calendar2-heart me-2"></i> Cuti & Sakit</a><a class="nav-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}" href="{{ route('payroll.index') }}"><i class="bi bi-wallet2 me-2"></i> Penggajian</a><a class="nav-link {{ request()->routeIs('recognition.*') ? 'active' : '' }}" href="{{ route('recognition.index') }}"><i class="bi bi-trophy me-2"></i> Reward & Punishment</a><a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart me-2"></i> Laporan</a>@if(auth()->user()->isAdmin())<small class="text-uppercase opacity-50 mt-4">Administrasi</small><a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people me-2"></i> Data Karyawan</a><a class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}"><i class="bi bi-calendar-event me-2"></i> Hari Libur</a><a class="nav-link {{ request()->routeIs('positions.*', 'work-schedules.*', 'salary-components.*') ? 'active' : '' }}" href="{{ route('positions.index') }}"><i class="bi bi-sliders me-2"></i> Master Data</a>@endif</nav>
    </aside>
    <div class="content flex-grow-1">
        <nav class="navbar bg-white border-bottom px-3 px-lg-4 py-3"><div class="container-fluid p-0"><button class="btn btn-outline-primary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"><i class="bi bi-list"></i></button><button class="btn btn-outline-secondary d-none d-lg-inline-block" id="sidebarToggle" title="Buka/tutup sidebar"><i class="bi bi-layout-sidebar"></i></button><span class="fw-semibold ms-2 ms-lg-2">{{ $title ?? 'Dashboard' }}</span><div class="dropdown me-2"><button class="btn btn-light position-relative" data-bs-toggle="dropdown" aria-label="Notifikasi"><i class="bi bi-bell"></i>@if(($notifUnread ?? 0) > 0)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">{{ $notifUnread > 9 ? '9+' : $notifUnread }}</span>@endif</button><ul class="dropdown-menu dropdown-menu-end p-2" style="width:320px;max-width:85vw"><li class="d-flex justify-content-between align-items-center px-2 py-1"><strong class="small">Notifikasi</strong>@if(($notifUnread ?? 0) > 0)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-link btn-sm text-decoration-none p-0" type="submit">Tandai dibaca</button></form>@endif</li><li><hr class="dropdown-divider"></li>@forelse(($notifRecent ?? collect()) as $notif)<li><a class="dropdown-item d-flex gap-2 py-2 px-2 rounded {{ is_null($notif->read_at) ? 'bg-light' : '' }}" href="{{ route('notifications.read', $notif->id) }}"><i class="bi {{ $notif->data['icon'] ?? 'bi-info-circle' }} mt-1"></i><span><span class="d-block small fw-semibold">{{ $notif->data['title'] ?? 'Notifikasi' }}</span><span class="d-block small text-secondary text-wrap">{{ $notif->data['body'] ?? '' }}</span><span class="d-block small text-secondary">{{ $notif->created_at->diffForHumans() }}</span></span></a></li>@empty<li class="px-2 py-3 text-center text-secondary small">Belum ada notifikasi.</li>@endforelse</ul></div><div class="dropdown ms-auto"><button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 overflow-hidden mw-100" style="max-width:52vw" data-bs-toggle="dropdown">@php($navEmployee = auth()->user()->employee)@if($navEmployee?->foto)<img src="{{ asset('storage/'.$navEmployee->photo) }}" class="rounded-circle flex-shrink-0" width="26" height="26" style="object-fit:cover" alt="Foto profil">@else<i class="bi bi-person-circle flex-shrink-0"></i>@endif<span class="d-none d-sm-inline text-truncate">{{ auth()->user()->name }}</span></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2"></i>Profil Saya</a></li><li><hr class="dropdown-divider"></li><li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button></form></li></ul></div></div></nav>
        <div class="offcanvas offcanvas-start mobile-drawer" tabindex="-1" id="mobileMenu">
            <div class="offcanvas-header align-items-center">
                <span class="menu-label">MENU</span>
                <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column pt-0">
                <nav class="d-flex flex-column gap-1">
                    <a class="mnav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="mnav-ic"><i class="bi bi-house"></i></span>Dashboard</a>
                    <a class="mnav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}"><span class="mnav-ic"><i class="bi bi-calendar-check"></i></span>Kehadiran</a>
                    <a class="mnav-link {{ request()->routeIs('visits.*') ? 'active' : '' }}" href="{{ route('visits.index') }}"><span class="mnav-ic"><i class="bi bi-geo-alt"></i></span>Kunjungan</a>
                    <a class="mnav-link {{ request()->routeIs('leave.*') ? 'active' : '' }}" href="{{ route('leave.index') }}"><span class="mnav-ic"><i class="bi bi-calendar2-heart"></i></span>Cuti & Sakit</a>
                    <a class="mnav-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}" href="{{ route('payroll.index') }}"><span class="mnav-ic"><i class="bi bi-wallet2"></i></span>Penggajian</a>
                    <a class="mnav-link {{ request()->routeIs('recognition.*') ? 'active' : '' }}" href="{{ route('recognition.index') }}"><span class="mnav-ic"><i class="bi bi-trophy"></i></span>Reward & Punishment</a>
                    <a class="mnav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span class="mnav-ic"><i class="bi bi-bar-chart"></i></span>Laporan</a>
                    @if(auth()->user()->isAdmin())
                        <a class="mnav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><span class="mnav-ic"><i class="bi bi-people"></i></span>Data Karyawan</a>
                        <a class="mnav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}"><span class="mnav-ic"><i class="bi bi-calendar-event"></i></span>Hari Libur</a>
                        <a class="mnav-link {{ request()->routeIs('positions.*', 'work-schedules.*', 'salary-components.*') ? 'active' : '' }}" href="{{ route('positions.index') }}"><span class="mnav-ic"><i class="bi bi-sliders"></i></span>Master Data</a>
                    @endif
                </nav>
                <form class="mt-auto pt-3" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="mnav-link logout" type="submit"><span class="mnav-ic"><i class="bi bi-box-arrow-right"></i></span>Log Out</button>
                </form>
            </div>
        </div>
        <main class="p-3 p-lg-4">@yield('content')</main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('success') || session('error') || $errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: @json(session('success') ? 'success' : 'error'),
        title: @json(session('success') ? 'Berhasil' : 'Gagal'),
        text: @json(session('success') ?? session('error') ?? $errors->first()),
        confirmButtonColor: '#106fae'
    });
});
</script>
@endif
<script>
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches || !form.matches('form[data-confirm]') || form.dataset.confirmed === '1') return;
    event.preventDefault();
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: form.dataset.confirm,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal'
    }).then(function (result) {
        if (result.isConfirmed) {
            form.dataset.confirmed = '1';
            form.submit();
        }
    });
});
</script>
<script>
if (localStorage.getItem('sidebar-collapsed') === '1') document.body.classList.add('sidebar-collapsed');
document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    var collapsed = document.body.classList.toggle('sidebar-collapsed');
    try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
});
</script>
@stack('scripts')
</body>
</html>
