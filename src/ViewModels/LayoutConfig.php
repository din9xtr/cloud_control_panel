<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels;
readonly class LayoutConfig
{
    public function __construct(
        public ?string $header = 'default',
        public bool    $showFooter = true,
        public ?string $layout = 'layouts/app',
    )
    {
    }
}
