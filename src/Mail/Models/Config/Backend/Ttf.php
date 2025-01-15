<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models\Config\Backend;

/**
 * Class Ttf
 * Handles the configuration for uploading TTF font files in the admin panel.
 */
class Ttf extends Font
{
    /**
     * Get the allowed extensions for uploaded files.
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        // Return an array of allowed file extensions for TTF files
        return ['ttf'];
    }
}
