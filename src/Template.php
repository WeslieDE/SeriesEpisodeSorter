<?php
class Template {
    private string $dir;

    public function __construct(string $dir) {
        $this->dir = rtrim($dir, '/');
    }

    public function render(string $name, array $vars = []): string {
        $file = "$this->dir/$name.php";
        if (!is_file($file)) {
            throw new InvalidArgumentException("Template $name not found");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}

// global helper functions
$__template_engine = null;
function template(string $name, array $vars = []): string {
    global $__template_engine;
    if (!$__template_engine) {
        $__template_engine = new Template(__DIR__ . '/../templates');
    }
    return $__template_engine->render($name, $vars);
}

function include_template(string $name, array $vars = []): void {
    echo template($name, $vars);
}
?>
