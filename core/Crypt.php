<?php

namespace Core;

class Crypt
{
    private const CIPHER  = 'aes-256-cbc';
    private const IV_LEN  = 16;
    private const HMAC_ALGO = 'sha256';

    private static function key(): string
    {
        $raw = (string) env('APP_KEY', '');
        if ($raw === '') {
            throw new \RuntimeException('APP_KEY is not set in .env');
        }
        return hash('sha256', $raw, true);
    }

    public static function encrypt(string $plaintext): string
    {
        $iv         = random_bytes(self::IV_LEN);
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException('Crypt::encrypt failed');
        }
        $payload = $iv . $ciphertext;
        $hmac    = hash_hmac(self::HMAC_ALGO, $payload, self::key(), true);
        return base64_encode($hmac . $payload);
    }

    public static function decrypt(string $encoded): string
    {
        $raw  = base64_decode($encoded, strict: true);
        if ($raw === false) {
            return '';
        }

        $hmacLen    = 32;
        $hmac       = substr($raw, 0, $hmacLen);
        $payload    = substr($raw, $hmacLen);
        $expectedMac = hash_hmac(self::HMAC_ALGO, $payload, self::key(), true);

        if (!hash_equals($expectedMac, $hmac)) {
            return '';
        }

        $iv         = substr($payload, 0, self::IV_LEN);
        $ciphertext = substr($payload, self::IV_LEN);
        $plaintext  = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);

        return $plaintext === false ? '' : $plaintext;
    }
}
