<?php

namespace App\Models;

use Core\Model;

class AuditLog extends Model
{
    private const TABLE = 'tbl_audit_logs';
    private const LIMIT = 2000;

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO " . self::TABLE . "
                (user_id, module, action, entity, entity_id, description,
                 old_values, new_values, ip_address, user_agent, route, method, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id']     ?? null,
            $data['module']      ?? null,
            $data['action']      ?? 'unknown',
            $data['entity']      ?? null,
            $data['entity_id']   ?? null,
            $data['description'] ?? null,
            $data['old_values']  ?? null,
            $data['new_values']  ?? null,
            $data['ip_address']  ?? null,
            $data['user_agent']  ?? null,
            $data['route']       ?? null,
            $data['method']      ?? null,
            $data['status']      ?? 'success',
        ]);
    }

    /**
     * Returns filtered audit logs (most recent first), capped at LIMIT rows.
     */
    public function getAll(array $filters = []): array
    {
        $sql    = "
            SELECT a.*,
                   CONCAT(u.nombres, ' ', u.apellidos) AS user_name,
                   u.email AS user_email
            FROM " . self::TABLE . " a
            LEFT JOIN tbl_users u ON u.id = a.user_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql    .= ' AND a.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['module'])) {
            $sql    .= ' AND a.module = ?';
            $params[] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $sql    .= ' AND a.action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['status'])) {
            $sql    .= ' AND a.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql    .= ' AND DATE(a.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql    .= ' AND DATE(a.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT ' . self::LIMIT;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT a.*,
                   CONCAT(u.nombres, ' ', u.apellidos) AS user_name,
                   u.email AS user_email
            FROM " . self::TABLE . " a
            LEFT JOIN tbl_users u ON u.id = a.user_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getDistinctModules(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT module FROM " . self::TABLE
            . " WHERE module IS NOT NULL ORDER BY module"
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getDistinctActions(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT action FROM " . self::TABLE . " ORDER BY action"
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function countAll(array $filters = []): int
    {
        $sql    = "SELECT COUNT(*) FROM " . self::TABLE . " WHERE 1=1";
        $params = [];
        if (!empty($filters['user_id']))   { $sql .= ' AND user_id = ?';  $params[] = (int)$filters['user_id']; }
        if (!empty($filters['module']))    { $sql .= ' AND module = ?';   $params[] = $filters['module']; }
        if (!empty($filters['action']))    { $sql .= ' AND action = ?';   $params[] = $filters['action']; }
        if (!empty($filters['status']))    { $sql .= ' AND status = ?';   $params[] = $filters['status']; }
        if (!empty($filters['date_from'])) { $sql .= ' AND DATE(created_at) >= ?'; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to']))   { $sql .= ' AND DATE(created_at) <= ?'; $params[] = $filters['date_to']; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
