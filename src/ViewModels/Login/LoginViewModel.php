<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels\Login;

use Din9xtrCloud\ViewModels\BaseViewModel;
use Din9xtrCloud\ViewModels\LayoutConfig;

final readonly class LoginViewModel extends BaseViewModel
{
    public function __construct(
        string         $title,
        public ?string $error = null,
        public ?string $csrf = null,
    )
    {
        $layoutConfig = new LayoutConfig(
            header: null,
            showFooter: true,
        );
        parent::__construct($layoutConfig, $title);
    }

    public function template(): string
    {
        return 'login';
    }

    protected function data(): array
    {
        return [
            'error' => $this->error,
            'csrf' => $this->csrf,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}