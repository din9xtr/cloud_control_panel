<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels\Errors;

use Din9xtrCloud\ViewModels\BaseViewModel;
use Din9xtrCloud\ViewModels\LayoutConfig;

final readonly class ErrorViewModel extends BaseViewModel
{
    public function __construct(
        string        $title,
        public string $errorCode,
        public string $message,
    )
    {
        $layoutConfig = new LayoutConfig(
            header: null,
            showFooter: false,
        );

        parent::__construct($layoutConfig, $title);
    }

    public function template(): string
    {
        return 'error';
    }

    protected function data(): array
    {
        return [
            'errorCode' => $this->errorCode,
            'message' => $this->message,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}