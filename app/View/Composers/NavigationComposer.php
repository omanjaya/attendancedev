<?php

namespace App\View\Composers;

use App\Services\NavigationService;
use Illuminate\View\View;

class NavigationComposer
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $view->with([
            'desktopNavigation' => $this->navigationService->getNavigation(),
            'mobileNavigation' => $this->navigationService->getMobileNavigation(),
        ]);
    }
}
