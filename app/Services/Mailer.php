<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use App\Models\SmtpSettings;
use Core\Logger;

class Mailer
{
    public static string $lastError = '';

    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = ''
    ): bool {
        self::$lastError = '';

        try {
            $cfg = self::config();
        } catch (\Throwable $e) {
            self::$lastError = 'config_error: ' . $e->getMessage();
            Logger::error('Mailer::config() error — ' . $e->getMessage());
            return false;
        }

        if ($cfg['host'] === '' || $cfg['username'] === '') {
            self::$lastError = 'not_configured';
            return false;
        }

        if ($cfg['password'] === '') {
            self::$lastError = 'password_empty';
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host      = $cfg['host'];
            $mail->SMTPAuth  = true;
            $mail->Username  = $cfg['username'];
            $mail->Password  = $cfg['password'];
            $mail->Port      = $cfg['port'];
            $mail->CharSet   = 'UTF-8';
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->Timeout   = 15;

            $mail->SMTPSecure = match ($cfg['encryption']) {
                'ssl'   => PHPMailer::ENCRYPTION_SMTPS,
                'tls'   => PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };

            if ($mail->SMTPSecure === '') {
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($cfg['from_address'], $cfg['from_name']);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            if ($plainBody !== '') {
                $mail->AltBody = $plainBody;
            }

            $mail->send();
            return true;

        } catch (MailerException $e) {
            $errorInfo = $mail->ErrorInfo ?: $e->getMessage();
            self::$lastError = self::categorizeError($errorInfo);
            Logger::error(sprintf(
                'Mailer::send failed [host=%s port=%d enc=%s from=%s] — %s',
                $cfg['host'],
                $cfg['port'],
                $cfg['encryption'],
                $cfg['from_address'],
                $errorInfo
            ));
            return false;
        } catch (\Throwable $e) {
            self::$lastError = 'internal_error';
            Logger::error('Mailer::send unexpected error — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns SMTP configuration: DB record takes priority over .env.
     * Falls back to .env if the DB record has no host or username configured.
     */
    public static function config(): array
    {
        try {
            $model    = new SmtpSettings();
            $settings = $model->getDecrypted();

            if ($settings['host'] !== '' && $settings['username'] !== '') {
                $enc = $settings['encryption'] ?? 'tls';
                return [
                    'host'         => trim($settings['host']),
                    'port'         => (int) $settings['port'],
                    'username'     => trim($settings['username']),
                    'password'     => $settings['password'],
                    'encryption'   => $enc === 'none' ? '' : $enc,
                    'from_address' => $settings['from_address'] !== ''
                        ? $settings['from_address']
                        : (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
                    'from_name'    => $settings['from_name'] !== ''
                        ? $settings['from_name']
                        : (string) env('MAIL_FROM_NAME', 'Skeleton'),
                ];
            }
        } catch (\Throwable $e) {
            Logger::error('Mailer::config() DB error — ' . $e->getMessage());
        }

        $encryption = strtolower((string) env('MAIL_ENCRYPTION', 'tls'));
        return [
            'host'         => (string) env('MAIL_HOST', 'smtp.example.com'),
            'port'         => (int)    env('MAIL_PORT', 587),
            'username'     => (string) env('MAIL_USERNAME', ''),
            'password'     => (string) env('MAIL_PASSWORD', ''),
            'encryption'   => in_array($encryption, ['tls', 'ssl'], true) ? $encryption : '',
            'from_address' => (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
            'from_name'    => (string) env('MAIL_FROM_NAME', 'Skeleton'),
        ];
    }

    /**
     * Returns a short category string for the PHPMailer error so the
     * controller can display a targeted user-friendly message.
     */
    private static function categorizeError(string $errorInfo): string
    {
        $lower = strtolower($errorInfo);
        if (str_contains($lower, 'authenticate') || str_contains($lower, 'username') || str_contains($lower, 'password')) {
            return 'auth_failed';
        }
        if (str_contains($lower, 'could not connect') || str_contains($lower, 'connection') || str_contains($lower, 'timeout') || str_contains($lower, 'timed out')) {
            return 'connect_failed';
        }
        if (str_contains($lower, 'invalid address') || str_contains($lower, 'from address') || str_contains($lower, 'invalid email')) {
            return 'invalid_address';
        }
        return 'smtp_error';
    }
}
