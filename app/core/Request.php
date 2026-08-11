<?php
namespace Core;

class Request
{
    public static function uri(): string
    {
        $url = $_GET['url'] ?? '';
        return '/' . trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    }

    public static function method(): string
    {
        $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        // HTML forms can only POST; use a hidden _method field for PUT/DELETE
        if ($m === 'POST' && !empty($_POST['_method'])) {
            $m = strtoupper($_POST['_method']);
        }
        return $m;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public static function all(): array
    {
        $data = array_merge($_GET, $_POST);
        unset($data['url'], $data['_method'], $data['_csrf']);
        return array_map(fn($v) => is_string($v) ? trim($v) : $v, $data);
    }

    public static function file(string $key): ?array
    {
        return (isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE)
            ? $_FILES[$key] : null;
    }

    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
