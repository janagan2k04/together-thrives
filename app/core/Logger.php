<?php
namespace Core;

class Logger
{
    public static function write(string $level, string $message, string $file = 'app'): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $line = sprintf("[%s] %s: %s%s", date('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
        file_put_contents("{$dir}/{$file}.log", $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $m): void { self::write('error', $m, 'error'); }
    public static function info(string $m): void  { self::write('info', $m); }
    public static function audit(string $m): void { self::write('audit', $m, 'audit'); }
}
