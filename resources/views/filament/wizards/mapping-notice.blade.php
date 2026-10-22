{{--
    Embeds the mapping-notice Livewire child inside the wizard schema.

    The key is regenerated on every render on purpose: Livewire spoofs
    mounts of previously-rendered children with an empty stub, and
    Filament's partial rendering keeps stale child keys alive across
    update cycles, which would otherwise re-render this conditional
    child as a dead empty div instead of its content.
--}}
<div>
    <livewire:wizards.import-mapping-notice key="mapping-notice-{{ str()->random(16) }}" />
</div>
