<header class="dashboard-header navbar navbar-expand-lg bg-white border-bottom shadow-sm py-0">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary d-lg-none" type="button" data-sidebar-toggle>
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">
                {{ config('Sistem Informasi Manajemen Proyek dan Pengadaan Material pada CV. Agha Jaya Sakti') }}
            </a>
        </div>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="text-end d-none d-sm-block">
                <div class="fw-semibold">{{ $user->name }}</div>
                <div class="text-muted small text-uppercase">{{ $user->role?->role_name ?? 'User' }}</div>
            </div>
            <div class="d-sm-none text-end">
                <div class="fw-semibold text-nowrap">{{ \Illuminate\Support\Str::limit($user->name, 18) }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="ms-1 d-none d-sm-inline">Keluar</span>
                </button>
            </form>
        </div>
    </div>
</header>
