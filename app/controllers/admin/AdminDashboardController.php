<?php
namespace Controllers\Admin;

use Core\Controller;

/**
 * Landing page for administrators.
 * Real metrics arrive with the module dashboards in weeks 3–5.
 */
class AdminDashboardController extends Controller
{
    public function index(): void
    {
        $this->view('home.dashboard', ['title' => 'Admin Dashboard']);
    }
}
