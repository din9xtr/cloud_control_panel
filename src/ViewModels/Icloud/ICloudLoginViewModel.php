<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels\Icloud;

use Din9xtrCloud\ViewModels\BaseViewModel;
use Din9xtrCloud\ViewModels\LayoutConfig;

final readonly class ICloudLoginViewModel extends BaseViewModel
{
    public function __construct(
        string         $title,
        public ?string $csrf = null,
        public bool    $show2fa = false,
        public string  $error = '',
        public string  $appleId = ''
    )
    {
        $layoutConfig = new LayoutConfig(
            header: null,
            showFooter: true,
        );
        parent::__construct($layoutConfig, $title);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function template(): string
    {
        return 'login_icloud';
    }
}