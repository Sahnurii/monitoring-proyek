<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} Dashboard | {{ config('app.name', 'Sistem Informasi Manajemen Proyek dan Pengadaan Material pada CV. Agha Jaya Sakti') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD"
        crossorigin="anonymous">
</head>

@php
    $role = optional($user->role)->role_name ?? 'operator';
@endphp

<body class="dashboard-body" data-role="{{ $role }}">
    <div class="dashboard-layout">
        @include('dashboard.partials.sidebar', ['user' => $user])

        <div class="dashboard-content d-flex flex-column">
            @include('dashboard.partials.header', ['user' => $user])

            <main class="dashboard-main container-fluid py-4">
                @includeWhen($role === 'admin', 'dashboard.partials.admin-overview', ['user' => $user])
                @includeWhen($role === 'manager', 'dashboard.partials.manager-overview', ['user' => $user])
                @includeWhen($role === 'operator', 'dashboard.partials.operator-overview', ['user' => $user])
                @unless (in_array($role, ['admin', 'manager', 'operator'], true))
                    @include('dashboard.partials.operator-overview', ['user' => $user])
                @endunless
            </main>

            @include('dashboard.partials.footer')
        </div>
    </div>

    <div class="dashboard-sidebar-backdrop" data-sidebar-backdrop></div>
</body>

</html>
