<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autentikasi | {{ config('app.name', 'Monitoring Proyek') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        integrity="sha384-+9IeAxzFvtJyX6+JsiuyanNqcyQ8Gcq/V5j1IHpF3N99ElfpZMlA4iAcFE9Z9Xjw"
        crossorigin="anonymous">
</head>

<body class="auth-body">
    @php
        $activeTab = session('auth_tab', 'login');
        $registerErrors = $errors->getBag('register');
        $resetErrors = $errors->getBag('reset');
    @endphp
    <main class="w-100">
        <div class="card auth-card shadow-lg border-0 overflow-hidden mx-auto">
            <div class="row g-0">
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-4 auth-brand-column">
                    <div>
                        <div class="auth-brand-icon d-flex align-items-center justify-content-center mb-4">
                            <span class="fs-4 fw-semibold">MP</span>
                        </div>
                        <h2 class="fw-bold mb-3">Sistem Informasi Monitoring Proyek</h2>
                        <p class="text-white-50 mb-4">
                            Kelola proyek, pantau progres, dan kolaborasi dengan tim lebih efektif melalui satu dashboard
                            terpadu.
                        </p>
                        <ul class="list-unstyled text-white-50 small mb-0">
                            <li class="d-flex align-items-center mb-2">
                                <span class="me-2 bi bi-check-circle-fill"></span>
                                Pelaporan real-time dan transparan
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <span class="me-2 bi bi-check-circle-fill"></span>
                                Integrasi dokumen proyek
                            </li>
                            <li class="d-flex align-items-center">
                                <span class="me-2 bi bi-check-circle-fill"></span>
                                Dashboard dan analitik interaktif
                            </li>
                        </ul>
                    </div>
                    <div class="auth-help small">
                        &copy; {{ now()->year }} {{ config('app.name', 'Monitoring Proyek') }}. Semua hak dilindungi.
                    </div>
                </div>
                <div class="col-lg-7 p-4 p-lg-5 bg-white">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold">Selamat Datang Kembali</h3>
                        <p class="text-muted mb-0">Masuk atau buat akun baru untuk mengelola proyek Anda.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <ul class="nav nav-pills auth-nav justify-content-center gap-2 mb-4" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'login' ? 'active' : '' }}" id="login-tab"
                                data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab"
                                aria-controls="login" aria-selected="{{ $activeTab === 'login' ? 'true' : 'false' }}">
                                Masuk
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'register' ? 'active' : '' }}"
                                id="register-tab" data-bs-toggle="pill" data-bs-target="#register" type="button"
                                role="tab" aria-controls="register"
                                aria-selected="{{ $activeTab === 'register' ? 'true' : 'false' }}">
                                Daftar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'reset' ? 'active' : '' }}" id="reset-tab"
                                data-bs-toggle="pill" data-bs-target="#reset" type="button" role="tab"
                                aria-controls="reset" aria-selected="{{ $activeTab === 'reset' ? 'true' : 'false' }}">
                                Lupa Password
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="authTabContent">
                        <div class="tab-pane fade {{ $activeTab === 'login' ? 'show active' : '' }}" id="login"
                            role="tabpanel" aria-labelledby="login-tab">
                            <form action="{{ route('login.perform') }}" method="POST" class="needs-validation" novalidate>
                                @csrf
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="loginEmail" name="email" placeholder="nama@perusahaan.com" required
                                        value="{{ $activeTab === 'login' ? old('email') : '' }}">
                                    <div class="invalid-feedback">
                                        @error('email')
                                            {{ $message }}
                                        @else
                                            Email wajib diisi.
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="loginPassword" name="password"
                                            placeholder="Masukkan password" required>
                                        <button class="btn btn-outline-secondary" type="button"
                                            data-toggle-password="loginPassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">Password wajib diisi.</div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="rememberMe"
                                            name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="rememberMe">Ingat saya</label>
                                    </div>
                                    <button type="button" class="btn btn-link px-0" data-bs-toggle="pill"
                                        data-bs-target="#reset">
                                        Butuh bantuan masuk?
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Masuk</button>
                            </form>
                            <div class="text-center text-muted mt-4">
                                <small>
                                    Belum punya akun?
                                    <button class="btn btn-link btn-sm px-1" type="button" data-bs-toggle="pill"
                                        data-bs-target="#register">Daftar sekarang</button>
                                </small>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'register' ? 'show active' : '' }}" id="register"
                            role="tabpanel" aria-labelledby="register-tab">
                            <form action="{{ route('register.perform') }}" method="POST" class="needs-validation" novalidate>
                                @csrf
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="registerName" class="form-label">Nama Lengkap</label>
                                        <input type="text"
                                            class="form-control {{ $registerErrors->has('name') ? 'is-invalid' : '' }}"
                                            id="registerName" name="name" placeholder="Nama lengkap" required
                                            value="{{ $activeTab === 'register' ? old('name') : '' }}">
                                        <div class="invalid-feedback">
                                            {{ $registerErrors->first('name') ?? 'Nama lengkap wajib diisi.' }}
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="registerPhone" class="form-label">Nomor Telepon</label>
                                        <input type="tel" class="form-control"
                                            id="registerPhone" name="phone" placeholder="08xxxxxxxxxx"
                                            value="{{ $activeTab === 'register' ? old('phone') : '' }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="registerEmail" class="form-label">Email</label>
                                        <input type="email"
                                            class="form-control {{ $registerErrors->has('email') ? 'is-invalid' : '' }}"
                                            id="registerEmail" name="email" placeholder="nama@perusahaan.com" required
                                            value="{{ $activeTab === 'register' ? old('email') : '' }}">
                                        <div class="invalid-feedback">
                                            {{ $registerErrors->first('email') ?? 'Email aktif wajib diisi.' }}
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="registerPassword" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password"
                                                class="form-control {{ $registerErrors->has('password') ? 'is-invalid' : '' }}"
                                                id="registerPassword" name="password"
                                                placeholder="Minimal 8 karakter" required>
                                            <button class="btn btn-outline-secondary" type="button"
                                                data-toggle-password="registerPassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">
                                                {{ $registerErrors->first('password') ?? 'Password wajib diisi.' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="registerPasswordConfirmation" class="form-label">Konfirmasi
                                            Password</label>
                                        <input type="password" class="form-control" id="registerPasswordConfirmation"
                                            name="password_confirmation" placeholder="Ulangi password" required>
                                        <div class="invalid-feedback">Konfirmasi password wajib diisi.</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="registerRole" class="form-label">Peran dalam Proyek</label>
                                        <select id="registerRole" class="form-select {{ $registerErrors->has('role') ? 'is-invalid' : '' }}"
                                            name="role" required>
                                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih peran</option>
                                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                                            <option value="operator" {{ old('role') === 'operator' ? 'selected' : '' }}>Operator</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            {{ $registerErrors->first('role') ?? 'Silakan pilih peran Anda.' }}
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input {{ $registerErrors->has('terms') ? 'is-invalid' : '' }}" type="checkbox" value="1" id="agreeTerms"
                                                name="terms" {{ old('terms') ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="agreeTerms">
                                                Saya setuju dengan syarat & ketentuan yang berlaku.
                                            </label>
                                            <div class="invalid-feedback">
                                                {{ $registerErrors->first('terms') ?? 'Anda harus menyetujui syarat & ketentuan.' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">Daftar Sekarang</button>
                                    </div>
                                </div>
                            </form>
                            <div class="text-center text-muted mt-4">
                                <small>
                                    Sudah punya akun?
                                    <button class="btn btn-link btn-sm px-1" type="button" data-bs-toggle="pill"
                                        data-bs-target="#login">Masuk di sini</button>
                                </small>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'reset' ? 'show active' : '' }}" id="reset"
                            role="tabpanel" aria-labelledby="reset-tab">
                            <form action="{{ route('password.email') }}" method="POST" class="needs-validation" novalidate>
                                @csrf
                                <div class="mb-3">
                                    <label for="resetEmail" class="form-label">Email Terdaftar</label>
                                    <input type="email"
                                        class="form-control {{ $resetErrors->has('email') ? 'is-invalid' : '' }}"
                                        id="resetEmail" name="email" placeholder="nama@perusahaan.com" required
                                        value="{{ $activeTab === 'reset' ? old('email') : '' }}">
                                    <div class="invalid-feedback">
                                        {{ $resetErrors->first('email') ?? 'Masukkan email terdaftar.' }}
                                    </div>
                                </div>
                                <p class="text-muted small">
                                    Kami akan mengirimkan tautan untuk mengatur ulang password ke email Anda apabila terdaftar
                                    di sistem.
                                </p>
                                <button type="submit" class="btn btn-primary w-100">Kirim Tautan Reset Password</button>
                            </form>
                            <div class="text-center text-muted mt-4">
                                <small>
                                    Sudah ingat password?
                                    <button class="btn btn-link btn-sm px-1" type="button" data-bs-toggle="pill"
                                        data-bs-target="#login">Kembali ke Masuk</button>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (() => {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            const toggleButtons = document.querySelectorAll('[data-toggle-password]');
            toggleButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-toggle-password');
                    const target = document.getElementById(targetId);
                    if (!target) {
                        return;
                    }
                    const currentType = target.getAttribute('type');
                    target.setAttribute('type', currentType === 'password' ? 'text' : 'password');
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('bi-eye');
                        icon.classList.toggle('bi-eye-slash');
                    }
                });
            });

            const activeTab = @json($activeTab);
            const trigger = document.querySelector(`[data-bs-target="#${activeTab}"]`);
            if (trigger) {
                const tab = bootstrap.Tab.getOrCreateInstance(trigger);
                tab.show();
            }
        })();
    </script>
</body>

</html>
