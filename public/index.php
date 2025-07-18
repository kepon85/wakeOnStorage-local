<?php
require __DIR__ . '/../vendor/autoload.php';

use WakeOnStorage\Config;
use WakeOnStorage\Auth;
use WakeOnStorage\ServiceManager;

header('Content-Type: application/json');

$appConfig = Config::load(__DIR__ . '/../config/app.yaml');
$serviceConfig = Config::load(__DIR__ . '/../config/services.yaml');

$auth = new Auth($appConfig);
if (!$auth->check()) {
    exit;
}

$manager = new ServiceManager($serviceConfig['services'] ?? []);

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = $path === '' ? [] : explode('/', $path);

if ($path === 'services' && $method === 'GET') {
    echo json_encode($manager->listServices());
    exit;
}

if (count($parts) >= 2) {
    $service = $parts[0];
    $action = $parts[1];

    if (!$manager->has($service)) {
        http_response_code(404);
        echo json_encode(['error' => 'service_not_found']);
        exit;
    }

    if ($method === 'GET' && $action === 'status') {
        echo json_encode($manager->status($service));
        exit;
    }

    if ($method === 'POST' && in_array($action, ['up', 'down', 'status'], true)) {
        echo json_encode($manager->run($service, $action));
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'not_found']);
