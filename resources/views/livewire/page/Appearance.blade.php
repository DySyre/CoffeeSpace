<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout heading="Appearance" subheading="Update your account's appearance settings">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="w-full max-w-full flex-col sm:flex-row">
            <flux:radio value="light" icon="sun" class="min-w-0">Warm Latte</flux:radio>
            <flux:radio value="dark" icon="moon" class="min-w-0">Dark Espresso</flux:radio>
            <flux:radio value="system" icon="computer-desktop" class="min-w-0">System</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</div>
