<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $authUser = Auth::user();
        $this->view('dashboard.index', compact('authUser'));
    }
}
