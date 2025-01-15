<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models\Config\Backend;

/**
 * Class WoffTwo
 * Handles the configuration for uploading WOFF2 font files in the admin panel.
 */
class WoffTwo extends Font
{
    /**
     * Get the allowed extensions for uploaded files.
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        // Return an array of allowed file extensions for WOFF2 files
        return ['woff2'];
    }
}
