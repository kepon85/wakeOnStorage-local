<?php
namespace WakeOnStorage;

use WakeOnStorage\Config;
use WakeOnStorage\Auth;
use WakeOnStorage\ServiceManager;
use WakeOnStorage\ServiceRunner;
use WakeOnStorage\Logger;

class Api
{
    private array $appConfig;
    private array $serviceConfig;
    private Auth $auth;
    private ServiceManager $manager;
    private ServiceRunner $runner;
    private bool $debug = false;

    public function __construct()
    {
        $appFile = __DIR__ . '/../config/app.yaml';
        $svcFile = __DIR__ . '/../config/services.yaml';
        $this->appConfig = Config::load($appFile);
        Logger::configure($this->appConfig['log'] ?? []);
        Logger::log(4, 'load Api');
        Logger::log(4, 'app config ' . realpath($appFile));
        $this->serviceConfig = Config::load($svcFile);
        Logger::log(4, 'services config ' . realpath($svcFile));

        $this->auth = new Auth($this->appConfig);
        Logger::log(4, 'init Auth');
        $this->manager = new ServiceManager($this->serviceConfig['services'] ?? []);
        Logger::log(4, 'init ServiceManager');
        $this->runner = new ServiceRunner(
            $this->appConfig['sudo_path'] ?? 'sudo',
            $this->appConfig['service_script'] ?? __DIR__ . '/../bin/service'
        );
        Logger::log(4, 'init ServiceRunner');
        $addr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (in_array($addr, $this->appConfig['debug_ips'] ?? [])) {
            $this->debug = true;
            Logger::startCapture();
        }
    }

    private function response(array $data): void
    {
        if ($this->debug) {
            $data['debug'] = Logger::getCapture();
        }
        echo json_encode($data);
    }

    public function run(): void
    {
        if (!$this->auth->check()) {
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $base = trim($this->appConfig['base_path'] ?? '', '/');
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        $path = ltrim($path, '/');
        Logger::log(4, "request $method $path");
        $parts = $path === '' ? [] : explode('/', $path);

        if ($path === 'services' && $method === 'GET') {
            Logger::log(4, 'route services/list');
            $this->response($this->manager->listServices());
            return;
        }

        if (count($parts) >= 2) {
            $service = $parts[0];
            $action = $parts[1];
            Logger::log(4, "route $service/$action");

            if (!$this->manager->has($service)) {
                Logger::log(4, 'service_not_found');
                http_response_code(404);
                $this->response(['error' => 'service_not_found']);
                return;
            }

            if ($method === 'GET') {
                if ($action === 'status') {
                    Logger::log(4, 'action status');
                    $this->response($this->runner->run($service, 'status'));
                    return;
                }
                if ($action === 'count') {
                    Logger::log(4, 'action count');
                    $this->response($this->runner->run($service, 'count'));
                    return;
                }
            }

            if ($method === 'POST') {
                if ($action === 'up') {
                    Logger::log(4, 'action up');
                    $this->response($this->runner->run($service, 'up'));
                    return;
                }
                if ($action === 'down') {
                    Logger::log(4, 'action down');
                    $this->response($this->runner->run($service, 'down'));
                    return;
                }
                if ($action === 'down-force') {
                    Logger::log(4, 'action down-force');
                    $this->response($this->runner->run($service, 'down-force'));
                    return;
                }
            }
        }

        http_response_code(404);
        Logger::log(4, 'route_not_found');
        $this->response(['error' => 'not_found']);
    }
}
