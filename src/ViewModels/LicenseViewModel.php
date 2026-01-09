<?php

namespace Din9xtrCloud\ViewModels;

final readonly class LicenseViewModel extends BaseViewModel
{
    /**
     * @param string $title
     */
    public function __construct(
        string $title,

    )
    {
        $layoutConfig = new LayoutConfig(
//            header: null,
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
        return 'license';
    }
}