<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Core\Audit;
use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\Redirect;

class NotificationsController extends Controller
{
    private Notification $notifications;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notifications = new Notification();
        $this->notificationService = new NotificationService($this->notifications);
    }

    public function index(): void
    {
        Auth::requirePermission('notifications.view');

        $authUser = Auth::user();
        $status = $this->normalizeStatus($_GET['status'] ?? 'all');
        $notifications = $this->notifications->getAllForUser((int)Auth::id(), ['status' => $status]);
        $unreadCount = $this->notifications->getUnreadCount((int)Auth::id());
        $readCount = $this->notifications->getReadCount((int)Auth::id());

        Audit::log([
            'module' => 'notifications',
            'action' => 'notifications.viewed',
            'description' => 'Consulta de notificaciones internas',
            'status' => 'info',
        ]);

        $this->view('notifications.index', compact('authUser', 'notifications', 'status', 'unreadCount', 'readCount'));
    }

    public function read(string $id): void
    {
        Auth::requirePermission('notifications.mark_read');

        $notificationId = (int)$id;
        $notification = $this->notifications->findForUser($notificationId, (int)Auth::id());
        if (!$notification) {
            Redirect::withError('/notifications', __('notifications.invalid_notification'));
        }

        $wasUnread = empty($notification['read_at']);
        $this->notifications->markAsRead($notificationId, (int)Auth::id());
        if ($wasUnread) {
            Audit::log([
                'module' => 'notifications',
                'action' => 'notifications.marked_read',
                'entity' => 'notification',
                'entity_id' => $notificationId,
                'description' => 'Notificacion interna abierta y marcada como leida',
                'status' => 'success',
            ]);
        }
        $target = $this->safeRedirectPath($notification['url'] ?? null);
        Redirect::to($target ?: '/notifications');
    }

    public function markRead(string $id): void
    {
        Auth::requirePermission('notifications.mark_read');
        CSRF::validateOrFail();

        $notificationId = (int)$id;
        $notification = $this->notifications->findForUser($notificationId, (int)Auth::id());
        if (!$notification) {
            Redirect::withError('/notifications', __('notifications.invalid_notification'));
        }

        $this->notifications->markAsRead($notificationId, (int)Auth::id());
        Audit::log([
            'module' => 'notifications',
            'action' => 'notifications.marked_read',
            'entity' => 'notification',
            'entity_id' => $notificationId,
            'description' => 'Notificacion interna marcada como leida',
            'status' => 'success',
        ]);

        Redirect::withSuccess('/notifications', __('notifications.marked_read_success'));
    }

    public function markAllRead(): void
    {
        Auth::requirePermission('notifications.mark_read');
        CSRF::validateOrFail();

        $count = $this->notifications->markAllAsRead((int)Auth::id());
        Audit::log([
            'module' => 'notifications',
            'action' => 'notifications.marked_all_read',
            'description' => 'Todas las notificaciones internas fueron marcadas como leidas',
            'new_values' => ['records_count' => $count],
            'status' => 'success',
        ]);

        Redirect::withSuccess('/notifications', __('notifications.marked_all_read_success'));
    }

    public function delete(string $id): void
    {
        Auth::requirePermission('notifications.view');
        CSRF::validateOrFail();

        $notificationId = (int)$id;
        $userId = (int)Auth::id();
        $notification = $this->notifications->findForUserIncludingDeleted($notificationId, $userId);
        if (!$notification) {
            Audit::log([
                'module' => 'notifications',
                'action' => 'notifications.deleted',
                'entity' => 'notification',
                'entity_id' => $notificationId,
                'description' => 'Intento de eliminar una notificacion no disponible para el usuario autenticado',
                'status' => 'denied',
            ]);
            Redirect::withError('/notifications', __('notifications.only_own_notifications'));
        }

        $count = empty($notification['deleted_at'])
            ? $this->notificationService->deleteNotificationForUser($notificationId, $userId)
            : 0;

        Audit::log([
            'module' => 'notifications',
            'action' => 'notifications.deleted',
            'entity' => 'notification',
            'entity_id' => $notificationId,
            'description' => 'Notificacion interna eliminada de la lista del usuario',
            'new_values' => ['records_count' => $count],
            'status' => 'success',
        ]);

        Redirect::withSuccess('/notifications', $count > 0 ? __('notifications.deleted_success') : __('notifications.no_notifications_to_delete'));
    }

    public function deleteRead(): void
    {
        Auth::requirePermission('notifications.view');
        CSRF::validateOrFail();

        $count = $this->notificationService->deleteReadForUser((int)Auth::id());
        Audit::log([
            'module' => 'notifications',
            'action' => 'notifications.deleted_read',
            'description' => 'Notificaciones internas leidas eliminadas de la lista del usuario',
            'new_values' => ['records_count' => $count],
            'status' => 'success',
        ]);

        Redirect::withSuccess('/notifications', $count > 0 ? __('notifications.deleted_read_success') : __('notifications.no_notifications_to_delete'));
    }

    public function deleteAll(): void
    {
        Auth::requirePermission('notifications.view');
        CSRF::validateOrFail();

        $count = $this->notificationService->deleteAllForUser((int)Auth::id());
        Audit::log([
            'module' => 'notifications',
            'action' => 'notifications.deleted_all',
            'description' => 'Todas las notificaciones internas fueron eliminadas de la lista del usuario',
            'new_values' => ['records_count' => $count],
            'status' => 'success',
        ]);

        Redirect::withSuccess('/notifications', $count > 0 ? __('notifications.deleted_all_success') : __('notifications.no_notifications_to_delete'));
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = (string)$status;
        return in_array($status, ['all', 'unread', 'read'], true) ? $status : 'all';
    }

    private function safeRedirectPath(?string $url): ?string
    {
        $url = trim((string)$url);
        if ($url === '' || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        return $url;
    }
}
