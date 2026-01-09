<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels\Folder;

use Din9xtrCloud\ViewModels\BaseViewModel;
use Din9xtrCloud\ViewModels\LayoutConfig;

final readonly class FolderViewModel extends BaseViewModel
{
    /**
     * @param array<int, array{name: string, size: string, modified: string}> $files
     */
    public function __construct(
        string         $title,
        public array   $files = [],
        public ?string $csrf = null,
        public ?string $totalSize = null,
        public ?string $lastModified = null,
    )
    {
        $layoutConfig = new LayoutConfig(
            header: 'folder',
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
        return 'folder';
    }

}