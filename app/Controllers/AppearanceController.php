<?php

namespace App\Controllers;

use Core\Audit;
use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Validator;
use Core\Logger;
use App\Models\AppearanceSetting;
use App\Services\UploadService;

class AppearanceController extends Controller
{
    private AppearanceSetting $model;

    public function __construct()
    {
        $this->model = new AppearanceSetting();
    }

    public function index(): void
    {
        Auth::requirePermission('appearance.view');
        $authUser   = Auth::user();
        $appearance = $this->model->get();
        $this->view('appearance.index', compact('authUser', 'appearance'));
    }

    public function update(): void
    {
        Auth::requirePermission('appearance.edit');

        if (!$this->isPost()) {
            Redirect::to('/appearance');
        }

        CSRF::validateOrFail();

        $displayName    = trim($this->input('app_display_name', ''));
        $tagline        = trim($this->input('app_tagline', ''));
        $primaryColor   = trim($this->input('primary_color', ''));
        $sidebarColor   = trim($this->input('sidebar_color', ''));
        $overlayColor   = trim($this->input('login_overlay_color', ''));
        $overlayOpacity = trim($this->input('login_overlay_opacity', ''));

        $validator = new Validator();
        $validator->maxLength('app_display_name', $displayName, 150, __('appearance.app_display_name'))
                  ->maxLength('app_tagline', $tagline, 255, __('appearance.app_tagline'));

        $extraErrors = [];
        foreach (['primary_color' => $primaryColor, 'sidebar_color' => $sidebarColor, 'login_overlay_color' => $overlayColor] as $k => $v) {
            if ($v !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                $extraErrors[$k] = __('appearance.color_invalid');
            }
        }
        if ($overlayOpacity !== '') {
            $op = (float)$overlayOpacity;
            if ($op < 0 || $op > 1) {
                $extraErrors['login_overlay_opacity'] = __('appearance.opacity_invalid');
            }
        }

        if ($validator->fails() || !empty($extraErrors)) {
            Redirect::withErrors('/appearance', array_merge($validator->errors(), $extraErrors));
        }

        $current   = $this->model->get();
        $uploadSvc = new UploadService();
        $uploaded  = [];

        $logoPath  = $current['logo_path'] ?? null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $r = $uploadSvc->upload($_FILES['logo'], 'appearance_logo', ['prefix' => 'logo_']);
            if (!$r['success']) {
                Redirect::withErrors('/appearance', ['logo' => $r['error']]);
            }
            if ($logoPath) {
                $uploadSvc->delete($logoPath, 'appearance_logo');
            }
            $logoPath   = $r['filename'];
            $uploaded[] = 'logo';
        }

