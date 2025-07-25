<?php
namespace WakeOnStorage;

class ServiceRunner
{
    private string $sudo;
    private string $script;

    public function __construct(string $sudo, string $script)
    {
        $this->sudo = $sudo;
        $this->script = $script;
    }

    public function run(string $service, string $action): array
    {
        $cmd = escapeshellcmd($this->sudo) . ' ' . $this->script
            . ' ' . escapeshellarg($service)
            . ' ' . escapeshellarg($action);
        Logger::log(4, "run cmd: $cmd");
        $output = [];
        $ret = 0;
        exec($cmd . ' 2>&1', $output, $ret);
        $json = implode("\n", $output);
        Logger::log(4, "cmd ret:$ret output:$json");

        $result = json_decode($json, true);
        if ($result === null && count($output) > 1) {
            $jsonLine = array_pop($output);
            $result = json_decode($jsonLine, true);
            if ($result !== null) {
                return [
                    'error' => 'command_failed',
                    'output' => implode("\n", $output)
                ];
            }
        }
        if ($result === null) {
            return ['error' => 'invalid_output', 'output' => $json];
        }
        return $result;
    }
}
