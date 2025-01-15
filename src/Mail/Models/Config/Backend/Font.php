<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models\Config\Backend;

use Magento\Config\Model\Config\Backend\File;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Font
 * Handles the configuration for uploading font files in the admin panel.
 */
class Font extends File
{
    /**
     * The tail part of the directory path for uploading.
     */
    public const UPLOAD_DIR = 'fonts';

    /**
     * Return path to the directory for uploading files.
     *
     * @throws LocalizedException
     *
     * @return string
     */
    protected function _getUploadDir()
    {
        // Retrieve the absolute path to the upload directory, appending scope info if necessary
        return $this->_mediaDirectory->getAbsolutePath(
            $this->_appendScopeInfo(self::UPLOAD_DIR),
        );
    }

    /**
     * Determine whether to add information about the scope to the upload directory path.
     *
     * @return bool
     */
    protected function _addWhetherScopeInfo()
    {
        // Always add scope info to the directory path
        return true;
    }
}
