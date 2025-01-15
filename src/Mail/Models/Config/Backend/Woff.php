<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models\Config\Backend;

/**
 * Class Woff
 * Handles the configuration for uploading WOFF font files in the admin panel.
 */
class Woff extends Font
{
    /**
     * Get the allowed extensions for uploaded files.
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        // Return an array of allowed file extensions for WOFF files
        return ['woff'];
    }
}
