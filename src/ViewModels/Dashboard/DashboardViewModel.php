<?php
declare(strict_types=1);

namespace Din9xtrCloud\ViewModels\Dashboard;

use Din9xtrCloud\ViewModels\BaseViewModel;
use Din9xtrCloud\ViewModels\LayoutConfig;

final readonly class DashboardViewModel extends BaseViewModel
{
    /**
     * @param string $title
     * @param string $username
     * @param array<string, mixed> $stats
     * @param string|null $csrf
     */
    public function __construct(
        string         $title,
        public string  $username,
        public array   $stats = [],
        public ?string $csrf = null,

    )
    {
        $layoutConfig = new LayoutConfig(
            header: 'dashboard',
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
        return 'dashboard';
    }

    protected function data(): array
    {
        return [
            'username' => $this->username,
            'stats' => $this->stats,
            'csrf' => $this->csrf,
        ];
    }
}