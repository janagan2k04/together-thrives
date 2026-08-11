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

    public static function renderError(int $code, string $message = ''): void
    {
        http_response_code($code);
        $file = APP_PATH . "/views/errors/{$code}.php";
        if (!is_file($file)) {
            $file = APP_PATH . '/views/errors/500.php';
        }
        require $file;
        exit;
    }
}
