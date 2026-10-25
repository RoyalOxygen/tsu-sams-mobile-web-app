<?php
declare(strict_types=1);

namespace App;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $data['user'] = Auth::user();
        $data['flash_success'] = flash('success');
        $data['flash_error'] = flash('error');
        $data['flash_info'] = flash('info');
        extract($data, EXTR_SKIP);
        $viewFile = config('paths.views') . '/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        if ($layout) {
            include config('paths.views') . '/' . $layout . '.php';
        } else {
            echo $content;
        }
    }
}
