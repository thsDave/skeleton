<?php

namespace App\Services;

use App\Models\MfaSettings;
use Core\Logger;

/**
 * SmsService — envía SMS usando el proveedor configurado en tbl_mfa_settings.
 *
 * Estado actual: estructura base lista. Los proveedores concretos (twilio,
 * vonage, custom) necesitan integración real vía Composer o HTTP.
 * Ver README.md → Sección "Seguridad > MFA" para instrucciones.
 */
class SmsService
{
    public static string $lastError = '';

    public static function send(string $to, string $message): bool
    {
        self::$lastError = '';

        try {
            $model    = new MfaSettings();
            $settings = $model->getDecrypted();

            $provider  = (string) ($settings['sms_provider']   ?? '');
            $apiKey    = (string) ($settings['sms_api_key']     ?? '');
            $apiSecret = (string) ($settings['sms_api_secret']  ?? '');
            $from      = (string) ($settings['sms_from']        ?? '');

            if ($provider === '' || $apiKey === '' || $from === '') {
                self::$lastError = 'not_configured';
                return false;
            }

            return match (strtolower($provider)) {
                'twilio'  => self::sendViaTwilio($apiKey, $apiSecret, $from, $to, $message),
                'vonage'  => self::sendViaVonage($apiKey, $apiSecret, $from, $to, $message),
                'custom'  => self::sendViaCustom($settings, $to, $message),
                default   => self::providerNotSupported($provider),
            };

        } catch (\Throwable $e) {
            self::$lastError = 'internal_error';
            Logger::error('SmsService::send unexpected error — ' . $e->getMessage());
            return false;
        }
    }

    public static function isConfigured(): bool
    {
        try {
            $row = (new MfaSettings())->get();
            return (
                !empty($row['sms_provider']) &&
                !empty($row['sms_api_key']) &&
                !empty($row['sms_api_secret_enc']) &&
                !empty($row['sms_from'])
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── Proveedores ─────────────────────────────────────────────────────────────

    private static function sendViaTwilio(
        string $sid, string $token, string $from, string $to, string $body
    ): bool {
        // Para activar: composer require twilio/sdk
        // Implementación de referencia:
        //   $client = new \Twilio\Rest\Client($sid, $token);
        //   $client->messages->create($to, ['from' => $from, 'body' => $body]);
        self::$lastError = 'provider_not_implemented';
        Logger::info("SmsService[twilio]: proveedor no implementado — to={$to}");
        return false;
    }

    private static function sendViaVonage(
        string $apiKey, string $apiSecret, string $from, string $to, string $body
    ): bool {
        // Para activar: composer require vonage/client
        // Implementación de referencia:
        //   $vonage = new \Vonage\Client(new \Vonage\Client\Credentials\Basic($apiKey, $apiSecret));
        //   $vonage->sms()->send(new \Vonage\SMS\Message\SMS($to, $from, $body));
        self::$lastError = 'provider_not_implemented';
        Logger::info("SmsService[vonage]: proveedor no implementado — to={$to}");
        return false;
    }

    private static function sendViaCustom(array $settings, string $to, string $body): bool
    {
        $endpoint = (string) ($settings['sms_endpoint'] ?? '');
        if ($endpoint === '') {
            self::$lastError = 'custom_no_endpoint';
            return false;
        }
        // Implementación HTTP genérica con curl:
        //   $ch = curl_init($endpoint);
        //   curl_setopt_array($ch, [...]);
        //   $response = curl_exec($ch);
        self::$lastError = 'provider_not_implemented';
        Logger::info("SmsService[custom]: proveedor no implementado — to={$to} endpoint={$endpoint}");
        return false;
    }

    private static function providerNotSupported(string $provider): bool
    {
        self::$lastError = 'provider_not_supported';
        Logger::error("SmsService: proveedor desconocido '{$provider}'");
        return false;
    }
}
