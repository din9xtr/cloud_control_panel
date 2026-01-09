<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels;

use Din9xtrCloud\Contracts\ViewModel;

abstract readonly class BaseViewModel implements ViewModel
{
    public function __construct(
        public LayoutConfig $layoutConfig,
        public string       $title,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'layoutConfig' => $this->layoutConfig,
            ...$this->data()
        ];
    }

    public function layout(): ?string
    {
        return $this->layoutConfig->layout;
    }

    /**
     * @return array<string, mixed>
     */
    protected function data(): array
    {
        return [];
    }

    abstract public function title(): string;

    abstract public function template(): string;
}