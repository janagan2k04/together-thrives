<?php
namespace Core;

class Lang
{
    private static array $lines = [];
    private static string $locale = 'en';

    public static function load(): void
    {
        $locale = Session::get('locale', Config::get('default_locale', 'en'));
        if (!in_array($locale, Config::get('locales', ['en']), true)) {
            $locale = 'en';
        }
        self::$locale = $locale;

        $file = BASE_PATH . "/resources/lang/{$locale}.php";
        self::$lines = is_file($file) ? require $file : [];
    }

    public static function get(string $key, ?string $fallback = null): string
    {
        return self::$lines[$key] ?? $fallback ?? $key;
    }

    public static function locale(): string { return self::$locale; }

    public static function set(string $locale): void
    {
        Session::set('locale', $locale);
        self::load();
    }
}
