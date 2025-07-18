<?php
namespace WakeOnStorage;

class Auth
{
    private array $config;

    public function __construct(array $appConfig)
    {
        $this->config = $appConfig['auth'] ?? [];
    }

    public function check(): bool
    {
        // IP restriction
        $allowed = $this->config['allowed_ips'] ?? [];
        if ($allowed && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed)) {
            http_response_code(403);
            echo json_encode(['error' => 'forbidden']);
            return false;
        }

        $token = '';
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.*)/', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
                $token = $m[1];
            }
        }
        if (!empty($this->config['token']) && $token !== $this->config['token']) {
            http_response_code(403);
            echo json_encode(['error' => 'unauthorized']);
            return false;
        }
        return true;
    }
}
