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
 * Embedded through the wizard's `View::make('filament.wizards.mapping-notice')`
 * blade wrapper, which renders `<livewire:wizards.import-mapping-notice>`
 * with a fresh `key` on every render — not through
 * `Filament\Schemas\Components\Livewire::make()`, which is deliberately
 * not used here: Livewire spoofs mounts of previously-rendered children
 * with an empty stub, and that wrapper's stable identity would re-render
 * this conditional child as a dead empty div (§28.3).
 */
class ImportMappingNotice extends Component
{
    public function render(): View
    {
        return view('livewire.wizards.import-mapping-notice');
    }
}