        $faviconPath = $current['favicon_path'] ?? null;
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
            $r = $uploadSvc->upload($_FILES['favicon'], 'appearance_favicon', ['prefix' => 'favicon_']);
            if (!$r['success']) {
                Redirect::withErrors('/appearance', ['favicon' => $r['error']]);
            }
            if ($faviconPath) {
                $uploadSvc->delete($faviconPath, 'appearance_favicon');
            }
            $faviconPath = $r['filename'];
            $uploaded[]  = 'favicon';
        }

        $loginBgPath = $current['login_background_path'] ?? null;
        if (isset($_FILES['login_background']) && $_FILES['login_background']['error'] !== UPLOAD_ERR_NO_FILE) {
            $r = $uploadSvc->upload($_FILES['login_background'], 'appearance_login_background', ['prefix' => 'login_bg_']);
            if (!$r['success']) {
                Redirect::withErrors('/appearance', ['login_background' => $r['error']]);
            }
            if ($loginBgPath) {
                $uploadSvc->delete($loginBgPath, 'appearance_login_background');
            }
            $loginBgPath = $r['filename'];
            $uploaded[]  = 'login_background';
        }

        $opacity = $overlayOpacity !== '' ? (float)$overlayOpacity : ($current['login_overlay_opacity'] ?? 0.40);

        try {
            $saved = $this->model->createOrUpdate([
                'app_display_name'      => $displayName ?: null,
                'app_tagline'           => $tagline ?: null,
                'logo_path'             => $logoPath,
                'favicon_path'          => $faviconPath,
                'login_background_path' => $loginBgPath,
                'primary_color'         => $primaryColor ?: null,
                'sidebar_color'         => $sidebarColor ?: null,
                'login_overlay_color'   => $overlayColor ?: null,
                'login_overlay_opacity' => $opacity,
            ]);

            if ($saved) {
                Logger::info('Apariencia actualizada por admin ID ' . Auth::id());
                Audit::log([
                    'module'      => 'appearance',
                    'action'      => 'appearance.updated',
                    'entity'      => 'appearance_settings',
                    'description' => 'Configuración de apariencia actualizada',
                    'new_values'  => ['files_updated' => $uploaded],
                    'status'      => 'success',
                ]);
                Redirect::withSuccess('/appearance', __('appearance.updated'));
            } else {
                Redirect::withError('/appearance', __('appearance.update_error'));
            }
        } catch (\PDOException $e) {
            Logger::error('AppearanceController::update PDOException: ' . $e->getMessage());
            Redirect::withError('/appearance', __('alerts.internal'));
        }
    }

    public function resetLogo(): void
    {
        $this->doReset('logo_path', 'appearance_logo', 'appearance.logo_reset');
    }

    public function resetFavicon(): void
    {
        $this->doReset('favicon_path', 'appearance_favicon', 'appearance.favicon_reset');
    }

    public function resetLoginBackground(): void
    {
        $this->doReset('login_background_path', 'appearance_login_background', 'appearance.login_background_reset');
    }

    public function resetColors(): void
    {
        Auth::requirePermission('appearance.reset');

        if (!$this->isPost()) {
            Redirect::to('/appearance');
        }
        CSRF::validateOrFail();

        $current = $this->model->get();

        try {
            $this->model->createOrUpdate(array_merge($current, [
                'primary_color'       => null,
                'sidebar_color'       => null,
                'login_overlay_color' => null,
            ]));
            Logger::info('Colores restablecidos por admin ID ' . Auth::id());
            Audit::log([
                'module'      => 'appearance',
                'action'      => 'appearance.colors_reset',
                'entity'      => 'appearance_settings',
                'description' => 'Colores del sistema restablecidos',
                'status'      => 'success',
            ]);
            Redirect::withSuccess('/appearance', __('appearance.reset_success'));
        } catch (\PDOException $e) {
            Logger::error('AppearanceController::resetColors PDOException: ' . $e->getMessage());
            Redirect::withError('/appearance', __('alerts.internal'));
        }
    }

    private function doReset(string $field, string $profile, string $auditAction): void
    {
        Auth::requirePermission('appearance.reset');

        if (!$this->isPost()) {
            Redirect::to('/appearance');
        }
        CSRF::validateOrFail();

        $current = $this->model->get();
        $path    = $current[$field] ?? null;

        try {
            if ($path) {
                (new UploadService())->delete($path, $profile);
            }
            $this->model->createOrUpdate(array_merge($current, [$field => null]));
            Logger::info("Apariencia campo {$field} restablecido por admin ID " . Auth::id());
            Audit::log([
                'module'      => 'appearance',
                'action'      => $auditAction,
                'entity'      => 'appearance_settings',
                'description' => "Campo {$field} de apariencia restablecido",
                'status'      => 'success',
            ]);
            Redirect::withSuccess('/appearance', __('appearance.reset_success'));
        } catch (\PDOException $e) {
            Logger::error("AppearanceController::doReset {$field} PDOException: " . $e->getMessage());
            Redirect::withError('/appearance', __('alerts.internal'));
        }
    }
}
