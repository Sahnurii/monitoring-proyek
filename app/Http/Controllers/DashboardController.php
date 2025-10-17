<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function admin(): View
    {
        return $this->render('Admin');
    }

    public function manager(): View
    {
        return $this->render('Manager');
    }

    public function operator(): View
    {
        return $this->render('Operator');
    }

    protected function render(string $title): View
    {
        return view('dashboard.index', [
            'title' => $title,
            'user' => Auth::user(),
        ]);
    }
}
