<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} Dashboard | {{ config('app.name', 'Monitoring Proyek') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Monitoring Proyek</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item me-lg-3">
                        <span class="nav-link text-white">{{ $user->name }}</span>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm">Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h3 mb-1">Dashboard {{ $title }}</h1>
                                <p class="text-muted mb-0">Selamat datang, {{ $user->name }}!</p>
                            </div>
                            <span class="badge bg-primary text-uppercase">{{ $user->role?->role_name }}</span>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h2 class="h4">Ringkasan</h2>
                                        <p class="text-muted mb-0">Tambahkan komponen dashboard sesuai kebutuhan peran
                                            {{ strtolower($title) }}.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h2 class="h4">Aktivitas</h2>
                                        <p class="text-muted mb-0">Tampilkan aktivitas terbaru untuk memudahkan monitoring
                                            proyek.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h2 class="h4">Statistik</h2>
                                        <p class="text-muted mb-0">Visualisasikan data penting seperti progres dan risiko proyek.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h2 class="h5">Langkah Selanjutnya</h2>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Sesuaikan menu navigasi dan hak akses sesuai peran.</li>
                                <li class="list-group-item">Integrasikan data proyek untuk menampilkan informasi aktual.</li>
                                <li class="list-group-item">Implementasikan notifikasi dan pelacakan aktivitas tim.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
