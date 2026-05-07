<?php

namespace App\Models;

use Core\Model;

class Module extends Model
{
    private const TABLE = 'tbl_modules';

    public function getAllActive(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM ' . self::TABLE . ' WHERE status_id = 1 ORDER BY sort_order'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM ' . self::TABLE . ' ORDER BY sort_order'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
