<?php

namespace Din9xtrCloud\Controllers;

use Din9xtrCloud\View;
use Din9xtrCloud\ViewModels\LicenseViewModel;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LicenseController
{
    public function license(ServerRequestInterface $request): string
    {
        return View::display(new LicenseViewModel('License'));
    }
}