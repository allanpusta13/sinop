<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\InventoryServiceProvider;

return [
    AppServiceProvider::class,
    InventoryServiceProvider::class,
    AdminPanelProvider::class,
];
