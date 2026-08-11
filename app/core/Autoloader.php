<?php
namespace Core;

/**
 * Maps namespaces to folders.
 *   Core\Router                 -> app/core/Router.php
 *   Controllers\HomeController  -> app/controllers/HomeController.php
 *   Models\Cleanup\CleanupEvent -> app/models/cleanup/CleanupEvent.php
 */
class Autoloader
{
    private const MAP = [
        'Core\\'        => '/core/',
        'Controllers\\' => '/controllers/',
        'Models\\'      => '/models/',
        'Services\\'    => '/services/',
        'Middleware\\'  => '/middleware/',
    ];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        foreach (self::MAP as $prefix => $dir) {
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $parts    = explode('\\', $relative);
                $file     = array_pop($parts);
                $sub      = $parts ? strtolower(implode('/', $parts)) . '/' : '';
                $path     = APP_PATH . $dir . $sub . $file . '.php';
                if (is_file($path)) {
                    require $path;
                }
                return;
            }
        }
    }
}
