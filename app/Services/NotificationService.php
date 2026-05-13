<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    private Notification $notifications;

    public function __construct(?Notification $notifications = null)
    {
        $this->notifications = $notifications ?? new Notification();
    }

    public function notifyPasswordChanged(int $userId): bool
    {
        return $this->notifications->createForUser(
            $userId,
            'password_changed',
            __('notifications.password_changed_title'),
            __('notifications.password_changed_message'),
            '/account',
            'warning',
            'ph-key'
        );
    }

    public function notifyEmailUpdated(int $userId): bool
    {
        return $this->notifications->createForUser(
            $userId,
            'email_updated',
            __('notifications.email_updated_title'),
            __('notifications.email_updated_message'),
            '/account',
            'success',
            'ph-envelope-simple'
        );
    }

    public function notifyMfaEnabled(int $userId): bool
    {
        return $this->notifications->createForUser(
            $userId,
            'mfa_enabled',
            __('notifications.mfa_enabled_title'),
            __('notifications.mfa_enabled_message'),
            '/profile/two-factor',
            'success',
            'ph-shield-check'
        );
    }

    public function notifyExternalAccountLinked(int $userId, string $providerName): bool
    {
        return $this->notifications->createForUser(
            $userId,
            'external_account_linked',
            __('notifications.external_account_linked_title'),
            __('notifications.external_account_linked_message', ['provider' => $providerName]),
            '/account',
            'info',
            'ph-link'
        );
    }

    public function notifyManualUploaded(string $manualName, int $uploadedByUserId): int
    {
        $created = 0;
        foreach ($this->notifications->getActiveUserIds() as $userId) {
            $ok = $this->notifications->createForUser(
                $userId,
                'manual_uploaded',
                __('notifications.manual_uploaded_title'),
                __('notifications.manual_uploaded_message', ['manual' => $manualName]),
                '/system-information',
                'info',
                'ph-book-open'
            );
            $created += $ok ? 1 : 0;
        }

        return $created;
    }
}
