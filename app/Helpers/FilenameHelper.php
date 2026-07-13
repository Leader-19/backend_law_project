<?php

use Illuminate\Support\Str;

if (! function_exists('safe_filename')) {
    /**
     * Generate a safe, unique filename (especially for Khmer/Unicode filenames).
     */
    function safe_filename($originalName, $prefix = '')
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $name = pathinfo($originalName, PATHINFO_FILENAME);

        // Transliterate to ASCII and slugify; non-Latin names collapse to empty.
        $clean = Str::slug(Str::ascii($name), '_');

        // Khmer and other non-Latin names have no ASCII equivalent - fall back
        // to a short hash so the generated name is still unique and safe.
        if ($clean === '') {
            $clean = substr(md5($name), 0, 12);
        }

        // time() + random guarantees uniqueness even for same-second uploads.
        $unique = time().'_'.Str::random(8);
        $prefix = $prefix !== '' ? $prefix.'_' : '';

        $filename = "{$unique}_{$prefix}{$clean}";

        return $extension !== '' ? "{$filename}.{$extension}" : $filename;
    }
}
