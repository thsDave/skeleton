<?php

namespace App\Controllers;

use App\Services\TemporaryDataCleanupService;
use Core\Audit;
use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\Logger;
use Core\Redirect;
use Core\Session;

class MaintenanceController extends Controller
{
    private TemporaryDataCleanupService $cleanupService;

    public function __construct()
    {
        $this->cleanupService = new TemporaryDataCleanupService();
    }

    public function cleanup(): void
    {
        Auth::requirePermission('maintenance.cleanup.view');

        $authUser = Auth::user();
        $retention = $this->cleanupService->normalizeRetention($_GET['retention'] ?? []);
        $items = $this->cleanupService->getSummary($retention);
        $lastCleanup = $this->cleanupService->getLastCleanup();
        $result = Session::getFlash('cleanup_result');

        Audit::log([
            'module' => 'maintenance',
            'action' => 'maintenance.cleanup_viewed',
            'description' => 'Consulta de limpieza de datos temporales',
            'status' => 'info',
        ]);

        $this->view('maintenance.cleanup', compact('authUser', 'items', 'retention', 'lastCleanup', 'result'));
    }

    public function runCleanup(): void
    {
        Auth::requirePermission('maintenance.cleanup.run');
        CSRF::validateOrFail();

        $selected = $_POST['items'] ?? [];
        $retention = $this->cleanupService->normalizeRetention($_POST['retention'] ?? []);

        if (!is_array($selected) || empty($selected)) {
            Redirect::withError('/maintenance/cleanup', __('maintenance.cleanup_no_items'));
        }

        Audit::log([
            'module' => 'maintenance',
            'action' => 'maintenance.cleanup_started',
            'description' => 'Inicio de limpieza de datos temporales',
            'new_values' => [
                'items' => array_values(array_map('strval', $selected)),
                'retention_days' => $retention,
            ],
            'status' => 'info',
        ]);

        try {
            $results = $this->cleanupService->cleanupSelected($selected, $retention);
            $deletedTotal = array_sum(array_map(static fn(array $item): int => (int)($item['deleted'] ?? 0), $results));

            foreach ($results as $key => $result) {
                Audit::log([
                    'module' => 'maintenance',
                    'action' => 'maintenance.cleanup.' . $key,
                    'description' => 'Limpieza de categoria temporal',
                    'new_values' => [
                        'category' => $key,
                        'deleted' => (int)($result['deleted'] ?? 0),
                        'retention_days' => $retention[$key] ?? null,
                        'status' => $result['status'] ?? 'unknown',
                    ],
                    'status' => ($result['status'] ?? '') === 'failed' ? 'failed' : 'success',
                ]);
            }

            Audit::log([
                'module' => 'maintenance',
                'action' => 'maintenance.cleanup_completed',
                'description' => 'Limpieza de datos temporales completada',
                'new_values' => [
                    'items' => array_keys($results),
                    'deleted_total' => $deletedTotal,
                    'retention_days' => $retention,
                ],
                'status' => 'success',
            ]);

            Session::flash('cleanup_result', $results);
            Redirect::withSuccess('/maintenance/cleanup', __('maintenance.cleanup_completed'));
        } catch (\Throwable $e) {
            Logger::error('MaintenanceController::runCleanup: ' . $e->getMessage());
            Audit::log([
                'module' => 'maintenance',
                'action' => 'maintenance.cleanup_failed',
                'description' => 'Error al limpiar datos temporales',
                'new_values' => [
                    'items' => array_values(array_map('strval', $selected)),
                    'retention_days' => $retention,
                ],
                'status' => 'failed',
            ]);

            Redirect::withError('/maintenance/cleanup', __('maintenance.cleanup_failed'));
        }
    }
}
