<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use App\Models\SmtpSettings;
use Core\Logger;

class Mailer
{
    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = ''
    ): bool {
        $cfg  = self::config();
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
            Logger::error('Mailer::send failed — ' . $mail->ErrorInfo);
            return false;
        }
    }

    private static function config(): array
    {
        try {
            $model    = new SmtpSettings();
            $settings = $model->getDecrypted();

            if ($settings['host'] !== '' && $settings['username'] !== '') {
                return [
                    'host'         => $settings['host'],
                    'port'         => (int) $settings['port'],
                    'username'     => $settings['username'],
                    'password'     => $settings['password'],
                    'encryption'   => $settings['encryption'] === 'none' ? '' : $settings['encryption'],
                    'from_address' => $settings['from_address'] ?: (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
                    'from_name'    => $settings['from_name']    ?: (string) env('MAIL_FROM_NAME', 'Skeleton'),
                ];
            }
        } catch (\Throwable) {
            // DB unavailable — fall through to .env
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
}
