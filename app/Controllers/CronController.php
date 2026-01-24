<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/CronJob.php';
require_once __DIR__ . '/../Models/CronJobLog.php';
require_once __DIR__ . '/../Services/CronService.php';

/**
 * Cron Controller
 * Handles cron job management requests
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class CronController extends Controller
{
    private $cronService;

    public function __construct()
    {
        parent::__construct();
        $this->cronService = new CronService();
    }

    /**
     * List all cron jobs
     */
    public function index()
    {
        $this->requireAuth();

        $cronModel = new CronJob();
        $jobs = $cronModel->getAll();

        $this->render('cron/index', [
            'jobs' => $jobs,
            'pageTitle' => 'Cron Jobs'
        ]);
    }

    /**
     * Show cron job creation form
     */
    public function create()
    {
        $this->requireAuth();

        $commonSchedules = $this->cronService->getCommonSchedules();

        $this->render('cron/create', [
            'commonSchedules' => $commonSchedules,
            'pageTitle' => 'Create Cron Job'
        ]);
    }

    /**
     * Create new cron job
     */
    public function store()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $name = $_POST['name'] ?? '';
        $command = $_POST['command'] ?? '';
        $schedule = $_POST['schedule'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($name)) {
            $_SESSION['error'] = 'Job name is required';
            header('Location: /cron/create');
            exit;
        }

        if (empty($command)) {
            $_SESSION['error'] = 'Command is required';
            header('Location: /cron/create');
            exit;
        }

        if (empty($schedule)) {
            $_SESSION['error'] = 'Schedule is required';
            header('Location: /cron/create');
            exit;
        }

        $result = $this->cronService->addJob(
            $name,
            $command,
            $schedule,
            $description,
            $_SESSION['user_id']
        );

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: /cron/' . $result['job_id']);
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: /cron/create');
        }
        exit;
    }

    /**
     * Show cron job details
     */
    public function show($id)
    {
        $this->requireAuth();

        $cronModel = new CronJob();
        $job = $cronModel->find($id);

        if (!$job) {
            $_SESSION['error'] = 'Cron job not found';
            header('Location: /cron');
            exit;
        }

        // Get execution logs
        $logModel = new CronJobLog();
        $logs = $logModel->getByCronJob($id, 20);

        $this->render('cron/show', [
            'job' => $job,
            'logs' => $logs,
            'pageTitle' => 'Cron Job: ' . $job['name']
        ]);
    }

    /**
     * Execute cron job manually
     */
    public function execute($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $result = $this->cronService->executeJob($id);

        if ($result['success']) {
            $_SESSION['success'] = 'Job executed successfully (' . $result['status'] . ')';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /cron/' . $id);
        exit;
    }

    /**
     * Toggle cron job enabled status
     */
    public function toggle($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $cronModel = new CronJob();
        $job = $cronModel->find($id);

        if (!$job) {
            $_SESSION['error'] = 'Cron job not found';
            header('Location: /cron');
            exit;
        }

        $newStatus = !$job['enabled'];
        $result = $this->cronService->toggleJob($id, $newStatus);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /cron/' . $id);
        exit;
    }

    /**
     * Delete cron job
     */
    public function destroy($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $result = $this->cronService->removeJob($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /cron');
        exit;
    }

    /**
     * View system crontab
     */
    public function viewCrontab()
    {
        $this->requireAuth();

        $result = $this->cronService->getSystemCrontab();

        $this->render('cron/crontab', [
            'crontab' => $result['crontab'] ?? 'Unable to read crontab',
            'pageTitle' => 'System Crontab'
        ]);
    }
}
