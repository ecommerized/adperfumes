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
        <x-slot name="heading">Email Marketing Subscribers</x-slot>
        <x-slot name="description">Customers who have opted in for email marketing. Export to CSV for import into Mailchimp, SendGrid, or other email platforms.</x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
