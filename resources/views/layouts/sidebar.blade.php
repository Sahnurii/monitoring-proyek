@php
    $role = optional($user->role)->role_name;
    $iconMap = [
        'layout-dashboard' => 'speedometer2',
        'folder' => 'folder',
        'database' => 'database',
        'box' => 'box',
        'file-text' => 'file-earmark-text',
        'users' => 'people',
    ];

    $menuConfig = [
        'admin' => [
            [
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'route' => 'dashboard',
            ],
            [
                'label' => 'Transaksi',
                'icon' => 'folder',
                'children' => [
                    ['label' => 'Material Request (MR)', 'route' => 'procurement.material-requests.index'],
                    ['label' => 'Purchase Order (PO)', 'route' => 'procurement.purchase-orders.index'],
                    ['label' => 'Goods Receipt (GR)', 'route' => 'procurement.goods-receipts.index'],
                    ['label' => 'Goods Issue (GI)', 'route' => 'procurement.goods-issues.index'],
                ],
            ],
            [
                'label' => 'Master Data',
                'icon' => 'database',
                'children' => [
                    ['label' => 'Material', 'route' => 'materials.index'],
                    ['label' => 'Satuan', 'route' => 'units.index'],
                    ['label' => 'Supplier', 'route' => 'suppliers.index'],
                    ['label' => 'Proyek', 'route' => 'projects.index'],
                ],
            ],
            [
                'label' => 'Persediaan',
                'icon' => 'box',
                'children' => [
                    ['label' => 'Stok Material', 'route' => 'supply.material-stock.index'],
                    ['label' => 'Mutasi Stok', 'route' => 'stock.transfer'],
                ],
            ],
            [
                'label' => 'Laporan',
                'icon' => 'file-text',
                'children' => [
                    ['label' => 'Data Proyek', 'route' => 'reports.project'],
                    ['label' => 'Pengadaan (PO)', 'route' => 'reports.procurement'],
                    ['label' => 'Stok & Kartu Stok', 'route' => 'reports.inventory'],
                    ['label' => 'Pengeluaran ke Proyek', 'route' => 'reports.project'],
                    ['label' => 'Data Supplier', 'route' => 'reports.supplier'],
                ],
            ],
            [
                'label' => 'Pengguna & Role',
                'icon' => 'users',
                'children' => [
                    ['label' => 'Manajemen Pengguna', 'route' => 'users.index'],
                    ['label' => 'Manajemen Role', 'route' => 'roles.index'],
                ],
            ],
        ],
        'manager' => [
            [
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'route' => 'dashboard',
            ],
            [
                'label' => 'Transaksi',
                'icon' => 'folder',
                'children' => [
                    ['label' => 'Material Request (MR)', 'route' => 'procurement.material-requests.index'],
                    ['label' => 'Purchase Order (PO)', 'route' => 'procurement.purchase-orders.index'],
                    ['label' => 'Goods Receipt (GR)', 'route' => 'gr.index'],
                    ['label' => 'Goods Issue (GI)', 'route' => 'gi.index'],
                ],
            ],
            [
                'label' => 'Persediaan',
                'icon' => 'box',
                'children' => [['label' => 'Stok Material', 'route' => 'supply.material-stock.index']],
            ],
            [
                'label' => 'Laporan',
                'icon' => 'file-text',
                'children' => [
                    ['label' => 'Laporan Pengadaan', 'route' => 'reports.procurement'],
                    ['label' => 'Laporan Proyek', 'route' => 'reports.project'],
                ],
            ],
        ],
        'operator' => [
            [
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'route' => 'dashboard',
            ],
            [
                'label' => 'Transaksi',
                'icon' => 'folder',
                'children' => [
                    ['label' => 'Material Request (MR)', 'route' => 'mr.index'],
                    ['label' => 'Purchase Order (PO)', 'route' => 'po.index'],
                    ['label' => 'Goods Receipt (GR)', 'route' => 'gr.index'],
                    ['label' => 'Goods Issue (GI)', 'route' => 'gi.index'],
                ],
            ],
            [
                'label' => 'Persediaan',
                'icon' => 'box',
                'children' => [['label' => 'Stok Material', 'route' => 'reports.inventory']],
            ],
            [
                'label' => 'Laporan',
                'icon' => 'file-text',
                'children' => [
                    ['label' => 'Laporan Pengadaan', 'route' => 'reports.procurement'],
                    ['label' => 'Laporan Stok', 'route' => 'reports.inventory'],
                ],
            ],
        ],
    ];

    $menuItems = $menuConfig[$role] ?? $menuConfig['operator'];

    $getRoutePatterns = function ($routeName) {
        $patterns = [];

        if ($routeName) {
            $patterns[] = $routeName;
            $patterns[] = $routeName . '.*';

            if (\Illuminate\Support\Str::endsWith($routeName, '.index')) {
                $patterns[] = \Illuminate\Support\Str::beforeLast($routeName, '.') . '.*';
            }
        }

        return array_unique($patterns);
    };
