<?php

namespace App\Models;

use Core\Logger;
use Core\Model;
use PDO;
use Throwable;

class Notification extends Model
{
    private const TABLE = 'tbl_notifications';
    private const SEVERITIES = ['info', 'success', 'warning', 'danger'];

    public function createForUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $url = null,
        string $severity = 'info',
        ?string $icon = null
    ): bool {
        try {
            if ($userId <= 0 || !$this->tableExists()) {
                return false;
            }

            $severity = in_array($severity, self::SEVERITIES, true) ? $severity : 'info';
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                 (user_id, type, title, message, url, icon, severity, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );

            return $stmt->execute([
                $userId,
                mb_substr(trim($type), 0, 100),
                mb_substr(trim($title), 0, 150),
                trim($message),
                $this->sanitizeInternalUrl($url),
                $icon ? mb_substr(trim($icon), 0, 100) : null,
                $severity,
            ]);
        } catch (Throwable $e) {
            Logger::error('Notification::createForUser: ' . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount(int $userId): int
    {
        try {
            if ($userId <= 0 || !$this->tableExists()) {
                return 0;
            }

            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM ' . self::TABLE . '
                 WHERE user_id = ? AND read_at IS NULL' . $this->activeWhereSql()
            );
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    public function getReadCount(int $userId): int
    {
        try {
            if ($userId <= 0 || !$this->tableExists()) {
                return 0;
            }

            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM ' . self::TABLE . '
                 WHERE user_id = ? AND read_at IS NOT NULL' . $this->activeWhereSql()
            );
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    public function getRecentForUser(int $userId, int $limit = 5): array
    {
        try {
            if ($userId <= 0 || !$this->tableExists()) {
                return [];
            }

            $limit = max(1, min(10, $limit));
            $stmt = $this->db->prepare(
                'SELECT * FROM ' . self::TABLE . '
                 WHERE user_id = ?' . $this->activeWhereSql() . '
                 ORDER BY created_at DESC, id DESC
                 LIMIT ' . $limit
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    public function getAllForUser(int $userId, array $filters = []): array
    {
        try {
            if ($userId <= 0 || !$this->tableExists()) {
                return [];
            }

            $where = ['user_id = ?'];
            if ($this->deletedAtColumnExists()) {
                $where[] = 'deleted_at IS NULL';
            }
            $params = [$userId];
            $status = (string)($filters['status'] ?? 'all');

            if ($status === 'unread') {
                $where[] = 'read_at IS NULL';
            } elseif ($status === 'read') {
                $where[] = 'read_at IS NOT NULL';
            }

            $stmt = $this->db->prepare(
                'SELECT * FROM ' . self::TABLE . '
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY created_at DESC, id DESC
                 LIMIT 200'
            );
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            Logger::error('Notification::getAllForUser: ' . $e->getMessage());
            return [];
        }
    }

    public function findForUser(int $notificationId, int $userId): array|false
    {
        return $this->findForUserInternal($notificationId, $userId, false);
    }

    public function findForUserIncludingDeleted(int $notificationId, int $userId): array|false
    {
        return $this->findForUserInternal($notificationId, $userId, true);
    }

    private function findForUserInternal(int $notificationId, int $userId, bool $includeDeleted): array|false
    {
        try {
            if ($notificationId <= 0 || $userId <= 0 || !$this->tableExists()) {
                return false;
            }

            $stmt = $this->db->prepare(
                'SELECT * FROM ' . self::TABLE . '
                 WHERE id = ? AND user_id = ?' . ($includeDeleted ? '' : $this->activeWhereSql()) . '
                 LIMIT 1'
            );
            $stmt->execute([$notificationId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return false;
        }
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            if ($notificationId <= 0 || $userId <= 0 || !$this->tableExists()) {
                return false;
            }

            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET read_at = COALESCE(read_at, NOW())
                 WHERE id = ? AND user_id = ?' . $this->activeWhereSql()
            );
            return $stmt->execute([$notificationId, $userId]);
        } catch (Throwable $e) {
            Logger::error('Notification::markAsRead: ' . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead(int $userId): int
    {
        try {
            if ($userId <= 0 || !$this->tableExists()) {
                return 0;
            }

            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET read_at = NOW()
                 WHERE user_id = ? AND read_at IS NULL' . $this->activeWhereSql()
            );
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            Logger::error('Notification::markAllAsRead: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteOldReadNotifications(int $days): int
    {
        try {
            if (!$this->tableExists()) {
                return 0;
            }

            $days = max(1, min(3650, $days));
            $where = $this->deletedAtColumnExists()
                ? 'deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
                : 'read_at IS NOT NULL AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)';
            $stmt = $this->db->prepare(
                'DELETE FROM ' . self::TABLE . '
                 WHERE ' . $where
            );
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            Logger::error('Notification::deleteOldReadNotifications: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteForUser(int $notificationId, int $userId): int
    {
        try {
            if ($notificationId <= 0 || $userId <= 0 || !$this->tableExists() || !$this->deletedAtColumnExists()) {
                return 0;
            }

            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET deleted_at = NOW()
                 WHERE id = ? AND user_id = ? AND deleted_at IS NULL'
            );
            $stmt->execute([$notificationId, $userId]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            Logger::error('Notification::deleteForUser: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteReadForUser(int $userId): int
    {
        try {
            if ($userId <= 0 || !$this->tableExists() || !$this->deletedAtColumnExists()) {
                return 0;
            }

            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET deleted_at = NOW()
                 WHERE user_id = ? AND read_at IS NOT NULL AND deleted_at IS NULL'
            );
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            Logger::error('Notification::deleteReadForUser: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteAllForUser(int $userId): int
    {
        try {
            if ($userId <= 0 || !$this->tableExists() || !$this->deletedAtColumnExists()) {
                return 0;
            }

            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET deleted_at = NOW()
                 WHERE user_id = ? AND deleted_at IS NULL'
            );
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            Logger::error('Notification::deleteAllForUser: ' . $e->getMessage());
            return 0;
        }
    }

    public function getDeletedCount(int $userId): int
    {
        try {
            if ($userId <= 0 || !$this->tableExists() || !$this->deletedAtColumnExists()) {
                return 0;
            }

            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM ' . self::TABLE . '
                 WHERE user_id = ? AND deleted_at IS NOT NULL'
            );
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    public function getActiveUserIds(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT u.id
                 FROM tbl_users u
                 LEFT JOIN tbl_statuses s ON s.id = u.status_id
                 WHERE COALESCE(s.slug, '') = 'active'"
            );
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            Logger::error('Notification::getActiveUserIds: ' . $e->getMessage());
            return [];
        }
    }

    private function sanitizeInternalUrl(?string $url): ?string
    {
        $url = trim((string)$url);
        if ($url === '' || str_starts_with($url, '//')) {
            return null;
        }

        if (!str_starts_with($url, '/')) {
            return null;
        }

        return mb_substr($url, 0, 255);
    }

    private function tableExists(): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $stmt->execute([self::TABLE]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function activeWhereSql(): string
    {
        return $this->deletedAtColumnExists() ? ' AND deleted_at IS NULL' : '';
    }

    private function deletedAtColumnExists(): bool
    {
        return $this->columnExists('deleted_at');
    }

    private function columnExists(string $column): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([self::TABLE, $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
