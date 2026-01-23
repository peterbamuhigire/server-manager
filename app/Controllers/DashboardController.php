<?php

require_once __DIR__ . '/Controller.php';

/**
 * Dashboard Controller
 * Chwezi Server Manager
 */

class DashboardController extends Controller
{
    /**
     * Show dashboard
     */
    public function index(): void
    {
        $this->requireAuth();

        $user = $this->user();

        $this->view('dashboard.index', [
            'user' => $user
        ]);
    }
}
