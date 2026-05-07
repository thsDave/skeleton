<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Session;
use Core\CSRF;
use Core\Redirect;
use Core\Logger;
use App\Models\SmtpSettings;
use App\Services\Mailer;

class SmtpSettingsController
{
    public function index(): void
    {
        Auth::requirePermission('security_smtp.view');

        $model    = new SmtpSettings();
        $settings = $model->getDecrypted();

        require dirname(__DIR__) . '/Views/security/smtp/index.php';
    }

    public function update(): void
    {
        Auth::requirePermission('security_smtp.edit');
        CSRF::validateOrFail();

        $host        = trim($_POST['host']        ?? '');
        $port        = (int) ($_POST['port']       ?? 587);
        $username    = trim($_POST['username']     ?? '');
        $password    = $_POST['password']          ?? '';
        $encryption  = $_POST['encryption']        ?? 'tls';
        $fromAddress = trim($_POST['from_address'] ?? '');
        $fromName    = trim($_POST['from_name']    ?? '');

        $errors = [];
        if ($host === '')        $errors[] = __('smtp.validation.host_required');
        if ($port < 1 || $port > 65535) $errors[] = __('smtp.validation.port_invalid');
        if ($username === '')    $errors[] = __('smtp.validation.username_required');
        if ($fromAddress === '') $errors[] = __('smtp.validation.from_address_required');
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) $errors[] = __('smtp.validation.from_address_invalid');
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) $encryption = 'tls';

        if ($errors) {
            Redirect::withErrors('/security/smtp', $errors, $_POST);
            exit;
        }

        $model = new SmtpSettings();
        $saved = $model->update([
            'host'         => $host,
            'port'         => $port,
            'username'     => $username,
            'password'     => $password,
            'encryption'   => $encryption,
            'from_address' => $fromAddress,
            'from_name'    => $fromName,
        ]);

        if ($saved) {
            Audit::log([
                'module'      => 'security_smtp',
                'action'      => 'settings_updated',
                'description' => 'Configuración SMTP actualizada',
                'new_values'  => ['host' => $host, 'port' => $port, 'username' => $username, 'encryption' => $encryption, 'from_address' => $fromAddress],
                'status'      => 'success',
            ]);
            Session::flash('success', __('smtp.updated'));
        } else {
            Session::flash('error', __('alerts.internal'));
        }

        Redirect::to('/security/smtp');
        exit;
    }

    public function test(): void
    {
        Auth::requirePermission('security_smtp.test');
        CSRF::validateOrFail();

        $model    = new SmtpSettings();
        $settings = $model->getDecrypted();

        if ($settings['host'] === '' || $settings['username'] === '') {
            Session::flash('error', __('smtp.test.not_configured'));
            Redirect::to('/security/smtp');
            exit;
        }

        $user    = Auth::user();
        $to      = $user['email'] ?? $settings['from_address'];
        $toName  = trim(($user['first_names'] ?? '') . ' ' . ($user['last_names'] ?? '')) ?: 'Admin';
        $subject = __('smtp.test.email_subject');
        $html    = '<p style="font-family:sans-serif;">' . htmlspecialchars(__('smtp.test.email_body')) . '</p>';

        $sent = Mailer::send($to, $toName, $subject, $html);

        $model->markTested($sent, $sent ? __('smtp.test.status_ok') : __('smtp.test.status_fail'));

        Audit::log([
            'module'      => 'security_smtp',
            'action'      => 'smtp_tested',
            'description' => $sent ? 'Prueba SMTP exitosa' : 'Prueba SMTP fallida',
            'status'      => $sent ? 'success' : 'error',
        ]);

        if ($sent) {
            Session::flash('success', __('smtp.test.sent_ok', [':email' => $to]));
        } else {
            Session::flash('error', __('smtp.test.sent_fail'));
        }

        Redirect::to('/security/smtp');
        exit;
    }
}
