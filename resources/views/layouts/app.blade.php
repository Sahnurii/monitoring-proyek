<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        {{ trim(($pageTitle = $title ?? '') !== '' ? $pageTitle . ' | ' : '') }}
        {{ config('app.name', 'Sistem Informasi Manajemen Proyek dan Pengadaan Material pada CV. Agha Jaya Sakti') }}
    </title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>

@php
    $role = optional($user->role)->role_name ?? 'operator';
@endphp

<body class="dashboard-body" data-role="{{ $role }}">
    <div class="dashboard-layout">
        @include('layouts.sidebar', ['user' => $user])

        <div class="dashboard-content d-flex flex-column">
            @include('layouts.header', [
                'user' => $user,
                'title' => $title ?? null,
            ])

            <main class="dashboard-main container-fluid py-4">
                @yield('content')
            </main>

            @include('layouts.footer')
        </div>
    </div>

    <div class="dashboard-sidebar-backdrop" data-sidebar-backdrop></div>

    @stack('scripts')
</body>

</html>
