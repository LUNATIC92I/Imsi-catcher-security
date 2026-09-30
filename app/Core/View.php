<?php
declare(strict_types=1);

namespace App\Core;

/** Simple PHP template renderer with layout support + XSS-safe escaping. */
final class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $viewsPath = App::config('paths')['views'];
        $templateFile = $viewsPath . '/' . $template . '.php';

        if (!is_file($templateFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($template);
            return;
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        $layoutFile = $viewsPath . '/layouts/' . $layout . '.php';
        if (is_file($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /** XSS-safe output escape. */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Global escape shortcut for templates. */
function e(mixed $value): string
{
    return View::e($value);
}
