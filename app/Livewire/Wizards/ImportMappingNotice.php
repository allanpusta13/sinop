<?php

declare(strict_types=1);

namespace App\Livewire\Wizards;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * ImportMappingNotice — read-only notice rendered on the import
 * wizard's mapping step when no uploaded file (and therefore no
 * headers) is available.
 *
 * Embedded via `Filament\Schemas\Components\Livewire::make()`, the
 * same pattern `WarehouseForm` uses for `AssignedUsersList` in place
 * of the deprecated Placeholder components.
 */
class ImportMappingNotice extends Component
{
    public function render(): View
    {
        return view('livewire.wizards.import-mapping-notice');
    }
}
