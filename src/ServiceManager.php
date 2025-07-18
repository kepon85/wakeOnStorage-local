<?php
namespace WakeOnStorage;

use WakeOnStorage\Logger;

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

    private function execCommand(string $cmd): array
    {
        Logger::log(4, "exec: $cmd");
        $output = [];
        $ret = 0;
        exec($cmd . ' 2>&1', $output, $ret);
        Logger::log(4, "ret: $ret out:" . implode(' ', $output));
        return [$ret, $output];
    }

    private function runCommands(string $service, string $action): array
    {
        $svc = $this->services[$service] ?? null;
        if (!$svc) {
            Logger::log(2, "service_not_found $service");
            return ['error' => 'service_not_found'];
        }
        $cmds = $svc['commands'][$action] ?? null;
        if (!$cmds) {
            Logger::log(2, "action_not_defined $action on $service");
            return ['error' => 'action_not_defined'];
        }
        if (!is_array($cmds)) {
            $cmds = [$cmds];
        }
        foreach ($cmds as $cmd) {
            [$ret, $output] = $this->execCommand($cmd);
            if ($ret !== 0) {
                Logger::log(1, "command_failed $cmd");
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
        $svc = $this->services[$service]['commands']['status'] ?? null;
        if (!$svc) {
            return ['error' => 'action_not_defined'];
        }
        $cmd = is_array($svc) ? $svc[0] : $svc;
        [$ret] = $this->execCommand($cmd);
        return ['status' => $ret === 0 ? 'up' : 'down'];
    }

    public function count(string $service): array
    {
        $svc = $this->services[$service]['commands']['count'] ?? null;
        if (!$svc) {
            return ['error' => 'action_not_defined'];
        }
        $cmd = is_array($svc) ? $svc[0] : $svc;
        [$ret, $output] = $this->execCommand($cmd);
        $count = (int)trim($output[0] ?? '0');
        return ['count' => $count];
    }

    public function up(string $service): array
    {
        $status = $this->status($service);
        if (($status['status'] ?? '') === 'up') {
            return ['info' => 'already running'];
        }
        return $this->runCommands($service, 'up');
    }

    public function down(string $service): array
    {
        $status = $this->status($service);
        if (($status['status'] ?? '') === 'down') {
            Logger::log(4, "already_down $service");
            return ['info' => 'already stopped'];
        }
        $count = $this->count($service);
        if (($count['count'] ?? 0) > 0) {
            return ['info' => 'connections_active', 'count' => $count['count']];
        }
        return $this->runCommands($service, 'down');
    }

    public function downForce(string $service): array
    {
        $status = $this->status($service);
        if (($status['status'] ?? '') === 'down') {
            Logger::log(4, "already_down $service");
            return ['info' => 'already stopped'];
        }
        return $this->runCommands($service, 'down');
    }
}
