<?php

namespace App\Support;

/**
 * The hospital logo for printed documents (RIS, ICS, PAR, tags), as a data
 * URI so dompdf can draw it without fetching anything.
 */
final class DocumentLogo
{
    /**
     * `data:image/png;base64,…` of public/{MailLogo::PATH}, or null when the
     * file is missing.
     */
    public static function dataUri(): ?string
    {
        $path = public_path(MailLogo::PATH);

        if (! is_file($path) || ($contents = file_get_contents($path)) === false) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($contents);
    }
}
