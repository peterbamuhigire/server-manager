<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/SystemMetric.php';
require_once __DIR__ . '/../Models/ActivityLog.php';
require_once __DIR__ . '/../Services/ServerMonitoringService.php';

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

        // Get real-time server metrics
        $metrics = ServerMonitoringService::getAllMetrics();

        // Get disk information
        $diskInfo = ServerMonitoringService::getDiskInfo();

        // Get system uptime
        $uptime = ServerMonitoringService::getUptime();

        // Get load average (Linux only)
        $loadAverage = ServerMonitoringService::getLoadAverage();

        // Get recent activity logs
        $recentActivities = ActivityLog::getRecent(10);

        // Get stats (placeholder for future phases)
        $stats = [
            'apps' => 0,           // Phase 4: App deployment
            'domains' => 0,        // Phase 5: Domain management
            'ssl_active' => 0,     // Phase 6: SSL management
            'ssl_expiring' => 0,   // Phase 6: SSL expiring soon
            'databases' => 0       // Phase 7: Database management
        ];

        $this->view('dashboard.index', [
            'user' => $user,
            'metrics' => $metrics,
            'diskInfo' => $diskInfo,
            'uptime' => $uptime,
            'loadAverage' => $loadAverage,
            'recentActivities' => $recentActivities,
            'stats' => $stats
        ]);
    }
}
