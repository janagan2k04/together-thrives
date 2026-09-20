<?php
/**
 * Global helper functions. Loaded once in index.php.
 */

use Core\Config;
use Core\Csrf;
use Core\Auth;

/** Escape for HTML output. Use this on EVERY echoed variable. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an absolute URL from an app path. */
function url(string $path = '/'): string
{
    return rtrim(Config::get('base_url'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Serve an uploaded file through the protected file controller. */
function upload_url(string $relativePath): string
{
    return url('file/' . ltrim($relativePath, '/'));
}

function csrf_field(): string { return Csrf::field(); }

/** Spoof PUT/DELETE for HTML forms. */
function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

/**
 * Old input and validation errors belonging to the view being rendered.
 * Core\Controller::view() seeds this once per request; old(), error_for() and
 * has_error() read it. Views never call this directly.
 *
 * It exists because Controller::view() extracts $old/$errors into *method*
 * scope — a `global $old` in these helpers would never see them.
 */
function form_state(?array $old = null, ?array $errors = null): array
{
    static $state = ['old' => [], 'errors' => []];

    if ($old !== null)    { $state['old']    = $old; }
    if ($errors !== null) { $state['errors'] = $errors; }

    return $state;
}

function old(string $key, mixed $default = ''): string
{
    return e(form_state()['old'][$key] ?? $default);
}

/** First error message for a field, ready to print. */
function error_for(string $key): string
{
    $errors = form_state()['errors'];
    return isset($errors[$key][0]) ? e($errors[$key][0]) : '';
}

function has_error(string $key): bool
{
    return !empty(form_state()['errors'][$key]);
}

function auth(): ?array { return Auth::user(); }
function is_logged_in(): bool { return Auth::check(); }
function role_is(string ...$roles): bool { return Auth::is(...$roles); }

/** Mark the active sidebar item. */
function nav_active(string $prefix): string
{
    return str_starts_with(Core\Request::uri(), $prefix) ? ' is-active' : '';
}

function format_money(float|int|string $amount): string
{
    return 'LKR ' . number_format((float) $amount, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    return $date ? date($format, strtotime($date)) : '—';
}

function time_ago(?string $datetime): string
{
    if (!$datetime) return '—';
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', strtotime($datetime));
}

function str_limit(string $text, int $limit = 120): string
{
    return mb_strlen($text) <= $limit ? $text : mb_substr($text, 0, $limit) . '…';
}

/** Translate a key using the active language file. */
function __(string $key, ?string $fallback = null): string
{
    return Core\Lang::get($key, $fallback);
}

function dd(mixed ...$vars): never
{
    echo '<pre style="background:#111;color:#0f0;padding:16px;font-size:13px">';
    foreach ($vars as $v) { var_dump($v); }
    echo '</pre>';
    exit;
}
