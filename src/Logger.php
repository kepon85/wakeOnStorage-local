<?php
namespace WakeOnStorage;

class Logger
{
    private static int $level = 0;
    private static string $file = '';
    private static int $maxSize = 1048576;
    private static bool $capture = false;
    private static array $buffer = [];

    public static function configure(array $config): void
    {
        self::$level = $config['level'] ?? 0;
        self::$file = $config['file'] ?? '';
        self::$maxSize = $config['max_size'] ?? self::$maxSize;
    }

    public static function startCapture(): void
    {
        self::$capture = true;
        self::$buffer = [];
    }

    public static function getCapture(): array
    {
        return self::$buffer;
    }

    public static function log(int $level, string $message): void
    {
        if (self::$capture) {
            self::$buffer[] = $message;
        }
        if ($level > self::$level || self::$level === 0 || self::$file === '') {
            return;
        }
        if (file_exists(self::$file) && filesize(self::$file) > self::$maxSize) {
            @rename(self::$file, self::$file.'.1');
        }
        $line = date('c').' '.$message.PHP_EOL;
        file_put_contents(self::$file, $line, FILE_APPEND);
    }
}
