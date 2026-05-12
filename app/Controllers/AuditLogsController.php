<?php

namespace App\Controllers;

use Core\Audit;
use Core\Controller;
use Core\Auth;
use Core\Logger;
use Core\Redirect;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ExcelExportService;

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

    public function exportExcel(): void
    {
        Auth::requirePermission('audit_logs.export');

        $filters = $this->buildFilters();

        try {
            $logs = $this->model->getForExport($filters, 5000);
            if (empty($logs)) {
                Redirect::withError('/audit-logs?' . http_build_query(array_filter($filters)), __('export.no_records'));
            }

            $rows = array_map(function (array $log): array {
                return [
                    'id' => (int)$log['id'],
                    'fecha' => $log['created_at'] ?? '',
                    'usuario' => $log['user_name'] ?: __('audit_logs.system_user'),
                    'correo' => $log['user_email'] ?? '',
                    'modulo' => $log['module'] ?? '',
                    'accion' => $log['action'] ?? '',
                    'estado' => $log['status'] ?? '',
                    'ip' => $log['ip_address'] ?? '',
                    'user_agent' => $log['user_agent'] ?? '',
                    'descripcion' => $log['description'] ?? '',
                    'entidad' => $log['entity'] ?? '',
                    'entidad_id' => $log['entity_id'] ?? '',
                    'ruta' => $log['route'] ?? '',
                    'metodo' => $log['method'] ?? '',
                ];
            }, $logs);

            Audit::log(['module' => 'audit_logs', 'action' => 'exported',
                'entity' => 'audit_log',
                'description' => 'Exportacion Excel de auditoria',
                'new_values' => ['records_count' => count($rows), 'filters' => array_filter($filters)],
                'status' => 'success']);

            (new ExcelExportService())->download(
                'auditoria_' . date('Ymd_His') . '.xlsx',
                [
                    'id' => 'ID',
                    'fecha' => __('audit_logs.created_at'),
                    'usuario' => __('audit_logs.user'),
                    'correo' => __('users.col_email'),
                    'modulo' => __('audit_logs.module'),
                    'accion' => __('audit_logs.action'),
                    'estado' => __('audit_logs.status'),
                    'ip' => __('audit_logs.ip_address'),
                    'user_agent' => __('audit_logs.user_agent'),
                    'descripcion' => __('audit_logs.description_text'),
                    'entidad' => __('audit_logs.entity'),
                    'entidad_id' => __('audit_logs.entity_id'),
                    'ruta' => __('audit_logs.route'),
                    'metodo' => __('audit_logs.method'),
                ],
                $rows,
                [
                    __('export.generated_at') => date('Y-m-d H:i:s'),
                    __('export.generated_by') => Auth::user()['email'] ?? '',
                    __('export.records_count') => (string)count($rows),
                    __('export.filters') => json_encode(array_filter($filters), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable $e) {
            Logger::error('AuditLogsController::exportExcel: ' . $e->getMessage());
            Audit::log(['module' => 'audit_logs', 'action' => 'export_failed',
                'description' => 'Error al exportar auditoria',
                'new_values' => ['filters' => array_filter($filters)],
                'status' => 'failed']);
            Redirect::withError('/audit-logs?' . http_build_query(array_filter($filters)), __('export.error'));
        }
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
