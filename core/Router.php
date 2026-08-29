<?php

namespace Core;

class Router
{
    private array $routes = [];

    /**
     * Guard opcional de ruta (Etapa 4). $options es un array asociativo
     * opcional; si se omite, el comportamiento es identico al de antes de
     * esta etapa (ruta publica, sin ningun guard).
     *
     * Claves soportadas:
     *   'guest'      => bool    Equivalente a Auth::requireGuest().
     *   'auth'       => bool    Equivalente a Auth::requireAuth().
     *   'permission' => string  Equivalente a Auth::requirePermission($slug)
     *                           (ya implica autenticacion internamente).
     *
     * Los checks dentro de los controladores NO deben eliminarse todavia:
     * este guard es defensa adicional, no un reemplazo (ver docs/crear-nuevo-modulo.md).
     */
    public function get(string $path, array $handler, array $options = []): void
    {
        $this->routes[] = [
            'method'  => 'GET',
            'path'    => $path,
            'handler' => $handler,
            'guard'   => $this->normalizeGuard($path, $options),
        ];
    }

    public function post(string $path, array $handler, array $options = []): void
    {
        $this->routes[] = [
            'method'  => 'POST',
            'path'    => $path,
            'handler' => $handler,
            'guard'   => $this->normalizeGuard($path, $options),
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = strtok($uri, '?');
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $pattern = $this->pathToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                $this->applyGuard($route['guard']);

                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                $controller->$action(...$matches);
                return;
            }
        }

        \Core\ErrorHandler::render404();
    }

    /**
     * Valida y normaliza la metadata de guard recibida al registrar la ruta.
     * Se ejecuta al momento de llamar get()/post() (es decir, al construir
     * las rutas en public/index.php), NO en cada dispatch. Un error de
     * configuracion lanza InvalidArgumentException, que el ErrorHandler
     * global ya captura y muestra como error 500 generico (sin detalles
     * tecnicos salvo APP_DEBUG) — se detecta temprano en desarrollo en vez
     * de dejar una ruta mal protegida en silencio.
     */
    private function normalizeGuard(string $path, array $options): array
    {
        $hasGuest      = array_key_exists('guest', $options);
        $hasAuthOpt    = array_key_exists('auth', $options);
        $hasPermission = array_key_exists('permission', $options);

        if (array_key_exists('permissions', $options)) {
            throw new \InvalidArgumentException(
                "Router: ruta '{$path}' — la opcion 'permissions' (multiple) no esta soportada en esta etapa. Usa 'permission' (string unico)."
            );
        }

        if ($hasGuest && !is_bool($options['guest'])) {
            throw new \InvalidArgumentException("Router: ruta '{$path}' — la opcion 'guest' debe ser boolean.");
        }

        if ($hasAuthOpt && !is_bool($options['auth'])) {
            throw new \InvalidArgumentException("Router: ruta '{$path}' — la opcion 'auth' debe ser boolean.");
        }

        if ($hasPermission && !is_string($options['permission'])) {
            throw new \InvalidArgumentException("Router: ruta '{$path}' — la opcion 'permission' debe ser un string.");
        }

        $guest = $hasGuest && $options['guest'] === true;
        $auth  = $hasAuthOpt && $options['auth'] === true;
        $permission = $hasPermission ? $options['permission'] : null;

        if ($guest && ($auth || $permission !== null)) {
            throw new \InvalidArgumentException(
                "Router: ruta '{$path}' — no se puede combinar 'guest' con 'auth'/'permission' en la misma ruta."
            );
        }

        return [
            'guest'      => $guest,
            'auth'       => $auth,
            'permission' => $permission,
        ];
    }

    /**
     * Aplica el guard ya validado, justo antes de instanciar el
     * controlador. Orden: guest primero (y termina ahi); si no, permission
     * (que ya exige autenticacion internamente via Auth::requireAuth());
     * si no, auth explicito. No se duplica Auth::requireAuth() cuando ya
     * se llamo por 'permission'.
     */
    private function applyGuard(array $guard): void
    {
        if ($guard['guest']) {
            \Core\Auth::requireGuest();
            return;
        }

        if ($guard['permission'] !== null) {
            \Core\Auth::requirePermission($guard['permission']);
            return;
        }

        if ($guard['auth']) {
            \Core\Auth::requireAuth();
        }
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
