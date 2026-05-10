<?php

namespace App\Controllers;

use Core\Audit;
use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Validator;
use Core\Logger;
use App\Models\SystemSetting;
use App\Models\UserManual;
use App\Models\Status;
use App\Services\UploadService;

class SystemInformationController extends Controller
{
    private SystemSetting $settingModel;
    private UserManual    $manualModel;
    private Status        $statusModel;

    public function __construct()
    {
        $this->settingModel = new SystemSetting();
        $this->manualModel  = new UserManual();
        $this->statusModel  = new Status();
    }

    // ─── Info del sistema (vista pública para todos los autenticados) ──────────

    public function index(): void
    {
        Auth::requirePermission('system_information.view');
        $authUser    = Auth::user();
        $setting     = $this->settingModel->get();
        $canManage   = can('manuals.upload') || can('manuals.edit') || can('manuals.activate') || can('manuals.deactivate');
        $manuals     = $canManage ? $this->manualModel->getAll() : $this->manualModel->getActive();
        $this->view('system_information.index', compact('authUser', 'setting', 'manuals'));
    }

    public function edit(): void
    {
        Auth::requirePermission('system_information.edit');
        $authUser = Auth::user();
        $setting  = $this->settingModel->get();
        $this->view('system_information.edit', compact('authUser', 'setting'));
    }

    public function update(): void
    {
        Auth::requirePermission('system_information.edit');

        if (!$this->isPost()) {
            Redirect::to('/system-information');
        }

        CSRF::validateOrFail();

        $year    = trim($this->input('release_year', ''));
        $leader  = trim($this->input('project_leader', ''));
        $version = trim($this->input('system_version', ''));

        $validator = new Validator();
        $validator->required('release_year', $year, 'Año de lanzamiento')
                  ->required('project_leader', $leader, 'Líder del proyecto')
                  ->maxLength('project_leader', $leader, 150, 'Líder del proyecto')
                  ->required('system_version', $version, 'Versión del sistema');

        if ($validator->fails()) {
            Redirect::withErrors('/system-information/edit', $validator->errors(),
                compact('year', 'leader', 'version'));
        }

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            Redirect::withErrors('/system-information/edit',
                ['system_version' => __('system.version_hint')],
                compact('year', 'leader', 'version'));
        }

        $yearInt = (int)$year;
        if ($yearInt < 2000 || $yearInt > 2100) {
            Redirect::withErrors('/system-information/edit',
                ['release_year' => 'El año de lanzamiento debe estar entre 2000 y 2100.'],
                compact('year', 'leader', 'version'));
        }

        $oldSetting = $this->settingModel->get();

