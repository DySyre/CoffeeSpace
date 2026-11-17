<?php

return [
    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'sanitize_timeout' => (int) env('MEDIA_SANITIZE_TIMEOUT', 180),
];
