<?php

namespace App\Controllers;

use App\Services\SystemHealthService;
use Core\Audit;
use Core\Auth;
use Core\Controller;

class SystemHealthController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission('system_health.view');

        $authUser = Auth::user();
        $report = (new SystemHealthService())->getReport();
        $sections = $report['sections'];
        $summary = $report['summary'];

        Audit::log([
            'module' => 'system_health',
            'action' => 'system_health.viewed',
            'description' => 'Consulta del panel de salud del sistema',
            'new_values' => [
                'total_checks' => $summary['total'] ?? 0,
                'warning_checks' => $summary['warning'] ?? 0,
                'error_checks' => $summary['danger'] ?? 0,
            ],
            'status' => 'info',
        ]);

        $this->view('system_health.index', compact('authUser', 'sections', 'summary'));
    }
}
