<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\MaterialController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\Procurement\GoodsReceiptController;
use App\Http\Controllers\Procurement\MaterialRequestController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Project\ProjectController;
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

Route::middleware('auth')->group(function () {
    Route::resource('materials', MaterialController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('units', UnitController::class);
    Route::resource('projects', ProjectController::class);
    Route::resource('procurement/material-requests', MaterialRequestController::class)
        ->names('procurement.material-requests');
    Route::resource('procurement/purchase-orders', PurchaseOrderController::class)
        ->names('procurement.purchase-orders');
    Route::resource('procurement/goods-receipts', GoodsReceiptController::class)
        ->names('procurement.goods-receipts');
});
