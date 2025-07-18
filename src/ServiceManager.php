<?php
namespace WakeOnStorage;

class ServiceManager
{
    private array $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    public function listServices(): array
    {
        $out = [];
        foreach ($this->services as $name => $data) {
            $out[] = ['service' => $name, 'type' => $data['type'] ?? ''];
        }
        return $out;
    }

    public function has(string $service): bool
    {
        return isset($this->services[$service]);
    }

    public function run(string $service, string $action): array
    {
        $svc = $this->services[$service] ?? null;
        if (!$svc) {
            return ['error' => 'service_not_found'];
        }
        $cmds = $svc['commands'][$action] ?? null;
        if (!$cmds) {
            return ['error' => 'action_not_defined'];
        }
        foreach ($cmds as $cmd) {
            $output = [];
            $ret = 0;
            exec($cmd . ' 2>&1', $output, $ret);
            if ($ret !== 0) {
                return [
                    'error' => 'command_failed',
                    'command' => $cmd,
                    'output' => implode("\n", $output)
                ];
            }
        }
        return ['success' => true];
    }

    public function status(string $service): array
    {
        return $this->run($service, 'status');
    }
}