        try {
            if ($this->settingModel->createOrUpdate([
                'release_year'   => $yearInt,
                'project_leader' => $leader,
                'system_version' => $version,
            ])) {
                Logger::security("Info del sistema actualizada por admin ID " . Auth::id());
                Audit::log(['module' => 'system_information', 'action' => 'updated',
                    'entity' => 'system_setting',
                    'description' => 'Información del sistema actualizada',
                    'old_values' => ['release_year' => $oldSetting['release_year'] ?? null,
                        'project_leader' => $oldSetting['project_leader'] ?? null,
                        'system_version' => $oldSetting['system_version'] ?? null],
                    'new_values' => ['release_year' => $yearInt, 'project_leader' => $leader, 'system_version' => $version],
                    'status' => 'success']);
                Redirect::withSuccess('/system-information', __('system.updated_ok'));
            } else {
                Redirect::withError('/system-information/edit', __('alerts.internal'));
            }
        } catch (\PDOException $e) {
            Logger::error('SystemInformationController::update PDOException: ' . $e->getMessage());
            Redirect::withError('/system-information/edit', __('alerts.internal'));
        }
    }

    // ─── Gestión de manuales (admin) ─────────────────────────────────────────

    public function createManual(): void
    {
        Auth::requirePermission('manuals.upload');
        $authUser = Auth::user();
        $statuses = $this->statusModel->getAll();
        $this->view('manuals.create', compact('authUser', 'statuses'));
    }

    public function storeManual(): void
    {
        Auth::requirePermission('manuals.upload');

        if (!$this->isPost()) {
            Redirect::to('/system-information');
        }

        CSRF::validateOrFail();

        $title       = trim($this->input('title', ''));
        $description = trim($this->input('description', ''));

        $validator = new Validator();
        $validator->required('title', $title, __('manuals.title_field'))
                  ->maxLength('title', $title, 150, __('manuals.title_field'));

        if ($validator->fails()) {
            Redirect::withErrors('/manuals/create', $validator->errors(),
                compact('title', 'description'));
        }

        if (!isset($_FILES['manual_file']) || $_FILES['manual_file']['error'] === UPLOAD_ERR_NO_FILE) {
            Redirect::withErrors('/manuals/create', ['manual_file' => __('manuals.file_required')],
                compact('title', 'description'));
        }

        $result = (new UploadService())->upload(
            $_FILES['manual_file'],
            'user_manuals',
            ['prefix' => 'manual_' . Auth::id() . '_']
        );
        if (!$result['success']) {
            Redirect::withErrors('/manuals/create', ['manual_file' => $result['error']],
                compact('title', 'description'));
        }

        try {
            $newId = $this->manualModel->create([
                'title'       => $title,
                'description' => $description ?: null,
                'file_name'   => $result['original_name'],
                'file_path'   => $result['filename'],
                'file_type'   => $result['mime'],
                'file_size'   => $result['size'],
                'uploaded_by' => Auth::id(),
                'status_id'   => 1,
            ]);

            if ($newId) {
                Logger::security("Manual subido ID {$newId} por admin ID " . Auth::id());
                Audit::log(['module' => 'manuals', 'action' => 'uploaded',
                    'entity' => 'manual', 'entity_id' => $newId,
                    'description' => "Manual subido: {$title}",
                    'new_values' => ['title' => $title, 'file_name' => $result['original_name'], 'file_size' => $result['size']],
                    'status' => 'success']);
                Redirect::withSuccess('/system-information', __('manuals.uploaded_ok'));
            } else {
                Redirect::withError('/manuals/create', __('alerts.internal'));
            }
        } catch (\PDOException $e) {
            Logger::error('SystemInformationController::storeManual PDOException: ' . $e->getMessage());
            Redirect::withError('/manuals/create', __('alerts.internal'));
        }
    }

    public function toggleManual(string $id): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            Redirect::to('/system-information');
        }

        CSRF::validateOrFail();

        $manualId = (int)$id;
        $manual   = $this->manualModel->findById($manualId);

        if (!$manual) {
            Redirect::withError('/system-information', __('manuals.not_found'));
        }

        $isActive          = ($manual['status_slug'] ?? '') === 'active';
        $requiredPermission = $isActive ? 'manuals.deactivate' : 'manuals.activate';
        Auth::requirePermission($requiredPermission);
        $statuses  = $this->statusModel->getAll();
        $newStatus = 1;
        foreach ($statuses as $s) {
            if (!$isActive && $s['slug'] === 'active')   { $newStatus = $s['id']; break; }
            if ($isActive  && $s['slug'] === 'inactive') { $newStatus = $s['id']; break; }
        }

        try {
            if ($this->manualModel->setStatus($manualId, $newStatus)) {
                $action = $isActive ? 'deactivated' : 'activated';
                $msg    = $isActive ? __('manuals.deactivated_ok') : __('manuals.activated_ok');
                Audit::log(['module' => 'manuals', 'action' => $action,
                    'entity' => 'manual', 'entity_id' => $manualId,
                    'description' => "Manual ID {$manualId} " . ($isActive ? 'desactivado' : 'activado'),
                    'status' => 'success']);
                Redirect::withSuccess('/system-information', $msg);
            } else {
                Redirect::withError('/system-information', __('alerts.internal'));
            }
        } catch (\PDOException $e) {
            Logger::error('SystemInformationController::toggleManual PDOException: ' . $e->getMessage());
            Redirect::withError('/system-information', __('alerts.internal'));
        }
    }

    public function downloadManual(string $id): void
    {
        Auth::requirePermission('manuals.view');

        $manualId = (int)$id;
        $manual   = $this->manualModel->findById($manualId);

        if (!$manual || ($manual['status_slug'] ?? '') !== 'active') {
            Redirect::withError('/system-information', __('manuals.not_found'));
        }

        $config   = require dirname(__DIR__, 2) . '/config/app.php';
        $filePath = $config['upload_manuals_path'] . $manual['file_path'];

        if (!file_exists($filePath)) {
            Redirect::withError('/system-information', __('manuals.not_found'));
        }

        $originalName = $manual['file_name'];
        $mimeType     = $manual['file_type'] ?: 'application/octet-stream';

        Audit::log(['module' => 'manuals', 'action' => 'downloaded',
            'entity' => 'manual', 'entity_id' => $manualId,
            'description' => "Manual descargado: {$originalName}",
            'status' => 'success']);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');
        readfile($filePath);
        exit;
    }

}
