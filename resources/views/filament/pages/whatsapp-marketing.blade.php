<x-filament-panels::page>
    {{-- Settings Form --}}
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </form>

    {{-- Opted-In Contacts Table --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">WhatsApp Opted-In Contacts</x-slot>
        <x-slot name="description">Customers who have opted in for WhatsApp marketing messages. Select and export numbers for bulk messaging tools.</x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
