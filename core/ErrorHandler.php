<?php

namespace Core;

class ErrorHandler
{
    private static bool  $handling = false;
    private static ?bool $debug    = null;

    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(\Throwable $e): void
    {
        self::logException($e);
        self::tryAudit('error.exception', get_class($e) . ': ' . $e->getMessage());
        self::renderGeneral([
            'code'    => 500,
            'type'    => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        self::logError($errno, $errstr, $errfile, $errline);

        if ($errno === E_USER_ERROR) {
            self::renderGeneral([
                'code'    => 500,
                'type'    => 'E_USER_ERROR',
                'message' => $errstr,
                'file'    => $errfile,
                'line'    => $errline,
                'trace'   => '',
            ]);
        }

        return true;
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

        if ($error === null || !in_array($error['type'], $fatal, true) || self::$handling) {
            return;
        }

        self::logError($error['type'], $error['message'], $error['file'], $error['line']);
        self::renderGeneral([
            'code'    => 500,
            'type'    => self::errorName($error['type']),
            'message' => $error['message'],
            'file'    => $error['file'],
            'line'    => $error['line'],
            'trace'   => '',
        ]);
    }

    public static function render404(): void
    {
        if (headers_sent()) {
            exit;
        }

        http_response_code(404);
        self::tryAudit('error.404', '404 — ' . ($_SERVER['REQUEST_URI'] ?? ''));

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $view = dirname(__DIR__) . '/app/Views/errors/404.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<h1>404 - Not Found</h1>';
        }

        exit;
    }

    private static function renderGeneral(array $context): void
    {
        if (self::$handling) {
            echo self::fallbackHtml($context, self::isDebug());
            exit;
        }

        self::$handling = true;

        if (!headers_sent()) {
            http_response_code($context['code'] ?? 500);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $isDebug      = self::isDebug();
        $errorContext = $context;

        $view = dirname(__DIR__) . '/app/Views/errors/general.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo self::fallbackHtml($context, $isDebug);
        }

        exit;
    }

    private static function logException(\Throwable $e): void
    {
        $message = sprintf(
            '[%s] %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        if (self::isDebug()) {
            $message .= "\nTrace:\n" . $e->getTraceAsString();
        }

        self::writeLog($message);
    }

    private static function logError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $message = sprintf(
            '[%s] %s in %s:%d',
            self::errorName($errno),
            $errstr,
            $errfile,
            $errline
        );

        self::writeLog($message);
    }

    private static function writeLog(string $message): void
    {
        try {
            $config  = require dirname(__DIR__) . '/config/app.php';
            $logPath = $config['error_log_path'] ?? dirname(__DIR__) . '/logs/error.log';
            $dir     = dirname($logPath);

            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $line = sprintf(
                "[%s] [IP: %s] %s\n",
                date('Y-m-d H:i:s'),
                $_SERVER['REMOTE_ADDR'] ?? 'cli',
                $message
            );

            @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // Silently fail — logging must never cause a secondary error
        }
    }

    private static function tryAudit(string $action, string $description): void
    {
        try {
            if (class_exists('\Core\Audit', false)) {
                \Core\Audit::log([
                    'module'      => 'errors',
                    'action'      => $action,
                    'description' => $description,
                    'status'      => 'warning',
                ]);
            }
        } catch (\Throwable) {
            // Never let audit failures cascade
        }
    }

    public static function isDebug(): bool
    {
        if (self::$debug !== null) {
            return self::$debug;
        }

        try {
            $config      = require dirname(__DIR__) . '/config/app.php';
            self::$debug = (bool)($config['debug'] ?? false);
        } catch (\Throwable) {
            self::$debug = false;
        }

        return self::$debug;
    }

    private static function errorName(int $type): string
    {
        return match ($type) {
            E_ERROR         => 'E_ERROR',
            E_WARNING       => 'E_WARNING',
            E_PARSE         => 'E_PARSE',
            E_NOTICE        => 'E_NOTICE',
            E_CORE_ERROR    => 'E_CORE_ERROR',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_USER_ERROR    => 'E_USER_ERROR',
            E_USER_WARNING  => 'E_USER_WARNING',
            E_USER_NOTICE   => 'E_USER_NOTICE',
            default         => "E_UNKNOWN({$type})",
        };
    }

    private static function fallbackHtml(array $ctx, bool $debug): string
    {
        $code = (int)($ctx['code'] ?? 500);
        $html  = "<!doctype html><html><head><meta charset='utf-8'>";
        $html .= "<title>Error {$code}</title></head>";
        $html .= "<body style='font-family:sans-serif;text-align:center;padding:4rem;'>";
        $html .= "<h1 style='font-size:4rem;color:#dc3545;'>{$code}</h1>";
        $html .= "<p>Ha ocurrido un error inesperado.</p>";

        if ($debug && !empty($ctx['message'])) {
            $html .= '<pre style="text-align:left;background:#f8f9fa;padding:1rem;border-radius:4px;overflow:auto;">';
            $html .= htmlspecialchars(($ctx['type'] ?? '') . ': ' . $ctx['message'], ENT_QUOTES, 'UTF-8');
            if (!empty($ctx['trace'])) {
                $html .= "\n\n" . htmlspecialchars($ctx['trace'], ENT_QUOTES, 'UTF-8');
            }
            $html .= '</pre>';
        }

        $html .= '</body></html>';
        return $html;
    }
}
