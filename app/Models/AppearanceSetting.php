<?php

namespace App\Models;

use Core\Model;

class AppearanceSetting extends Model
{
    private const TABLE = 'tbl_appearance_settings';
    private static ?array $cache = null;

    public function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' ORDER BY id ASC LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch();
        self::$cache = $row ?: $this->defaults();
        return self::$cache;
    }

    public function createOrUpdate(array $data): bool
    {
        self::$cache = null;
        $existing = $this->getRaw();
        if ($existing) {
            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET app_display_name = ?, app_tagline = ?,
                     logo_path = ?, favicon_path = ?, login_background_path = ?,
                     primary_color = ?, sidebar_color = ?,
                     login_overlay_color = ?, login_overlay_opacity = ?,
                     updated_at = NOW()
                 WHERE id = ?'
            );
            return $stmt->execute([
                $data['app_display_name'] ?? null,
                $data['app_tagline'] ?? null,
                $data['logo_path'] ?? null,
                $data['favicon_path'] ?? null,
                $data['login_background_path'] ?? null,
                $data['primary_color'] ?? null,
                $data['sidebar_color'] ?? null,
                $data['login_overlay_color'] ?? null,
                $data['login_overlay_opacity'] ?? 0.40,
                $existing['id'],
            ]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . '
             (app_display_name, app_tagline, logo_path, favicon_path, login_background_path,
              primary_color, sidebar_color, login_overlay_color, login_overlay_opacity)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        return $stmt->execute([
            $data['app_display_name'] ?? null,
            $data['app_tagline'] ?? null,
            $data['logo_path'] ?? null,
            $data['favicon_path'] ?? null,
            $data['login_background_path'] ?? null,
            $data['primary_color'] ?? null,
            $data['sidebar_color'] ?? null,
            $data['login_overlay_color'] ?? null,
            $data['login_overlay_opacity'] ?? 0.40,
        ]);
    }

    private function getRaw(): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' ORDER BY id ASC LIMIT 1');
        $stmt->execute();
        return $stmt->fetch();
    }

    private function defaults(): array
    {
        return [
            'id'                    => null,
            'app_display_name'      => null,
            'app_tagline'           => null,
            'logo_path'             => null,
            'favicon_path'          => null,
            'login_background_path' => null,
            'primary_color'         => null,
            'sidebar_color'         => null,
            'login_overlay_color'   => null,
            'login_overlay_opacity' => 0.40,
        ];
    }
}
