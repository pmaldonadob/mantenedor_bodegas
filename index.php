<?php

declare(strict_types=1);

// Mostrar errores solo en desarrollo
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/controllers/BodegaController.php';

// Obtener la acción solicitada (default: index)
$action = $_GET['action'] ?? 'index';

// Acciones permitidas y su método HTTP correspondiente
$routes = [
    'index'    => 'GET',
    'crear'    => 'POST',
    'obtener'  => 'GET',
    'editar'   => 'POST',
    'eliminar' => 'POST',
];

// Validar que la acción exista
if (!array_key_exists($action, $routes)) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Acción no encontrada.']));
}

// Validar método HTTP
$expectedMethod = $routes[$action];
if ($_SERVER['REQUEST_METHOD'] !== $expectedMethod) {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método HTTP no permitido.']));
}

// Instanciar controlador y ejecutar la acción
try {
    $controller = new BodegaController();
    $controller->{$action}();
} catch (PDOException $e) {
    // Error de conexión a base de datos
    $msg = 'Error de conexión a la base de datos. Verifique la configuración en config/database.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => $msg]);
    } else {
        // Para la vista principal, mostrar error HTML
        echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:2rem;'>
              <h2 style='color:#c0392b;'>&#9888; Error de conexión</h2>
              <p>{$msg}</p>
              <pre>" . htmlspecialchars($e->getMessage()) . "</pre>
              </body></html>";
    }
}
