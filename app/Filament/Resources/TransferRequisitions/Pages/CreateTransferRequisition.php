<?php

declare(strict_types=1);

namespace App\Filament\Resources\TransferRequisitions\Pages;

use App\Enums\TransferRequisitionStatus;
use App\Exceptions\DomainRuleViolationException;
use App\Filament\Resources\TransferRequisitions\Schemas\TransferRequisitionForm;
use App\Filament\Resources\TransferRequisitions\TransferRequisitionResource;
use App\Models\TransferRequisition;
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

class CreateTransferRequisition extends CreateRecord
{
    use HasWizard;

    protected static string $resource = TransferRequisitionResource::class;

    public function getMaxContentWidth(): ?string
    {
        return Width::SevenExtraLarge->value;
    }

    /** @return array<Step> */
    protected function getSteps(): array
    {
        return [
            Step::make(__('resources.transfer_requisitions.steps.routing'))
                ->description(__('resources.transfer_requisitions.steps.routing_description'))
                ->icon(Heroicon::BuildingOffice)
                ->schema(TransferRequisitionForm::getRoutingFields()),

            Step::make(__('resources.transfer_requisitions.steps.manifest'))
                ->description(__('resources.transfer_requisitions.steps.manifest_description'))
                ->icon(Heroicon::ClipboardDocumentList)
                ->schema(TransferRequisitionForm::getMaterialManifestFields()),

            Step::make(__('resources.transfer_requisitions.steps.review'))
                ->description(__('resources.transfer_requisitions.steps.review_description'))
                ->icon(Heroicon::CheckCircle)
                ->schema([
                    View::make('filament.wizards.transfer-review')
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference_code'] = $data['reference_code']
            ?? GeneratesReferenceCodes::generateReferenceCode('TR');
        $data['requested_by'] = auth()->id();
        $data['status'] = TransferRequisitionStatus::Draft->value;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        Gate::authorize('create', TransferRequisition::class);

        // §2 — collision retry: regenerate and re-insert on unique-constraint
        // violation, up to 5 attempts. The enclosing Filament create
        // transaction uses savepoints, so a failed insert rolls back only to
        // the savepoint and the loop can retry (ponytail: holds for
        // MySQL/SQLite; on PostgreSQL each attempt would need its own
        // savepoint).
        $maxAttempts = 5;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return parent::handleRecordCreation($data);
            } catch (UniqueConstraintViolationException) {
                $data['reference_code'] = GeneratesReferenceCodes::generateReferenceCode('TR');
            }
        }

        throw new DomainRuleViolationException('errors.reference_code_exhausted', [
            'prefix' => 'TR',
            'attempts' => $maxAttempts,
        ]);
    }
}
