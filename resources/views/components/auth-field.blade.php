@props(['name', 'label', 'type' => 'text', 'autocomplete' => null])
<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-medium">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if($type !== 'password') value="{{ old($name, $value ?? '') }}" @endif @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif required aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @error($name) aria-describedby="{{ $name }}-error" @enderror {{ $attributes->except('value')->merge(['class' => 'h-11 w-full rounded-sm border border-line bg-surface px-3 text-base outline-none focus:border-brand']) }}>
    @error($name)<p id="{{ $name }}-error" role="alert" class="text-xs text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
</div>
