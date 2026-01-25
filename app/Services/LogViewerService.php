<?php

/**
 * Log Viewer Service
 * Read and manage system logs on Ubuntu/Debian
 *
 * @package ChweziSM
 * @subpackage Services
 */
class LogViewerService
{
    private $logPaths;
    private $specificLogs;
    private $maxFileSize;

    public function __construct()
    {
        // Common log locations on Ubuntu/Debian
        $this->logPaths = [
            'apache' => '/var/log/apache2',
            'nginx' => '/var/log/nginx',
            'php' => '/var/log/php',
            'mysql' => '/var/log/mysql',
            'application' => __DIR__ . '/../../storage/logs',
            'system' => '/var/log'
        ];

        // Additional specific log files (PHP error logs in various locations)
        $this->specificLogs = [
            'php' => [
                '/var/log/php_errors.log',
                '/var/log/php-fpm/error.log',
                '/var/log/php-fpm/www-error.log',
                '/var/log/php8.0-fpm.log',
                '/var/log/php8.1-fpm.log',
                '/var/log/php8.2-fpm.log',
                '/var/log/php8.3-fpm.log',
                '/var/log/php8.4-fpm.log',
                __DIR__ . '/../../storage/logs/php_error.log',
                ini_get('error_log'), // PHP configured error log
            ],
            'syslog' => [
                '/var/log/syslog',
                '/var/log/messages',
                '/var/log/auth.log',
            ]
        ];

        // Maximum file size to read (10MB)
        $this->maxFileSize = 10 * 1024 * 1024;
    }

    /**
     * Get list of available log files
     */
    public function getAvailableLogs()
    {
        $logs = [];
        $addedPaths = []; // Track added paths to avoid duplicates

        // Check if running on Windows (development)
        if (stripos(PHP_OS, 'WIN') === 0) {
            return $this->getWindowsLogs();
        }

        // Add specific log files (like PHP error logs)
        if (isset($this->specificLogs)) {
            foreach ($this->specificLogs as $category => $files) {
                foreach ($files as $file) {
                    if (empty($file) || isset($addedPaths[$file])) continue;
                    if (!file_exists($file) || !is_readable($file)) continue;

                    $addedPaths[$file] = true;
                    $logs[] = [
                        'category' => $category,
                        'name' => basename($file),
                        'path' => $file,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                        'readable' => true
                    ];
                }
            }
        }

        // Scan directories
        foreach ($this->logPaths as $category => $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = $this->scanDirectory($path);

            foreach ($files as $file) {
                if (isset($addedPaths[$file])) continue;
                $addedPaths[$file] = true;

                $logs[] = [
                    'category' => $category,
                    'name' => basename($file),
                    'path' => $file,
                    'size' => file_exists($file) ? filesize($file) : 0,
                    'modified' => file_exists($file) ? filemtime($file) : 0,
                    'readable' => is_readable($file)
                ];
            }
        }

        // Sort by modified time (newest first)
        usort($logs, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $logs;
    }

    /**
     * Get Windows development logs
     */
    private function getWindowsLogs()
    {
        $logs = [];
        $appLogPath = __DIR__ . '/../../storage/logs';

        if (is_dir($appLogPath)) {
            $files = $this->scanDirectory($appLogPath);

            foreach ($files as $file) {
                $logs[] = [
                    'category' => 'application',
                    'name' => basename($file),
                    'path' => $file,
                    'size' => file_exists($file) ? filesize($file) : 0,
                    'modified' => file_exists($file) ? filemtime($file) : 0,
                    'readable' => is_readable($file)
                ];
            }
        }

        // Add WAMP logs if accessible
        $wampLogPaths = [
            'C:/wamp64/logs/apache_error.log' => 'apache',
            'C:/wamp64/logs/php_error.log' => 'php',
            'C:/wamp64/bin/mysql/mysql8.0.36/data/error.log' => 'mysql',
        ];

        foreach ($wampLogPaths as $file => $category) {
            if (file_exists($file)) {
                $logs[] = [
                    'category' => $category,
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'readable' => is_readable($file)
                ];
            }
        }

        return $logs;
    }

    /**
     * Scan directory for log files
     */
    private function scanDirectory($path)
    {
        $files = [];

        if (!is_dir($path) || !is_readable($path)) {
            return $files;
        }

        try {
            $items = scandir($path);

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $fullPath = $path . '/' . $item;

                // Only include files that look like logs
                if (is_file($fullPath) && $this->isLogFile($item)) {
                    $files[] = $fullPath;
                }
            }
        } catch (Exception $e) {
            // Silently fail if directory is not accessible
        }

        return $files;
    }

