<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    private Google2FA $g2fa;

    public function __construct()
    {
        $this->g2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->g2fa->generateSecretKey();
    }

    public function generateQrSvg(string $email, string $secret, string $appName = 'Skeleton'): string
    {
        $url      = $this->g2fa->getQRCodeUrl($appName, $email, $secret);
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        return (new Writer($renderer))->writeString($url);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        return (bool) $this->g2fa->verifyKey($secret, $code, 2);
    }

    public function generateNumericCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }

    public function verifyCode(string $code, string $hash): bool
    {
        return hash_equals($hash, hash('sha256', $code));
    }
}
