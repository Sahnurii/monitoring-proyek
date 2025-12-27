<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\MaterialController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\Procurement\GoodsIssueController;
use App\Http\Controllers\Procurement\GoodsReceiptController;
use App\Http\Controllers\Procurement\MaterialRequestApprovalController;
use App\Http\Controllers\Procurement\MaterialRequestController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Report\ProjectReportController;
use App\Http\Controllers\Supply\MaterialStockController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/register', [AuthController::class, 'register'])->name('register.perform');
Route::post('/password/email', [AuthController::class, 'sendResetLink'])->name('password.email');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', [DashboardController::class, 'admin'])
    ->name('dashboard.admin');

Route::middleware(['auth', 'role:manager'])->get('/manager/dashboard', [DashboardController::class, 'manager'])
    ->name('dashboard.manager');

Route::middleware(['auth', 'role:operator'])->get('/operator/dashboard', [DashboardController::class, 'operator'])
    ->name('dashboard.operator');

Route::middleware('auth')->get('/dashboard', function () {
    $user = Auth::user();
    $role = optional($user->role)->role_name;

    return redirect()->route(match ($role) {
        'admin' => 'dashboard.admin',
        'manager' => 'dashboard.manager',
        'operator' => 'dashboard.operator',
        default => 'dashboard.operator',
    });
})->name('dashboard');


Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('materials', MaterialController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('units', UnitController::class);
    Route::resource('projects', ProjectController::class);

    Route::resource('procurement/purchase-orders', PurchaseOrderController::class)
        ->names('procurement.purchase-orders');

    Route::patch(
        'purchase-orders/{purchaseOrder}/mark-ordered',
        [PurchaseOrderController::class, 'markOrdered']
    )->middleware('role:admin,gudang')
        ->name('procurement.purchase-orders.mark-ordered');


    Route::resource('procurement/goods-receipts', GoodsReceiptController::class)
        ->names('procurement.goods-receipts');

    Route::resource('procurement/goods-issues', GoodsIssueController::class)
        ->only(['edit', 'update', 'destroy'])
        ->names('procurement.goods-issues');
});

Route::middleware(['auth'])->group(function () {

    Route::middleware('role:operator')->group(function () {
        Route::get(
            'procurement/material-requests/create',
            [MaterialRequestController::class, 'create']
        )->name('procurement.material-requests.create');

        Route::post(
            'procurement/material-requests',
            [MaterialRequestController::class, 'store']
        )->name('procurement.material-requests.store');
    });

    Route::middleware('role:admin,manager,operator')->group(function () {
        Route::get(
            'procurement/material-requests',
            [MaterialRequestController::class, 'index']
        )->name('procurement.material-requests.index');

        Route::get(
            'procurement/material-requests/{material_request}',
            [MaterialRequestController::class, 'show']
        )->name('procurement.material-requests.show');
    });

    Route::middleware('role:admin,manager')->group(function () {

        Route::patch(
            'procurement/material-requests/{material_request}/approve',
            [MaterialRequestApprovalController::class, 'approve']
        )->name('procurement.material-requests.approve');

        Route::patch(
            'procurement/material-requests/{material_request}/reject',
            [MaterialRequestController::class, 'reject']
        )->name('procurement.material-requests.reject');

        Route::resource('supply/material-stock', MaterialStockController::class)
            ->only(['index'])
            ->names('supply.material-stock');
            
        Route::get('reports/projects', [ProjectReportController::class, 'index'])
            ->name('reports.project');

        Route::get('reports/projects/export/pdf', [ProjectReportController::class, 'exportPdf'])
            ->name('reports.project.pdf');
    });
});

Route::middleware(['auth', 'role:admin,operator'])->group(function () {
    Route::resource('procurement/goods-issues', GoodsIssueController::class)
        ->only(['index', 'show', 'create', 'store'])
        ->names('procurement.goods-issues');
});



// Route::middleware('auth')->group(function () {
//     Route::middleware('role:admin,manager')->get('reports/projects', [ProjectReportController::class, 'index'])
//         ->name('reports.project');
//     Route::middleware('role:admin,manager')->get('reports/projects/export/pdf', [ProjectReportController::class, 'exportPdf'])
//         ->name('reports.project.pdf');

//     Route::resource('materials', MaterialController::class);
//     Route::resource('suppliers', SupplierController::class);
//     Route::resource('units', UnitController::class);
//     Route::resource('projects', ProjectController::class);
//     Route::resource('procurement/material-requests', MaterialRequestController::class)
//         ->names('procurement.material-requests');
//     Route::resource('procurement/purchase-orders', PurchaseOrderController::class)
//         ->names('procurement.purchase-orders');
//     Route::resource('procurement/goods-receipts', GoodsReceiptController::class)
//         ->names('procurement.goods-receipts');
//     Route::resource('procurement/goods-issues', GoodsIssueController::class)
//         ->names('procurement.goods-issues');
//     route::resource('supply/material-stock', MaterialStockController::class)
//         ->names('supply.material-stock');


//     // Route::get('supply/material-stock', [MaterialStockController::class, 'index'])
//     //     ->middleware('role:operator')
//     //     ->name('supply.material-stock.index');
// });
