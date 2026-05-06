<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Validator;
use Core\Logger;
use App\Models\Language;
use App\Models\Status;

class LanguagesController extends Controller
{
    private Language $langModel;
    private Status   $statusModel;

    public function __construct()
    {
        $this->langModel   = new Language();
        $this->statusModel = new Status();
    }

    public function index(): void
    {
        Auth::requireAdmin();
        $authUser  = Auth::user();
        $languages = $this->langModel->getAll();
        $this->view('languages.index', compact('authUser', 'languages'));
    }

    public function create(): void
    {
        Auth::requireAdmin();
        $authUser = Auth::user();
        $statuses = $this->statusModel->getAll();
        $this->view('languages.create', compact('authUser', 'statuses'));
    }

    public function store(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            Redirect::to('/languages');
        }

        CSRF::validateOrFail();

        $name       = trim($this->input('name', ''));
        $nativeName = trim($this->input('native_name', ''));
        $code       = strtolower(trim($this->input('code', '')));
        $statusId   = (int)$this->input('status_id', 1);

        $validator = new Validator();
        $validator->required('name', $name, 'Nombre')
                  ->maxLength('name', $name, 100, 'Nombre')
                  ->required('code', $code, 'Código')
                  ->maxLength('code', $code, 10, 'Código')
                  ->required('status_id', $statusId ?: '', 'Estado');

        if ($validator->fails()) {
            Redirect::withErrors('/languages/create', $validator->errors(),
                compact('name', 'nativeName', 'code', 'statusId'));
        }

        if ($this->langModel->codeExists($code)) {
            Redirect::withErrors('/languages/create',
                ['code' => __('languages.code_unique')],
                compact('name', 'nativeName', 'code', 'statusId'));
        }

        try {
            $newId = $this->langModel->create([
                'name'        => $name,
                'native_name' => $nativeName ?: null,
                'code'        => $code,
                'status_id'   => $statusId,
            ]);

            if ($newId) {
                Logger::security("Idioma creado ID {$newId} por admin ID " . Auth::id());
                // Advertir si no existe el archivo de traducción
                $langFile = dirname(__DIR__, 2) . '/lang/' . $code . '.php';
                if (!file_exists($langFile)) {
                    $warning = __('languages.warning_no_file', ['code' => $code]);
                    \Core\Session::flash('warning', $warning);
                }
                Redirect::withSuccess('/languages', __('languages.created_ok'));
            } else {
                Redirect::withError('/languages/create', __('alerts.internal'));
            }
        } catch (\PDOException $e) {
            Logger::error('LanguagesController::store PDOException: ' . $e->getMessage());
            Redirect::withError('/languages/create', __('alerts.internal'));
        }
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();
        $authUser = Auth::user();
        $langId   = (int)$id;
        $language = $this->langModel->findById($langId);

        if (!$language) {
            Redirect::withError('/languages', __('languages.not_found'));
        }

        $statuses = $this->statusModel->getAll();
        $this->view('languages.edit', compact('authUser', 'language', 'statuses'));
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            Redirect::to('/languages');
        }

        CSRF::validateOrFail();

        $langId     = (int)$id;
        $language   = $this->langModel->findById($langId);

        if (!$language) {
            Redirect::withError('/languages', __('languages.not_found'));
        }

        $name       = trim($this->input('name', ''));
        $nativeName = trim($this->input('native_name', ''));
        $code       = strtolower(trim($this->input('code', '')));
        $statusId   = (int)$this->input('status_id', 1);

        // No desactivar el idioma por defecto
        if ($language['is_default'] && $statusId != 1) {
            Redirect::withError("/languages/edit/{$langId}", __('languages.cannot_deact_def'));
        }

        $validator = new Validator();
        $validator->required('name', $name, 'Nombre')
                  ->maxLength('name', $name, 100, 'Nombre')
                  ->required('code', $code, 'Código')
                  ->maxLength('code', $code, 10, 'Código')
                  ->required('status_id', $statusId ?: '', 'Estado');

        if ($validator->fails()) {
            Redirect::withErrors("/languages/edit/{$langId}", $validator->errors());
        }

        if ($this->langModel->codeExists($code, $langId)) {
            Redirect::withErrors("/languages/edit/{$langId}",
                ['code' => __('languages.code_unique')]);
        }

        try {
            if ($this->langModel->update($langId, [
                'name'        => $name,
                'native_name' => $nativeName ?: null,
                'code'        => $code,
                'status_id'   => $statusId,
            ])) {
                Logger::security("Idioma ID {$langId} actualizado por admin ID " . Auth::id());
                // Advertir si no existe archivo de traducción
                $langFile = dirname(__DIR__, 2) . '/lang/' . $code . '.php';
                if (!file_exists($langFile)) {
                    \Core\Session::flash('warning', __('languages.warning_no_file', ['code' => $code]));
                }
                Redirect::withSuccess('/languages', __('languages.updated_ok'));
            } else {
                Redirect::withError("/languages/edit/{$langId}", __('alerts.internal'));
            }
        } catch (\PDOException $e) {
            Logger::error('LanguagesController::update PDOException: ' . $e->getMessage());
            Redirect::withError("/languages/edit/{$langId}", __('alerts.internal'));
        }
    }

    public function toggle(string $id): void
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            Redirect::to('/languages');
        }

        CSRF::validateOrFail();

        $langId   = (int)$id;
        $language = $this->langModel->findById($langId);

        if (!$language) {
            Redirect::withError('/languages', __('languages.not_found'));
        }

        if ($language['is_default']) {
            Redirect::withError('/languages', __('languages.cannot_deact_def'));
        }

        $isActive  = ($language['status_slug'] ?? '') === 'active';
        $newStatus = $isActive ? 2 : 1; // 1=active, 2=inactive (según tbl_statuses)

        // Buscar el status_id correcto por slug
        $statuses = $this->statusModel->getAll();
        foreach ($statuses as $s) {
            if (!$isActive && $s['slug'] === 'active')   { $newStatus = $s['id']; break; }
            if ($isActive  && $s['slug'] === 'inactive') { $newStatus = $s['id']; break; }
        }

        try {
            if ($this->langModel->setStatus($langId, $newStatus)) {
                $msg = $isActive ? __('languages.deactivated_ok') : __('languages.activated_ok');
                Logger::security("Idioma ID {$langId} " . ($isActive ? 'desactivado' : 'activado') . " por admin ID " . Auth::id());
                Redirect::withSuccess('/languages', $msg);
            } else {
                Redirect::withError('/languages', __('alerts.internal'));
            }
        } catch (\PDOException $e) {
            Logger::error('LanguagesController::toggle PDOException: ' . $e->getMessage());
            Redirect::withError('/languages', __('alerts.internal'));
        }
    }
}
