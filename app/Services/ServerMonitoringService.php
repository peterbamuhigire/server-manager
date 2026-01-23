<?php

/**
 * ServerMonitoring Service
 *
 * Collects and monitors server performance metrics.
 * Provides methods to get CPU, RAM, Disk usage, and service status.
 *
 * @package ChweziSM
 * @subpackage Services
 * @version 1.0
 */

class ServerMonitoringService
{
    /**
     * Get CPU usage percentage
     *
     * @return float CPU usage percentage (0-100)
     */
    public static function getCpuUsage()
    {
        // Check if running on Windows or Linux
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return self::getCpuUsageWindows();
        } else {
            return self::getCpuUsageLinux();
        }
    }

    /**
     * Get CPU usage on Linux
     *
     * @return float CPU usage percentage
     */
    private static function getCpuUsageLinux()
    {
        try {
            // Read /proc/stat to get CPU stats
            $stat1 = @file_get_contents('/proc/stat');
            if (!$stat1) {
                return 0;
            }

            usleep(100000); // Sleep 100ms

            $stat2 = @file_get_contents('/proc/stat');
            if (!$stat2) {
                return 0;
            }

            // Parse CPU line
            preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat1, $matches1);
            preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat2, $matches2);

            if (!$matches1 || !$matches2) {
                return 0;
            }

            $idle1 = $matches1[4];
            $idle2 = $matches2[4];

            $total1 = array_sum(array_slice($matches1, 1));
            $total2 = array_sum(array_slice($matches2, 1));

            $totalDiff = $total2 - $total1;
            $idleDiff = $idle2 - $idle1;

            if ($totalDiff == 0) {
                return 0;
            }

            $usage = (($totalDiff - $idleDiff) / $totalDiff) * 100;

            return round($usage, 2);
        } catch (Exception $e) {
            log_message('error', 'CPU monitoring error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get CPU usage on Windows
     *
     * @return float CPU usage percentage
     */
    private static function getCpuUsageWindows()
    {
        try {
            // Use WMIC to get CPU usage
            $output = shell_exec('wmic cpu get loadpercentage');
            if ($output) {
                preg_match('/\d+/', $output, $matches);
                return isset($matches[0]) ? (float)$matches[0] : 0;
            }
            return 0;
        } catch (Exception $e) {
            log_message('error', 'CPU monitoring error (Windows): ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get RAM usage percentage
     *
     * @return float RAM usage percentage (0-100)
     */
    public static function getRamUsage()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return self::getRamUsageWindows();
        } else {
            return self::getRamUsageLinux();
        }
    }

    /**
     * Get RAM usage on Linux
     *
     * @return float RAM usage percentage
     */
    private static function getRamUsageLinux()
    {
        try {
            $meminfo = @file_get_contents('/proc/meminfo');
            if (!$meminfo) {
                return 0;
            }

            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);

            if (!$total || !$available) {
                return 0;
            }

            $totalMem = (int)$total[1];
            $availableMem = (int)$available[1];
            $usedMem = $totalMem - $availableMem;

            if ($totalMem == 0) {
                return 0;
            }

            $usage = ($usedMem / $totalMem) * 100;

            return round($usage, 2);
        } catch (Exception $e) {
            log_message('error', 'RAM monitoring error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get RAM usage on Windows
     *
     * @return float RAM usage percentage
     */
    private static function getRamUsageWindows()
    {
        try {
            $output = shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
            if ($output) {
                preg_match('/FreePhysicalMemory=(\d+)/', $output, $free);
                preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $total);

                if ($free && $total) {
                    $freeMem = (int)$free[1];
                    $totalMem = (int)$total[1];
                    $usedMem = $totalMem - $freeMem;

                    if ($totalMem == 0) {
                        return 0;
                    }

                    $usage = ($usedMem / $totalMem) * 100;
                    return round($usage, 2);
                }
            }
            return 0;
        } catch (Exception $e) {
            log_message('error', 'RAM monitoring error (Windows): ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get disk usage percentage
     *
     * @param string $path Path to check (default: root)
     * @return float Disk usage percentage (0-100)
     */
    public static function getDiskUsage($path = '/')
    {
        try {
            // For Windows, use C: drive
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $path = 'C:';
            }

            $total = @disk_total_space($path);
            $free = @disk_free_space($path);

            if (!$total || !$free) {
                return 0;
            }

            $used = $total - $free;
            $usage = ($used / $total) * 100;

            return round($usage, 2);
        } catch (Exception $e) {
            log_message('error', 'Disk monitoring error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get disk space information in human-readable format
     *
     * @param string $path Path to check
     * @return array Disk space info
     */
    public static function getDiskInfo($path = '/')
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $path = 'C:';
            }

            $total = @disk_total_space($path);
            $free = @disk_free_space($path);
            $used = $total - $free;

            return [
                'total' => self::formatBytes($total),
                'used' => self::formatBytes($used),
                'free' => self::formatBytes($free),
                'percentage' => self::getDiskUsage($path)
            ];
        } catch (Exception $e) {
            return [
                'total' => '0 B',
                'used' => '0 B',
                'free' => '0 B',
                'percentage' => 0
            ];
        }
    }

    /**
     * Check if Apache is running
     *
     * @return bool True if running, false otherwise
     */
    public static function isApacheRunning()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - check WAMP Apache service
            $output = shell_exec('tasklist /FI "IMAGENAME eq httpd.exe"');
            return strpos($output, 'httpd.exe') !== false;
        } else {
            // Linux - check Apache/Apache2 service
            $output = shell_exec('systemctl is-active apache2 2>/dev/null || systemctl is-active httpd 2>/dev/null');
            return trim($output) === 'active';
        }
    }

    /**
     * Check if MySQL is running
     *
     * @return bool True if running, false otherwise
     */
    public static function isMysqlRunning()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - check MySQL process
            $output = shell_exec('tasklist /FI "IMAGENAME eq mysqld.exe"');
            return strpos($output, 'mysqld.exe') !== false;
        } else {
            // Linux - check MySQL/MariaDB service
            $output = shell_exec('systemctl is-active mysql 2>/dev/null || systemctl is-active mariadb 2>/dev/null');
            return trim($output) === 'active';
        }
    }

    /**
     * Get all server metrics
     *
     * @return array All metrics
     */
    public static function getAllMetrics()
    {
        return [
            'cpu' => self::getCpuUsage(),
            'ram' => self::getRamUsage(),
            'disk' => self::getDiskUsage(),
            'apache' => self::isApacheRunning(),
            'mysql' => self::isMysqlRunning(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Collect and store metrics in database
     *
     * @return bool Success status
     */
    public static function collectAndStore()
    {
        try {
            $cpu = self::getCpuUsage();
            $ram = self::getRamUsage();
            $disk = self::getDiskUsage();

            SystemMetric::record('cpu', $cpu);
            SystemMetric::record('ram', $ram);
            SystemMetric::record('disk', $disk);

            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to collect metrics: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get system uptime
     *
     * @return string Uptime string
     */
    public static function getUptime()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec('net statistics workstation | find "Statistics since"');
            return $output ? trim($output) : 'Unknown';
        } else {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $seconds = (int)explode(' ', $uptime)[0];
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $minutes = floor(($seconds % 3600) / 60);

                return "{$days}d {$hours}h {$minutes}m";
            }
            return 'Unknown';
        }
    }

    /**
     * Get load average (Linux only)
     *
     * @return array|null Load averages
     */
    public static function getLoadAverage()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return null;
        }

        $loadavg = @file_get_contents('/proc/loadavg');
        if ($loadavg) {
            $values = explode(' ', $loadavg);
            return [
                '1min' => (float)$values[0],
                '5min' => (float)$values[1],
                '15min' => (float)$values[2]
            ];
        }

        return null;
    }
}
