<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Services/WebTerminalService.php';

/**
 * Terminal Controller
 * Handles web terminal requests
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class TerminalController extends Controller
{
    private $terminalService;

    public function __construct()
    {
        parent::__construct();
        $this->terminalService = new WebTerminalService();
    }

    /**
     * Show terminal interface
     */
    public function index()
    {
        $this->requireAuth();

        $stats = $this->terminalService->getStats($_SESSION['user_id']);
        $commonCommands = $this->terminalService->getCommonCommands();
        $workingDir = $this->terminalService->getWorkingDirectory();

        $this->render('terminal/index', [
            'stats' => $stats,
            'commonCommands' => $commonCommands,
            'workingDir' => $workingDir,
            'pageTitle' => 'Web Terminal'
        ]);
    }

    /**
     * Execute a command
     */
    public function execute()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        header('Content-Type: application/json');

        $command = $_POST['command'] ?? '';
        $confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === 'true';
        $userId = $_SESSION['user_id'];

        $result = $this->terminalService->executeCommand($command, $userId, $confirmed);

        echo json_encode($result);
        exit;
    }

    /**
     * Get command history (AJAX or page view)
     */
    public function history()
    {
        $this->requireAuth();

        $userId = $_SESSION['user_id'];

        // If AJAX request, return JSON
        if (isset($_GET['ajax']) || isset($_GET['limit'])) {
            header('Content-Type: application/json');

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $history = $this->terminalService->getHistory($userId, $limit);

            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            exit;
        }

        // Otherwise show history page
        $history = $this->terminalService->getHistory($userId, 100);
        $stats = $this->terminalService->getStats($userId);

        $this->render('terminal/history', [
            'history' => $history,
            'stats' => $stats,
            'pageTitle' => 'Command History'
        ]);
    }

    /**
     * Search command history
     */
    public function search()
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        $query = $_GET['query'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $userId = $_SESSION['user_id'];

        $results = $this->terminalService->searchHistory($query, $userId, $limit);

        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
        exit;
    }

    /**
     * Clear command history
     */
    public function clear()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $result = $this->terminalService->clearHistory($_SESSION['user_id']);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /terminal');
        exit;
    }

}
