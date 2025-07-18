<?php
namespace WakeOnStorage;

class Config
{
    public static function load(string $file): array
    {
        if (function_exists('yaml_parse_file')) {
            $data = yaml_parse_file($file);
            if (is_array($data)) {
                return $data;
            }
        }
        return self::simpleParse($file);
    }

    private static function simpleParse(string $file): array
    {
        $lines = file($file);
        $data = [];
        $refs = [&$data];
        $indents = [0];
        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '' || preg_match('/^\s*#/', $line)) {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line));
            $line = ltrim($line);
            while ($indent < end($indents)) {
                array_pop($indents);
                array_pop($refs);
            }
            $current = &$refs[count($refs)-1];
            if (strpos($line, '- ') === 0) {
                $val = substr($line, 2);
                $current[] = self::parseValue($val);
                continue;
            }
            if (strpos($line, ':') !== false) {
                list($key, $value) = array_map('trim', explode(':', $line, 2));
                if ($value === '') {
                    $current[$key] = [];
                    $refs[] = &$current[$key];
                    $indents[] = $indent + 2;
                } else {
                    $current[$key] = self::parseValue($value);
                }
            }
        }
        return $data;
    }

    private static function parseValue(string $value)
    {
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        if ($value !== '' && $value[0] === '"' && substr($value, -1) === '"') {
            return substr($value, 1, -1);
        }
        return $value;
    }
}
