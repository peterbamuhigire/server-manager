<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Services/LogViewerService.php';

/**
 * Log Controller
 * Handles log viewing requests
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class LogController extends Controller
{
    private $logService;

    public function __construct()
    {
        parent::__construct();
        $this->logService = new LogViewerService();
    }

    /**
     * List all available log files
     */
    public function index()
    {
        $this->requireAuth();

        $logs = $this->logService->getAvailableLogs();
        $categories = $this->logService->getCategories();

        // Group logs by category
        $groupedLogs = [];
        foreach ($logs as $log) {
            $category = $log['category'];
            if (!isset($groupedLogs[$category])) {
                $groupedLogs[$category] = [];
            }
            $groupedLogs[$category][] = $log;
        }

        $this->render('logs/index', [
            'logs' => $logs,
            'groupedLogs' => $groupedLogs,
            'categories' => $categories,
            'pageTitle' => 'Log Viewer'
        ]);
    }

    /**
     * View a specific log file
     */
    public function showLog()
    {
        $this->requireAuth();

        $filepath = $_GET['file'] ?? '';

        if (empty($filepath)) {
            $_SESSION['error'] = 'No log file specified';
            header('Location: /logs');
            exit;
        }

        // Security: Decode and validate filepath
        $filepath = base64_decode($filepath);

        if (!file_exists($filepath)) {
            $_SESSION['error'] = 'Log file not found';
            header('Location: /logs');
            exit;
        }

        $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
        $lines = min(max($lines, 10), 1000); // Between 10 and 1000

        $result = $this->logService->readLog($filepath, $lines);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            header('Location: /logs');
            exit;
        }

        $info = $this->logService->getLogInfo($filepath);

        $this->render('logs/view', [
            'file' => $info,
            'content' => $result['content'],
            'lines' => $lines,
            'pageTitle' => 'View Log: ' . basename($filepath)
        ]);
    }

    /**
     * Tail a log file (live view)
     */
    public function tail()
    {
        $this->requireAuth();

        $filepath = $_GET['file'] ?? '';

        if (empty($filepath)) {
            $_SESSION['error'] = 'No log file specified';
            header('Location: /logs');
            exit;
        }

        // Security: Decode and validate filepath
        $filepath = base64_decode($filepath);

        if (!file_exists($filepath)) {
            $_SESSION['error'] = 'Log file not found';
            header('Location: /logs');
            exit;
        }

        // Check if this is an AJAX request for content
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');

            $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 50;
            $content = $this->logService->tailFile($filepath, $lines);

            echo json_encode([
                'success' => true,
                'content' => htmlspecialchars($content),
                'timestamp' => time()
            ]);
            exit;
        }

        $info = $this->logService->getLogInfo($filepath);

        $this->render('logs/tail', [
            'file' => $info,
            'pageTitle' => 'Tail Log: ' . basename($filepath)
        ]);
    }

    /**
     * Search within a log file
     */
    public function search()
    {
        $this->requireAuth();

        $filepath = $_POST['file'] ?? '';
        $query = $_POST['query'] ?? '';
        $caseSensitive = isset($_POST['case_sensitive']);

        if (empty($filepath) || empty($query)) {
            $_SESSION['error'] = 'File and search query are required';
            header('Location: /logs');
            exit;
        }

        // Security: Decode and validate filepath
        $filepath = base64_decode($filepath);

        if (!file_exists($filepath)) {
            $_SESSION['error'] = 'Log file not found';
            header('Location: /logs');
            exit;
        }

        $result = $this->logService->searchLog($filepath, $query, $caseSensitive);
        $info = $this->logService->getLogInfo($filepath);

        $this->render('logs/search', [
            'file' => $info,
            'query' => $query,
            'caseSensitive' => $caseSensitive,
            'matches' => $result['matches'] ?? [],
            'count' => $result['count'] ?? 0,
            'pageTitle' => 'Search Log: ' . basename($filepath)
        ]);
    }

    /**
     * Download a log file
     */
    public function download()
    {
        $this->requireAuth();

        $filepath = $_GET['file'] ?? '';

        if (empty($filepath)) {
            $_SESSION['error'] = 'No log file specified';
            header('Location: /logs');
            exit;
        }

        // Security: Decode and validate filepath
        $filepath = base64_decode($filepath);

        if (!file_exists($filepath)) {
            $_SESSION['error'] = 'Log file not found';
            header('Location: /logs');
            exit;
        }

        $this->logService->downloadLog($filepath);
    }

    /**
     * Clear a log file
     */
    public function clear()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $filepath = $_POST['file'] ?? '';

        if (empty($filepath)) {
            $_SESSION['error'] = 'No log file specified';
            header('Location: /logs');
            exit;
        }

        // Security: Decode and validate filepath
        $filepath = base64_decode($filepath);

        $result = $this->logService->clearLog($filepath);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        // Redirect back to log view
        header('Location: /logs/view?file=' . base64_encode($filepath));
        exit;
    }
}
