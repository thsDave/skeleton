<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Redirect;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogsController extends Controller
{
    private AuditLog $model;

    public function __construct()
    {
        $this->model = new AuditLog();
    }

    public function index(): void
    {
        Auth::requirePermission('audit_logs.view');
        $authUser = Auth::user();

        $filters = $this->buildFilters();
        $logs    = $this->model->getAll($filters);
        $total   = $this->model->countAll([]);
        $modules = $this->model->getDistinctModules();
        $actions = $this->model->getDistinctActions();
        $users   = (new User())->getAll();

        $this->view('audit_logs.index',
            compact('authUser', 'logs', 'total', 'filters', 'modules', 'actions', 'users'));
    }

    public function show(string $id): void
    {
        Auth::requirePermission('audit_logs.show');
        $authUser = Auth::user();
        $logId    = (int)$id;
        $log      = $this->model->findById($logId);

        if (!$log) {
            Redirect::withError('/audit-logs', __('audit_logs.not_found'));
        }

        $this->view('audit_logs.show', compact('authUser', 'log'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function buildFilters(): array
    {
        $raw = [
            'user_id'   => $_GET['user_id']   ?? '',
            'module'    => $_GET['module']    ?? '',
            'action'    => $_GET['action']    ?? '',
            'status'    => $_GET['status']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];

        // Sanitize date fields
        foreach (['date_from', 'date_to'] as $d) {
            if (!empty($raw[$d]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw[$d])) {
                $raw[$d] = '';
            }
        }

        return $raw;
    }
}
