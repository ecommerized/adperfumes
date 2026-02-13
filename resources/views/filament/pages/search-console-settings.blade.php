<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="flex items-center gap-3 mt-6">
            <x-filament-panels::form.actions
                :actions="$this->getFormActions()"
            />

            <x-filament::button
                type="button"
                color="gray"
                wire:click="testConnection"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                <span wire:loading wire:target="testConnection">Testing...</span>
            </x-filament::button>
        </div>
    </form>

    @if($connectionTested && $connectionStatus === 'success' && !empty($availableSites))
        <x-filament::section class="mt-6">
            <x-slot name="heading">Available Sites</x-slot>
            <p class="text-sm text-gray-500 mb-3">These are the sites your service account can access. Make sure the site URL above matches one of these:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($availableSites as $site)
                    <li class="font-mono text-xs">{{ $site }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
