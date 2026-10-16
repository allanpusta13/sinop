<?php

declare(strict_types=1);

namespace App\Filament\Resources\DirectTransfers\Pages;

use App\Exceptions\DomainRuleViolationException;
use App\Filament\Resources\DirectTransfers\DirectTransferResource;
use App\Filament\Resources\DirectTransfers\Schemas\DirectTransferForm;
use App\Models\DirectTransfer;
use App\Services\InventoryService;
use App\Support\GeneratesReferenceCodes;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Gate;

class CreateDirectTransfer extends CreateRecord
{
    use HasWizard;

    protected static string $resource = DirectTransferResource::class;

    public function getMaxContentWidth(): ?string
    {
        return Width::SevenExtraLarge->value;
    }

    /** @return array<Step> */
    protected function getSteps(): array
    {
        return [
            Step::make(__('resources.direct_transfers.steps.location_mapping'))
                ->description(__('resources.direct_transfers.steps.location_mapping_description'))
                ->icon(Heroicon::BuildingOffice)
                ->schema(DirectTransferForm::getLocationMappingFields()),

            Step::make(__('resources.direct_transfers.steps.stock_allocation'))
                ->description(__('resources.direct_transfers.steps.stock_allocation_description'))
                ->icon(Heroicon::Cube)
                ->schema(DirectTransferForm::getStockAllocationFields()),

            Step::make(__('resources.direct_transfers.steps.review_verify'))
                ->description(__('resources.direct_transfers.steps.review_verify_description'))
                ->icon(Heroicon::CheckCircle)
                ->schema([
                    View::make('filament.wizards.direct-transfer-review')
                        ->viewData(fn (Get $get): array => [
                            'state' => [
                                'from_warehouse_id' => $get('from_warehouse_id'),
                                'to_warehouse_id' => $get('to_warehouse_id'),
                                'items' => $get('items') ?? [],
                                'notes' => $get('notes'),
                            ],
                        ])
                        ->columnSpanFull(),
                ]),

        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        Gate::authorize('create', DirectTransfer::class);

        // §2 — collision retry: regenerate and re-insert on unique-constraint
        // violation, up to 5 attempts. The enclosing Filament create
        // transaction and the service's own nested transaction use savepoints,
        // so a failed insert rolls back only to the savepoint and the loop can
        // retry (ponytail: holds for MySQL/SQLite; on PostgreSQL a failed
        // statement aborts the transaction and each attempt would need its own
        // savepoint).
        $maxAttempts = 5;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $data['reference_code'] ??= GeneratesReferenceCodes::generateReferenceCode('DT');

            try {
                return app(InventoryService::class)->directTransfer(
                    fromWarehouseId: (int) $data['from_warehouse_id'],
                    toWarehouseId: (int) $data['to_warehouse_id'],
                    items: $data['items'] ?? [],
                    referenceCode: $data['reference_code'],
                    notes: $data['notes'],
                );
            } catch (UniqueConstraintViolationException) {
                $data['reference_code'] = null;
            }
        }

        throw new DomainRuleViolationException('errors.reference_code_exhausted', [
            'prefix' => 'DT',
            'attempts' => $maxAttempts,
        ]);
    }
}
