<?php

namespace App\Services;

use Core\Logger;

class UploadService
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/uploads.php';
    }

    /**
     * Upload a file using a named profile defined in config/uploads.php.
     *
     * Options:
     *   prefix (string) — override the profile's default filename prefix
     *
     * Returns array with keys:
     *   success (bool), filename (string|null), original_name (string|null),
     *   mime (string|null), size (int|null), error (string|null — user-friendly message)
     */
    public function upload(array $file, string $profile, array $options = []): array
    {
        $empty = [
            'success'       => false,
            'filename'      => null,
            'original_name' => null,
            'mime'          => null,
            'size'          => null,
            'error'         => null,
        ];

        if (!isset($this->config['profiles'][$profile])) {
            Logger::error("UploadService: perfil desconocido '{$profile}'");
            return array_merge($empty, ['error' => __('upload.save_failed')]);
        }

        $cfg = $this->config['profiles'][$profile];

        // 1. Verificar que se recibió un archivo
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return array_merge($empty, ['error' => __('upload.no_file')]);
        }

        // 2. Error de subida de PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Logger::error("UploadService: error PHP {$file['error']} perfil={$profile}");
            return array_merge($empty, ['error' => __('upload.error')]);
        }

        // 3. Bloquear extensiones peligrosas del nombre original
        $originalName = isset($file['name']) ? basename($file['name']) : '';
        $originalExt  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $forbidden    = $this->config['forbidden_extensions'] ?? [];

        if (in_array($originalExt, $forbidden, true)) {
            Logger::security("UploadService: extensión prohibida '{$originalExt}' perfil={$profile}");
            return array_merge($empty, ['error' => __('upload.forbidden_extension')]);
        }

        // 4. Tamaño máximo
        if ($file['size'] > $cfg['max_size']) {
            return array_merge($empty, ['error' => __('upload.max_size_exceeded')]);
        }

        // 5. MIME real (no el que declara el cliente)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $cfg['allowed_mime'], true)) {
            Logger::security("UploadService: MIME no permitido '{$mime}' perfil={$profile}");
            return array_merge($empty, ['error' => __('upload.invalid_mime')]);
        }

        // 6. Extensión derivada del MIME (nunca del nombre del cliente)
        $ext = $this->extFromMime($mime);
        if (!$ext) {
            return array_merge($empty, ['error' => __('upload.invalid_extension')]);
        }

        // 7. Validar contenido real de imagen si el perfil lo requiere
        if (!empty($cfg['validate_image'])) {
            if (!@getimagesize($file['tmp_name'])) {
                Logger::security("UploadService: getimagesize falló perfil={$profile}");
                return array_merge($empty, ['error' => __('upload.invalid_image')]);
            }
        }

        // 8. Generar nombre de archivo seguro
        $prefix   = $options['prefix'] ?? ($cfg['prefix'] ?? 'file_');
        $filename = basename($prefix . bin2hex(random_bytes(8)) . '.' . $ext);

        // 9. Crear directorio si no existe
        $diskPath = $cfg['disk_path'];
        if (!is_dir($diskPath)) {
            if (!mkdir($diskPath, 0755, true)) {
                Logger::error("UploadService: no se pudo crear directorio '{$diskPath}'");
                return array_merge($empty, ['error' => __('upload.directory_not_writable')]);
            }
        }

        if (!is_writable($diskPath)) {
            Logger::error("UploadService: directorio sin escritura '{$diskPath}'");
            return array_merge($empty, ['error' => __('upload.directory_not_writable')]);
        }

        // 10. Mover archivo al destino final
        if (!move_uploaded_file($file['tmp_name'], $diskPath . $filename)) {
            Logger::error("UploadService: move_uploaded_file falló perfil={$profile} file={$filename}");
            return array_merge($empty, ['error' => __('upload.save_failed')]);
        }

        return [
            'success'       => true,
            'filename'      => $filename,
            'original_name' => $originalName,
            'mime'          => $mime,
            'size'          => $file['size'],
            'error'         => null,
        ];
    }

    /**
     * Delete a stored file (by basename) within a profile's disk path.
     * Returns true if the file was deleted, false if it didn't exist or couldn't be removed.
     */
    public function delete(?string $filename, string $profile): bool
    {
        if (!$filename || !isset($this->config['profiles'][$profile])) {
            return false;
        }
        $path = $this->config['profiles'][$profile]['disk_path'] . basename($filename);
        if (file_exists($path)) {
            return @unlink($path);
        }
        return false;
    }

    /**
     * Return the absolute disk path for a named profile.
     */
    public function getDiskPath(string $profile): string
    {
        return $this->config['profiles'][$profile]['disk_path'] ?? '';
    }

    private function extFromMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => null,
        };
    }
}
