<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Login | BUMDESMA LKD TARUB</title>
	<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<style>
		:root { --blue: #106fae; --blue-dark: #075487; --blue-light: #159bd0; --gold: #f5c34d; --ink: #173247; }
		body { min-height: 100vh; background: #eef5fa; color: var(--ink); }
		.login-wrap { min-height: 100vh; }
		.login-card { border: 0; border-radius: 1.5rem; overflow: hidden; box-shadow: 0 30px 70px rgba(7, 84, 135, .18); }
		.login-brand { background: linear-gradient(155deg, var(--blue-dark) 0%, var(--blue) 55%, var(--blue-light) 100%); position: relative; overflow: hidden; }
		.login-brand::before, .login-brand::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .08); }
		.login-brand::before { width: 280px; height: 280px; top: -90px; right: -90px; }
		.login-brand::after { width: 190px; height: 190px; bottom: -60px; left: -60px; background: rgba(245, 195, 77, .18); }
		.brand-logo { width: 84px; height: 84px; object-fit: contain; background: #fff; border-radius: 50%; padding: 8px; box-shadow: 0 10px 25px rgba(0, 0, 0, .2); }
		.brand-fallback { display: inline-grid; place-items: center; width: 84px; height: 84px; border-radius: 50%; background: #fff; color: var(--blue-dark); font-weight: 800; font-size: 1.5rem; }
		.feature-pill { display: flex; align-items: center; gap: .7rem; background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .18); border-radius: .9rem; padding: .6rem .9rem; font-size: .9rem; }
		.feature-pill i { display: grid; place-items: center; width: 34px; height: 34px; border-radius: .7rem; background: rgba(255, 255, 255, .16); font-size: 1.05rem; flex-shrink: 0; }
		.input-group-text { background: #f2f8fc; border-right: 0; color: var(--blue); }
		.form-control { border-left: 0; }
		.form-control:focus { box-shadow: 0 0 0 .25rem rgba(16, 111, 174, .15); border-color: var(--blue); }
		.input-group:focus-within .input-group-text { border-color: var(--blue); }
		.btn-login { background: linear-gradient(135deg, var(--blue-dark), var(--blue-light)); border: 0; font-weight: 600; letter-spacing: .02em; box-shadow: 0 12px 25px rgba(16, 111, 174, .35); transition: transform .15s ease, box-shadow .15s ease; }
		.btn-login:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(16, 111, 174, .4); }
		.divider { display: flex; align-items: center; gap: .75rem; color: #8aa3b5; font-size: .8rem; }
		.divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: #e2ecf3; }
		.back-home { position: fixed; top: 1.25rem; left: 1.25rem; z-index: 10; display: inline-flex; align-items: center; gap: .55rem; padding: .6rem 1.1rem; border-radius: 999px; background: rgba(255, 255, 255, .75); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(16, 111, 174, .18); color: var(--blue-dark); font-weight: 600; font-size: .9rem; text-decoration: none; box-shadow: 0 10px 25px rgba(7, 84, 135, .15); transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
		.back-home:hover { transform: translateX(-3px); background: #fff; box-shadow: 0 14px 30px rgba(7, 84, 135, .22); color: var(--blue-dark); }
		.back-home .back-ic { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, var(--blue-dark), var(--blue-light)); color: #fff; font-size: .85rem; }
		@media (max-width: 576px) { .back-home span.txt { display: none; } .back-home { padding: .6rem; } }
	</style>
</head>
<body>
	<a class="back-home" href="{{ route('home') }}" title="Kembali ke halaman utama website">
	    <span class="back-ic">
	        <i class="bi bi-arrow-left">
	        </i>
	    </span>
	    <span class="txt">Kembali ke Beranda</span>
	</a>
	<main class="container d-flex align-items-center justify-content-center login-wrap py-4 py-md-5">
		<div class="login-card bg-white w-100" style="max-width: 920px;">
			<div class="row g-0">
				<div class="col-lg-5 login-brand text-white p-4 p-md-5 d-flex flex-column">
					<div class="position-relative" style="z-index: 1;">
						<div class="text-center text-lg-start">
							@if (file_exists(public_path('images/logo-bumdesma.png')))
								<img src="{{ asset('images/logo-bumdesma.png') }}" alt="Logo BUMDESMA LKD TARUB" class="brand-logo">
							@else
								<span class="brand-fallback">BT</span>
							@endif
							<h1 class="h4 fw-bold mt-3 mb-1">BUMDESMA LKD TARUB</h1>
							<p class="mb-1 fw-semibold" style="color: var(--gold);">Sistem Informasi Kehadiran</p>
							<p class="small mb-4 opacity-75">Kelola kehadiran, cuti, penggajian, dan apresiasi dalam satu pintu.</p>
						</div>
						<div class="d-grid gap-2">
							<div class="feature-pill">
							    <i class="bi bi-calendar-check">
							    </i>
							    <span>Absensi masuk &amp; pulang tercatat otomatis</span>
							</div>
							<div class="feature-pill">
							    <i class="bi bi-calendar2-heart">
							    </i>
							    <span>Pengajuan cuti &amp; izin lebih mudah</span>
							</div>
							<div class="feature-pill">
							    <i class="bi bi-wallet2">
							    </i>
							    <span>Slip gaji &amp; reward transparan</span>
							</div>
						</div>
						<p class="small mt-4 mb-0 opacity-50 d-none d-lg-block">
						    <i class="bi bi-shield-lock me-1">
						    </i>Data Anda terlindungi &amp; hanya untuk internal.</p>
					</div>
				</div>
				<div class="col-lg-7 p-4 p-md-5">
					<div class="mx-auto" style="max-width: 400px;">
						<h2 class="h4 fw-bold mb-1">Selamat datang kembali</h2>
						<p class="text-secondary small mb-4">Masuk untuk melanjutkan ke dashboard Anda.</p>
						<form method="POST" action="{{ route('login.store') }}">
							@csrf
							<div class="mb-3">
								<label class="form-label fw-semibold small" for="email">Email</label>
								<div class="input-group input-group-lg">
									<span class="input-group-text">
									    <i class="bi bi-envelope">
									    </i>
									</span>
									<input
									    class="form-control"
									    id="email"
									    name="email"
									    type="email"
									    placeholder="nama@bumdesma.test"
									    value="{{ old('email') }}"
									    required
									    autofocus>
								</div>
							</div>
							<div class="mb-2">
								<label class="form-label fw-semibold small" for="password">Password</label>
								<div class="input-group input-group-lg">
									<span class="input-group-text">
									    <i class="bi bi-key">
									    </i>
									</span>
									<input class="form-control" id="password" name="password" type="password" placeholder="••••••••" required>
									<button
									    class="btn btn-outline-secondary"
									    type="button"
									    id="togglePassword"
									    title="Tampilkan/sembunyikan password">
									<i class="bi bi-eye">
									</i>
									</button>
								</div>
							</div>
							<div class="d-flex justify-content-between align-items-center mb-4">
								<div class="form-check">
								    <input class="form-check-input" id="remember" name="remember" type="checkbox">
								    <label class="form-check-label small" for="remember">Ingat saya</label>
								</div>
								<a class="small text-decoration-none" href="{{ route('password.request') }}">Lupa password?</a>
							</div>
							@if($errors->any())
								<div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
								    <i class="bi bi-exclamation-triangle-fill">
								    </i>
								    <span>
								        {{ $errors->first() }}
								    </span>
								</div>
							@endif
							<button class="btn btn-primary btn-lg w-100 btn-login" type="submit">
							    <i class="bi bi-box-arrow-in-right me-2">
							    </i>Masuk ke sistem</button>
						</form>
						<div class="divider my-4">akses internal karyawan</div>
						<p class="text-center text-secondary small mb-0">Butuh bantuan akun? Hubungi administrator BUMDESMA.</p>
					</div>
				</div>
			</div>
		</div>
	</main>
	<script>
		document.getElementById('togglePassword')?.addEventListener('click', function () {
			const input = document.getElementById('password');
			const icon = this.querySelector('i');
			const show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
		});
	</script>
</body>
</html>