    /**
     * Check if file is a log file based on extension/name
     */
    private function isLogFile($filename)
    {
        $logExtensions = ['log', 'txt', 'err', 'out'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return in_array($extension, $logExtensions) ||
               strpos($filename, '.log') !== false ||
               strpos($filename, 'access') !== false ||
               strpos($filename, 'error') !== false;
    }

    /**
     * Read log file content
     */
    public function readLog($filepath, $lines = 100, $offset = 0)
    {
        try {
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Log file not found'
                ];
            }

            if (!is_readable($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Log file is not readable'
                ];
            }

            $filesize = filesize($filepath);

            if ($filesize > $this->maxFileSize) {
                return [
                    'success' => false,
                    'message' => 'Log file is too large. Use tail or download instead.'
                ];
            }

            // Read last N lines
            $content = $this->tailFile($filepath, $lines, $offset);

            return [
                'success' => true,
                'content' => $content,
                'size' => $filesize,
                'lines' => substr_count($content, "\n"),
                'modified' => filemtime($filepath)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Tail a log file (get last N lines)
     */
    public function tailFile($filepath, $lines = 100, $offset = 0)
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return '';
        }

        // Check if running on Windows
        if (stripos(PHP_OS, 'WIN') === 0) {
            return $this->tailFilePhp($filepath, $lines, $offset);
        }

        // Use tail command on Linux (faster for large files)
        $skip = $offset > 0 ? " | tail -n +$offset" : '';
        $command = "tail -n $lines " . escapeshellarg($filepath) . $skip . " 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            return implode("\n", $output);
        }

        // Fallback to PHP method
        return $this->tailFilePhp($filepath, $lines, $offset);
    }

    /**
     * Pure PHP implementation of tail
     */
    private function tailFilePhp($filepath, $lines = 100, $offset = 0)
    {
        $file = new SplFileObject($filepath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key() + 1;

        $startLine = max(0, $totalLines - $lines - $offset);
        $endLine = $totalLines - $offset;

        $result = [];
        $file->seek($startLine);

        for ($i = $startLine; $i < $endLine; $i++) {
            $result[] = $file->current();
            $file->next();
        }

        return implode('', $result);
    }

    /**
     * Search within a log file
     */
    public function searchLog($filepath, $query, $caseSensitive = false)
    {
        try {
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Log file not found'
                ];
            }

            if (!is_readable($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Log file is not readable'
                ];
            }

            // Use grep on Linux for better performance
            if (stripos(PHP_OS, 'WIN') !== 0) {
                $caseFlag = $caseSensitive ? '' : '-i';
                $command = "grep $caseFlag " . escapeshellarg($query) . " " . escapeshellarg($filepath) . " 2>&1";
                exec($command, $output, $returnCode);

                $matches = $output;
            } else {
                // PHP fallback for Windows
                $matches = [];
                $file = fopen($filepath, 'r');

                while (($line = fgets($file)) !== false) {
                    $haystack = $caseSensitive ? $line : strtolower($line);
                    $needle = $caseSensitive ? $query : strtolower($query);

                    if (strpos($haystack, $needle) !== false) {
                        $matches[] = $line;
                    }

                    // Limit results to prevent memory issues
                    if (count($matches) >= 1000) {
                        break;
                    }
                }

                fclose($file);
            }

            return [
                'success' => true,
                'matches' => $matches,
                'count' => count($matches)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get log file info
     */
    public function getLogInfo($filepath)
    {
        if (!file_exists($filepath)) {
            return null;
        }

        $lines = 0;
        $file = fopen($filepath, 'r');
        while (fgets($file) !== false) {
            $lines++;
        }
        fclose($file);

        return [
            'path' => $filepath,
            'name' => basename($filepath),
            'size' => filesize($filepath),
            'size_formatted' => $this->formatBytes(filesize($filepath)),
            'modified' => filemtime($filepath),
            'modified_formatted' => date('Y-m-d H:i:s', filemtime($filepath)),
            'lines' => $lines,
            'readable' => is_readable($filepath),
            'writable' => is_writable($filepath)
        ];
    }

    /**
     * Clear/truncate a log file
     */
    public function clearLog($filepath)
    {
        try {
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Log file not found'
                ];
            }

            if (!is_writable($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Log file is not writable'
                ];
            }

            file_put_contents($filepath, '');

            return [
                'success' => true,
                'message' => 'Log file cleared successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Download log file
     */
    public function downloadLog($filepath)
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return false;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    /**
     * Format bytes to human-readable size
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get log categories
     */
    public function getCategories()
    {
        return [
            'apache' => 'Apache Web Server',
            'nginx' => 'Nginx Web Server',
            'php' => 'PHP Error Logs',
            'mysql' => 'MySQL Database',
            'application' => 'Application Logs',
            'system' => 'System Logs',
            'syslog' => 'System Messages'
        ];
    }
}
