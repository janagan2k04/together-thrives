<?php
namespace Core;

class App
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        Config::set($config);
    }

    public function run(): void
    {
        Session::start();
        Database::init($this->config['db']);

        $router = new Router();
        foreach (glob(CONFIG_PATH . '/routes/*.php') as $file) {
            (require $file)($router);
        }

        try {
            $router->dispatch(Request::uri(), Request::method());
        } catch (HttpException $e) {
            self::renderError($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            if (Config::get('debug')) {
                throw $e;
            }
            self::renderError(500, 'Something went wrong.');
        }
    }

    /**
     * Status codes PHP has no reason phrase for. http_response_code() silently
     * turns those into 500 — which is how a CSRF failure, thrown as 419, was
     * reaching the browser as "500 Internal Server Error" while still rendering
     * the 419 page. Send the status line ourselves for these.
     */
    private const REASONS = [419 => 'Authentication Timeout'];

    public static function renderError(int $code, string $message = ''): void
    {
        if (isset(self::REASONS[$code])) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            header("{$protocol} {$code} " . self::REASONS[$code]);
        } else {
            http_response_code($code);
        }
        $file = APP_PATH . "/views/errors/{$code}.php";
        if (!is_file($file)) {
            $file = APP_PATH . '/views/errors/500.php';
        }
        require $file;
        exit;
    }
}
