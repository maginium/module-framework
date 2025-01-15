<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models\Config\Backend;

/**
 * Class Eot
 * Handles the configuration for uploading EOT font files in the admin panel.
 */
class Eot extends Font
{
    /**
     * Get the allowed extensions for uploaded files.
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        // Return an array of allowed file extensions for EOT files
        return ['eot'];
    }
}
