@props(['name', 'label', 'type' => 'text', 'autocomplete' => null])
@php
    $inputClass = 'h-11 w-full rounded-sm border border-line bg-surface text-base outline-none focus:border-brand '.($type === 'password' ? 'pl-3 pr-12' : 'px-3');
    $inputAttributes = $attributes->except('value')->merge(['class' => $inputClass]);
@endphp
<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-medium">{{ $label }}</label>
    @if($type === 'password')
        <div class="relative">
            <input id="{{ $name }}" name="{{ $name }}" type="password" @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif required aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @error($name) aria-describedby="{{ $name }}-error" @enderror {{ $inputAttributes }}>
            <button type="button" aria-label="Show password" title="Show password" aria-pressed="false" aria-controls="{{ $name }}" onclick="const input = this.parentElement.querySelector('input'); const visible = input.type === 'password'; input.type = visible ? 'text' : 'password'; this.setAttribute('aria-pressed', String(visible)); this.setAttribute('aria-label', visible ? 'Hide password' : 'Show password'); this.title = visible ? 'Hide password' : 'Show password'; this.querySelector('[data-eye]').hidden = visible; this.querySelector('[data-eye-off]').hidden = !visible;" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-sm text-muted hover:text-brand focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <span data-eye aria-hidden="true"><x-lucide-eye class="size-4"/></span>
                <span data-eye-off aria-hidden="true" hidden><x-lucide-eye-off class="size-4"/></span>
            </button>
        </div>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value ?? '') }}" @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif required aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @error($name) aria-describedby="{{ $name }}-error" @enderror {{ $inputAttributes }}>
    @endif
    @error($name)<p id="{{ $name }}-error" role="alert" class="text-xs text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
</div>
