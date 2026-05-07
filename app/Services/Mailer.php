<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use Core\Logger;

class Mailer
{
    /**
     * Envía un correo electrónico usando PHPMailer + SMTP configurado en .env.
     *
     * @param string $to        Dirección de correo del destinatario
     * @param string $toName    Nombre visible del destinatario
     * @param string $subject   Asunto del correo
     * @param string $htmlBody  Cuerpo HTML
     * @param string $plainBody Cuerpo en texto plano (opcional)
     * @return bool             true si se envió, false si falló
     */
    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = ''
    ): bool {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST', 'smtp.example.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME', '');
            $mail->Password   = env('MAIL_PASSWORD', '');
            $mail->Port       = (int) env('MAIL_PORT', 587);
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPDebug  = SMTP::DEBUG_OFF;

            $encryption = strtolower((string) env('MAIL_ENCRYPTION', 'tls'));
            $mail->SMTPSecure = match ($encryption) {
                'ssl'  => PHPMailer::ENCRYPTION_SMTPS,
                'tls'  => PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };

            if ($mail->SMTPSecure === '') {
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom(
                (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
                (string) env('MAIL_FROM_NAME', 'Skeleton')
            );

            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject  = $subject;
            $mail->Body     = $htmlBody;

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
}
