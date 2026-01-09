<?php
declare(strict_types=1);

namespace Din9xtrCloud;

use Din9xtrCloud\Contracts\ViewModel;
use Din9xtrCloud\ViewModels\BaseViewModel;
use Din9xtrCloud\ViewModels\LayoutViewModel;
use RuntimeException;
use Throwable;

final readonly class View
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? '/var/www/resources/views/';
    }

    public function render(ViewModel $viewModel): string
    {
        $html = $this->renderTemplate($viewModel);

        if ($viewModel instanceof BaseViewModel && $viewModel->layout()) {
            return $this->render(
                new LayoutViewModel(
                    content: $html,
                    page: $viewModel
                )
            );
        }

        return $html;
    }

    private function renderTemplate(ViewModel $viewModel): string
    {
        $file = $this->basePath
            . str_replace('.', '/', $viewModel->template())
            . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Template not found: $file");
        }

        ob_start();

        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException(
                "Render template error $file: " . $e->getMessage(),
                0,
                $e
            );
        }

        return (string)ob_get_clean();
    }

    public static function display(ViewModel $vm): string
    {
        static $instance;
        return ($instance ??= new self())->render($vm);
    }
}
