<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route($this->dashboardRouteFor(Auth::user()));
        }

        return view('auth.index');
    }

    public function login(Request $request): RedirectResponse
    {
        $validator = validator($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email', 'remember'))
                ->with('auth_tab', 'login');
        }

        $remember = $request->boolean('remember');

        if (! Auth::attempt($validator->validated(), $remember)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak sesuai.'])
                ->withInput($request->only('email', 'remember'))
                ->with('auth_tab', 'login');
        }

        $request->session()->regenerate();

        return redirect()->route($this->dashboardRouteFor(Auth::user()));
    }

    public function register(Request $request): RedirectResponse
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'exists:roles,role_name'],
            'terms' => ['accepted'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal terdiri dari :min karakter.',
            'role.required' => 'Silakan pilih peran.',
            'role.exists' => 'Peran tidak ditemukan.',
            'terms.accepted' => 'Anda harus menyetujui syarat & ketentuan.',
        ], [
            'role' => 'peran',
            'terms' => 'syarat & ketentuan',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'register')
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('auth_tab', 'register');
        }

        $validated = $validator->validated();

        $roleId = Role::where('role_name', $validated['role'])->value('id');

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $roleId,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route($this->dashboardRouteFor($user));
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validator = validator($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'reset')
                ->withInput($request->only('email'))
                ->with('auth_tab', 'reset');
        }

        $status = Password::sendResetLink(
            $validator->validated()
        );

        return back()
            ->with('auth_tab', 'reset')
            ->with(
                $status === Password::RESET_LINK_SENT ? 'status' : 'error',
                $status === Password::RESET_LINK_SENT
                    ? 'Tautan reset password telah dikirim bila email terdaftar.'
                    : 'Email tidak ditemukan atau terjadi kesalahan. Coba lagi beberapa saat.'
            );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function dashboardRouteFor(?User $user): string
    {
        $role = optional($user->role)->role_name;

        return match ($role) {
            'admin' => 'dashboard.admin',
            'manager' => 'dashboard.manager',
            'operator' => 'dashboard.operator',
            default => 'dashboard.operator',
        };
    }
}
