<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels;

use Din9xtrCloud\Contracts\ViewModel;
use RuntimeException;

final readonly class LayoutViewModel implements ViewModel
{
    public function __construct(
        public string        $content,
        public BaseViewModel $page,
    )
    {
    }

    public function template(): string
    {
        $layout = $this->page->layout();

        if ($layout === null) {
            throw new RuntimeException(
                'LayoutViewModel requires page to have a layout, but layout() returned null'
            );
        }

        return $layout;
    }

    public function layout(): ?string
    {
        return null;
    }

    public function toArray(): array
    {
        return [];
    }

    public function title(): ?string
    {
        return null;
    }
}
