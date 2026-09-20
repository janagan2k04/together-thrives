<?php
namespace Core;

abstract class Controller
{
    /** Render a view inside a layout. */
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        $file = APP_PATH . '/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new HttpException(500, "View {$view} not found.");
        }

        $data['flash']  = Session::pullFlash();
        $data['errors'] = Session::pullErrors();
        $data['old']    = Session::pullOld();
        $data['user']   = Auth::user();

        // Hand old input and errors to old() / error_for() / has_error(),
        // which cannot see the method-local variables extract() creates.
        form_state($data['old'], $data['errors']);

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        require APP_PATH . '/views/layouts/' . $layout . '.php';
    }

    /** Render a view with no layout (partials, modals, AJAX fragments). */
    protected function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require APP_PATH . '/views/' . str_replace('.', '/', $view) . '.php';
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function back(): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $ref);
        exit;
    }

    /** Validate or bounce back with errors + old input. */
    protected function validate(array $rules, ?array $data = null): array
    {
        $data      = $data ?? Request::all();
        $validator = new Validator($data, $rules);

        if (!$validator->passes()) {
            Session::flashInput($data, $validator->errors());
            Session::flash('error', 'Please correct the highlighted fields.');
            $this->back();
        }
        return $validator->validated();
    }

    protected function abort(int $code, string $message = ''): never
    {
        throw new HttpException($code, $message);
    }
}
