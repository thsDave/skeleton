<?php

namespace App\Services;

use League\OAuth2\Client\Provider\GenericProvider;
use Core\Logger;

class ExternalAuthService
{
    private const ID_FIELDS = [
        'google'    => 'sub',
        'microsoft' => 'id',
        'github'    => 'id',
    ];

    public function buildProvider(array $providerRow, string $plainSecret): GenericProvider
    {
        $authUrl  = $providerRow['authorization_url'] ?? '';
        $tokenUrl = $providerRow['token_url']         ?? '';

        if (!empty($providerRow['tenant_id'])) {
            $authUrl  = str_replace('{tenant_id}', $providerRow['tenant_id'], $authUrl);
            $tokenUrl = str_replace('{tenant_id}', $providerRow['tenant_id'], $tokenUrl);
        }

        return new GenericProvider([
            'clientId'                => $providerRow['client_id'],
            'clientSecret'            => $plainSecret,
            'redirectUri'             => $providerRow['redirect_uri'],
            'urlAuthorize'            => $authUrl,
            'urlAccessToken'          => $tokenUrl,
            'urlResourceOwnerDetails' => $providerRow['userinfo_url'] ?? '',
            'responseResourceOwnerId' => self::ID_FIELDS[$providerRow['slug']] ?? 'id',
        ]);
    }

    public function fetchUserInfo(string $slug, string $accessToken, string $userinfoUrl, ?string $tenantId = null): array
    {
        $url = $userinfoUrl;
        if ($tenantId) {
            $url = str_replace('{tenant_id}', $tenantId, $url);
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
        ];

        if ($slug === 'github') {
            $headers[] = 'User-Agent: Skeleton-MVC-OAuth/1.0';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            Logger::error("ExternalAuthService::fetchUserInfo cURL error [{$slug}]: {$error}");
            return [];
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : [];
    }

    public function extractEmail(string $slug, array $userInfo, string $accessToken): string
    {
        if ($slug === 'github') {
            $email = $userInfo['email'] ?? '';
            if ($email === '' || $email === null) {
                $email = $this->fetchGitHubPrimaryEmail($accessToken);
            }
            return (string) $email;
        }

        if ($slug === 'microsoft') {
            return (string) ($userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? '');
        }

        return (string) ($userInfo['email'] ?? '');
    }

    public function extractUserId(string $slug, array $userInfo): string
    {
        $field = self::ID_FIELDS[$slug] ?? 'id';
        return (string) ($userInfo[$field] ?? '');
    }

    public function extractName(string $slug, array $userInfo): string
    {
        if ($slug === 'microsoft') {
            return (string) ($userInfo['displayName'] ?? '');
        }
        if ($slug === 'github') {
            return (string) ($userInfo['name'] ?? $userInfo['login'] ?? '');
        }
        return (string) ($userInfo['name'] ?? '');
    }

    public function extractAvatar(string $slug, array $userInfo): ?string
    {
        return match ($slug) {
            'google' => $userInfo['picture']    ?? null,
            'github' => $userInfo['avatar_url'] ?? null,
            default  => null,
        };
    }

    public function generateRedirectUri(string $baseUrl, string $slug): string
    {
        return rtrim($baseUrl, '/') . '/auth/external/' . $slug . '/callback';
    }

    private function fetchGitHubPrimaryEmail(string $accessToken): string
    {
        $ch = curl_init('https://api.github.com/user/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/vnd.github+json',
                'User-Agent: Skeleton-MVC-OAuth/1.0',
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return '';
        }

        $emails = json_decode($response, true);
        if (!is_array($emails)) {
            return '';
        }

        foreach ($emails as $entry) {
            if (!empty($entry['primary']) && !empty($entry['verified'])) {
                return (string) ($entry['email'] ?? '');
            }
        }

        return '';
    }
}