@endphp

<aside class="dashboard-sidebar" data-sidebar>
    <div class="sidebar-header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="sidebar-logo">
        <span class="sidebar-title">Sistem Informasi Monitoring Proyek dan Pengadaan Material</span>
        <br>
        <small style="font-size: 0.7em; color: #888;">CV. Agha Jaya Sakti</small>
        <button class="btn btn-close btn-sm d-lg-none" type="button" data-sidebar-toggle></button>
    </div>
    <nav class="sidebar-nav">
        @foreach ($menuItems as $index => $item)
            @php
                $hasChildren = !empty($item['children']);
                $routeName = $item['route'] ?? null;
                $resolvedIcon = $iconMap[$item['icon']] ?? 'dot';
                $childRoutePatterns = [];
                if ($hasChildren) {
                    foreach ($item['children'] as $childItem) {
                        if (!empty($childItem['route'])) {
                            $childRoutePatterns = array_merge(
                                $childRoutePatterns,
                                $getRoutePatterns($childItem['route']),
                            );
                        }
                    }
                    $childRoutePatterns = array_values(array_unique($childRoutePatterns));
                }

                $isActive = false;
                foreach ($getRoutePatterns($routeName) as $pattern) {
                    if (request()->routeIs($pattern)) {
                        $isActive = true;
                        break;
                    }
                }

                if (!$isActive && $hasChildren) {
                    foreach ($childRoutePatterns as $pattern) {
                        if ($pattern && request()->routeIs($pattern)) {
                            $isActive = true;
                            break;
                        }
                    }
                }

                $collapseId = $hasChildren ? 'sidebarGroup' . $loop->index : null;
                $href = '#';

                if ($routeName && \Illuminate\Support\Facades\Route::has($routeName)) {
                    $href = route($routeName);
                }
            @endphp

            <div
                class="sidebar-item {{ $hasChildren ? 'has-children' : '' }} {{ $isActive ? 'is-active is-open' : '' }}">
                @if ($hasChildren)
                    <button class="sidebar-link" type="button" data-sidebar-accordion
                        data-sidebar-target="#{{ $collapseId }}" aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                        <span class="sidebar-link-icon"><i class="bi bi-{{ $resolvedIcon }}"></i></span>
                        <span class="sidebar-link-text">{{ $item['label'] }}</span>
                        <span class="sidebar-link-caret"><i class="bi bi-chevron-down"></i></span>
                    </button>
                    <div class="sidebar-collapse {{ $isActive ? 'show' : '' }}" id="{{ $collapseId }}">
                        <nav class="sidebar-subnav">
                            @foreach ($item['children'] as $child)
                                @php
                                    $childRoute = $child['route'] ?? null;
                                    $childActive = false;
                                    foreach ($getRoutePatterns($childRoute) as $pattern) {
                                        if ($pattern && request()->routeIs($pattern)) {
                                            $childActive = true;
                                            break;
                                        }
                                    }
                                    $childHref = '#';
                                    if ($childRoute && \Illuminate\Support\Facades\Route::has($childRoute)) {
                                        $childHref = route($childRoute);
                                    }
                                @endphp
                                <a class="sidebar-sublink {{ $childActive ? 'active' : '' }}"
                                    href="{{ $childHref }}">
                                    <span class="sidebar-dot"></span>
                                    <span>{{ $child['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                @else
                    <a class="sidebar-link {{ $isActive ? 'active' : '' }}" href="{{ $href }}">
                        <span class="sidebar-link-icon"><i class="bi bi-{{ $resolvedIcon }}"></i></span>
                        <span class="sidebar-link-text">{{ $item['label'] }}</span>
                    </a>
                @endif
            </div>
        @endforeach
    </nav>
</aside>
