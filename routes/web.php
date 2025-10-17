<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\MaterialController;
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

Route::middleware('auth')->resource('materials', MaterialController::class);
