<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class MatchingPhotoRatios implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $firstRatio = null;
        foreach ($value as $file) {
            if (! $file instanceof UploadedFile || ! in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }
            $size = @getimagesize($file->getRealPath());
            if (! $size || $size[1] === 0) {
                continue;
            }
            $ratio = $size[0] / $size[1];
            if ($firstRatio !== null && abs($ratio - $firstRatio) > 0.005) {
                $fail('All photos in this post must use the same crop shape as the first photo.');

                return;
            }
            $firstRatio ??= $ratio;
        }
    }
}
