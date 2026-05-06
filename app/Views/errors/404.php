<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>404 - Página no encontrada</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/css/style.css" />
  <style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f9fa;}</style>
</head>
<body>
  <div class="text-center">
    <h1 class="display-1 text-primary">404</h1>
    <h4>Página no encontrada</h4>
    <p class="text-muted">La ruta solicitada no existe en el sistema.</p>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/dashboard" class="btn btn-primary">Ir al Dashboard</a>
  </div>
</body>
</html>
