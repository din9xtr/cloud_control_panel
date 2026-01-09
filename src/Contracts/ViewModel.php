<?php

declare(strict_types=1);

namespace Din9xtrCloud\Contracts;
interface ViewModel
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    public function template(): string;

    public function layout(): ?string;

    public function title(): ?string;
}