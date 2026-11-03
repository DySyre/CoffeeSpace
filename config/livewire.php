<?php

return [
    // Retain the starter kit's Blade layout location on Livewire 4.
    'component_layout' => 'components.layouts.app',
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,mp4,mov', 'extensions:jpg,jpeg,png,mp4,mov'],
        'preview_mimes' => ['jpg', 'jpeg', 'png', 'mp4', 'mov'],
    ],
];
