@php
    $currentRouteName = \Illuminate\Support\Facades\Route::currentRouteName();
    $fallbackTitle = $currentRouteName ? \Illuminate\Support\Str::headline($currentRouteName) : null;

    if ($fallbackTitle) {
        $fallbackTitle = \Illuminate\Support\Str::replaceLast(' Index', '', $fallbackTitle);
    }

    $pageTitle = $title ?? ($fallbackTitle ?? 'Dashboard');

    $breadcrumbs = [
        [
            'label' => 'Dashboard',
            'url' => route('dashboard'),
            'active' => !$currentRouteName || $currentRouteName === 'dashboard',
        ],
    ];

    if ($currentRouteName && $currentRouteName !== 'dashboard') {
        $segments = explode('.', $currentRouteName);
        $resourceSegment = array_shift($segments);

        if ($resourceSegment && $resourceSegment !== 'dashboard') {
            $resourceRouteName = $resourceSegment . '.index';
            $hasResourceRoute = \Illuminate\Support\Facades\Route::has($resourceRouteName);

            $resourceLabelSource = $resourceSegment;

            if ($hasResourceRoute) {
                $resourceLabelSource = \Illuminate\Support\Str::singular($resourceLabelSource);
            }

            $resourceLabel = \Illuminate\Support\Str::of($resourceLabelSource)
                ->replace(['_', '-'], ' ')
                ->title()
                ->value();
            $resourceUrl = $hasResourceRoute ? route($resourceRouteName) : null;
            $resourceActive = empty($segments) || (count($segments) === 1 && $segments[0] === 'index');

            $breadcrumbs[] = [
                'label' => $resourceLabel,
                'url' => $resourceUrl,
                'active' => $resourceActive,
            ];
        }

        $actionMap = [
            'index' => 'Daftar',
            'create' => 'Tambah',
            'store' => 'Tambah',
            'show' => 'Detail',
            'edit' => 'Edit',
            'update' => 'Perbarui',
            'destroy' => 'Hapus',
        ];

        $actionSegments = [];

        foreach ($segments as $segment) {
            $normalized = \Illuminate\Support\Str::of($segment)->replace('_', '-')->lower()->value();

            if ($normalized === 'index' || $normalized === '') {
                continue;
            }

            $actionSegments[] = $normalized;
        }

        $lastIndex = count($actionSegments) - 1;

        foreach ($actionSegments as $index => $normalized) {
            $label =
                $actionMap[$normalized] ??
                \Illuminate\Support\Str::of($normalized)->replace('-', ' ')->title()->value();

            $breadcrumbs[] = [
                'label' => $label,
                'url' => null,
                'active' => $index === $lastIndex,
            ];
        }
    }
@endphp

<header class="dashboard-header navbar navbar-expand-lg bg-white border-bottom shadow-sm py-0">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary d-lg-none" type="button" data-sidebar-toggle>
                <i class="bi bi-list"></i>
            </button>
            <div class="d-flex flex-column">
                <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">
                    {{ config('Sistem Informasi Manajemen Proyek dan Pengadaan Material pada CV. Agha Jaya Sakti') }}
                </a>
                @if (!empty($breadcrumbs))
                    <div class="text-muted small">
                        <nav class="d-inline" aria-label="Breadcrumb">
                            <ol class="breadcrumb mb-0 d-inline-flex align-items-center"
                                style="--bs-breadcrumb-divider: '/';">
                                @foreach ($breadcrumbs as $crumb)
                                    <li class="breadcrumb-item {{ $crumb['active'] ? 'active' : '' }}"
                                        @if ($crumb['active']) aria-current="page" @endif>
                                        @if (!$crumb['active'] && !empty($crumb['url']))
                                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                                        @else
                                            {{ $crumb['label'] }}
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    </div>
                @endif
            </div>
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
