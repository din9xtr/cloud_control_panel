<?php
declare(strict_types=1);

namespace Din9xtrCloud\Container;

use Closure;

final readonly class Definition
{
    public function __construct(
        public Closure $factory,
        public Scope   $scope
    )
    {
    }
}
