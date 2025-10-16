<?php
require __DIR__ . '/../vendor/autoload.php';

use WakeOnStorage\Config;
use WakeOnStorage\Auth;
use WakeOnStorage\ServiceManager;
use WakeOnStorage\ServiceRunner;
use WakeOnStorage\Logger;

header('Content-Type: application/json');

$appConfig = Config::load(__DIR__ . '/../config/app.yaml');
Logger::configure($appConfig['log'] ?? []);
Logger::log(4, 'load index.php');
$serviceConfig = Config::load(__DIR__ . '/../config/services.yaml');

$auth = new Auth($appConfig);
Logger::log(4, 'init Auth');
if (!$auth->check()) {
    exit;
}

$manager = new ServiceManager($serviceConfig['services'] ?? []);
Logger::log(4, 'init ServiceManager');
$runner = new ServiceRunner(
    $appConfig['sudo_path'] ?? 'sudo',
    $appConfig['service_script'] ?? __DIR__ . '/../bin/service'
);
Logger::log(4, 'init ServiceRunner');

$method = $_SERVER['REQUEST_METHOD'];
$path = trim($_GET['r'] ?? '', '/');
if ($path === '') {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}
$base = trim($appConfig['base_path'] ?? '', '/');
if ($base !== '' && strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}
$path = ltrim($path, '/');
$scriptName = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
if ($scriptName !== '' && ($path === $scriptName || strpos($path, $scriptName . '/') === 0)) {
    $path = substr($path, strlen($scriptName));
    $path = ltrim($path, '/');
}
if ($base !== '' && strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}
$path = ltrim($path, '/');
Logger::log(4, "request $method $path");
$parts = $path === '' ? [] : explode('/', $path);

if ($path === 'services' && $method === 'GET') {
    Logger::log(4, 'route services/list');
    echo json_encode($manager->listServices());
    exit;
}

if (count($parts) >= 2) {
    $service = $parts[0];
    $action = $parts[1];
    Logger::log(4, "route $service/$action");

    if (!$manager->has($service)) {
        Logger::log(4, 'service_not_found');
        http_response_code(404);
        echo json_encode(['error' => 'service_not_found']);
        exit;
    }

    if ($method === 'GET') {
        if ($action === 'status') {
            Logger::log(4, 'action status');
            echo json_encode($runner->run($service, 'status'));
            exit;
        }
        if ($action === 'count') {
            Logger::log(4, 'action count');
            echo json_encode($runner->run($service, 'count'));
            exit;
        }
    }

    if ($method === 'POST') {
        if ($action === 'up') {
            Logger::log(4, 'action up');
            echo json_encode($runner->run($service, 'up'));
            exit;
        }
        if ($action === 'down') {
            Logger::log(4, 'action down');
            echo json_encode($runner->run($service, 'down'));
            exit;
        }
        if ($action === 'down-force') {
            Logger::log(4, 'action down-force');
            echo json_encode($runner->run($service, 'down-force'));
            exit;
        }
    }
}

http_response_code(404);
Logger::log(4, 'route_not_found');
echo json_encode(['error' => 'not_found']);
