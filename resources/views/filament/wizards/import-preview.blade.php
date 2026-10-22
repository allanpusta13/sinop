{{-- resources/views/filament/wizards/import-preview.blade.php --}}
@php
    $counts = $preview['counts'] ?? ['create' => 0, 'update' => 0, 'reject' => 0, 'total' => 0];
    $rows = $preview['rows'] ?? [];
    $hasUpdates = $preview['has_updates'] ?? false;

    $badgeIcons = [
        'create' => \Filament\Support\Icons\Heroicon::Check,
        'update' => \Filament\Support\Icons\Heroicon::PencilSquare,
        'reject' => \Filament\Support\Icons\Heroicon::XMark,
    ];

    /**
     * Row-level error reasons may be translation keys with parameters
     * (preferred, per §0A.10) or plain strings during migration.
     */
    $resolveError = static function ($error): string {
        if (is_array($error)) {
            return __($error['key'] ?? '', $error['params'] ?? []);
        }

        return (string) $error;
    };
@endphp

<div class="space-y-4">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-1.5 text-xs text-zinc-500 uppercase tracking-wider"><x-filament::icon :icon="\Filament\Support\Icons\Heroicon::RectangleStack" class="h-4 w-4 shrink-0 text-zinc-400 dark:text-zinc-500" />{{ __('imports.preview.total_rows') }}</div>
            <div class="text-xl font-bold text-zinc-800 dark:text-zinc-100">{{ $counts['total'] }}</div>
        </div>
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold"><x-filament::icon :icon="\Filament\Support\Icons\Heroicon::CheckCircle" class="h-4 w-4 shrink-0 text-emerald-500 dark:text-emerald-400" />{{ __('imports.preview.to_create') }}</div>
            <div class="text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ $counts['create'] }}</div>
        </div>
        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-lg border border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold"><x-filament::icon :icon="\Filament\Support\Icons\Heroicon::PencilSquare" class="h-4 w-4 shrink-0 text-amber-500 dark:text-amber-400" />{{ __('imports.preview.to_update') }}</div>
            <div class="text-xl font-bold text-amber-700 dark:text-amber-300">{{ $counts['update'] }}</div>
        </div>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 rounded-lg border border-rose-200 dark:border-rose-800">
            <div class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 uppercase tracking-wider font-semibold"><x-filament::icon :icon="\Filament\Support\Icons\Heroicon::XCircle" class="h-4 w-4 shrink-0 text-rose-500 dark:text-rose-400" />{{ __('imports.preview.to_reject') }}</div>
            <div class="text-xl font-bold text-rose-700 dark:text-rose-300">{{ $counts['reject'] }}</div>
        </div>
    </div>

    {{-- Update Warning --}}
    @if ($hasUpdates)
        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-lg border border-amber-200 dark:border-amber-800 flex items-start gap-2">
            <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::ExclamationTriangle" class="h-4 w-4 shrink-0 mt-0.5 text-amber-500 dark:text-amber-400" />
            <div>
                <div class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">{{ __('imports.preview.update_warning_title') }}</div>
                <div class="text-sm text-amber-800 dark:text-amber-200">{{ __('imports.preview.update_warning_body', ['count' => $counts['update']]) }}</div>
            </div>
        </div>
    @endif

    {{-- Row-level details --}}
    @if (count($rows) > 0)
        @if ($preview['rows_truncated'] ?? false)
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-start gap-2">
                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::InformationCircle" class="h-4 w-4 shrink-0 mt-0.5 text-zinc-500 dark:text-zinc-400" />
                <div class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('imports.preview.rows_truncated', ['shown' => count($rows), 'total' => $counts['total']]) }}</div>
            </div>
        @endif
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('imports.preview.col_row') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('imports.preview.col_status') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('imports.preview.col_identifier') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('imports.preview.col_details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($rows as $row)
                        @php
                            $status = $row['status'] ?? 'reject';
                            $statusClasses = match ($status) {
                                'create' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                'update' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                                default => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
                            };
                            $statusLabel = match ($status) {
                                'create' => __('imports.preview.badge_create'),
                                'update' => __('imports.preview.badge_update'),
                                default => __('imports.preview.badge_reject'),
                            };
                        @endphp
                        <tr class="{{ $status === 'reject' ? 'bg-rose-50/50 dark:bg-rose-950/20' : '' }}">
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $row['row_number'] ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                    <x-filament::icon :icon="$badgeIcons[$status] ?? $badgeIcons['reject']" class="h-3 w-3 shrink-0" />
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $row['identifier'] ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if (! empty($row['errors']))
                                    <ul class="list-disc list-inside text-rose-600 dark:text-rose-400 space-y-0.5">
                                        @foreach ($row['errors'] as $error)
                                            <li>{{ $resolveError($error) }}</li>
                                        @endforeach
                                    </ul>
                                @elseif ($status === 'create')
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ __('imports.preview.msg_ready_create') }}</span>
                                @elseif ($status === 'update')
                                    <span class="text-amber-600 dark:text-amber-400">{{ __('imports.preview.msg_ready_update') }}</span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-3 text-center text-zinc-500 dark:text-zinc-400 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-lg">
            {{ __('imports.preview.no_rows') }}
        </div>
    @endif
</div>
