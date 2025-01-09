<?php

declare(strict_types=1);

namespace Maginium\Framework\Resize\Interfaces;

use Maginium\Foundation\Exceptions\Exception;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Resizer class handles the process of opening, resizing, and saving images.
 * It also supports various image manipulations such as quality adjustments, cropping, and format conversion.
 */
interface ResizerInterface
{
    /**
     * open is a static constructor that returns a new instance of Resizer.
     *
     * @param  mixed  $file  The image file or path to an image.
     *
     * @return self Returns a new Resizer instance.
     */
    public static function open($file): self;

    /**
     * save saves the image to a specified path based on its file type.
     *
     * @param  string  $savePath  The path where the image will be saved.
     *
     * @throws Exception If an invalid image type is provided.
     */
    public function save($savePath);

    /**
     * reset resets the image back to the original state.
     *
     * @return self Returns the Resizer instance for method chaining.
     */
    public function reset(): self;

    /**
     * setOptions sets the resizer options. Available options are:
     *  - mode: Defines the resizing mode. Options are 'exact', 'portrait', 'landscape', 'auto', 'fit', 'crop'.
     *  - offset: The offset of the crop, represented as an array [left, top].
     *  - sharpen: The sharpness of the image, ranging from 0 to 100 (default: 0).
     *  - interlace: Boolean value indicating whether interlacing is enabled. Default is false (disabled).
     *  - quality: The image quality, ranging from 0 to 100 (default: 90).
     *
     * @param  array  $options  Associative array of options to configure the image resizing behavior.
     *
     * @return self Returns the current instance of the resizer with updated options.
     */
    public function setOptions(array $options): self;

    /**
     * resize and/or crop an image to the given dimensions while preserving the options set for the image.
     *
     * @param  int  $newWidth  The new width of the image.
     * @param  int  $newHeight  The new height of the image.
     * @param  array  $options  Additional resizing options, such as mode or offset.
     *
     * @return self Returns the instance with the resized (and optionally cropped) image.
     */
    public function resize($newWidth, $newHeight, $options = []): self;

    /**
     * sharpen the image to a specified level of sharpness, from 0 to 100.
     *
     * @param  int  $sharpness  The sharpness level, between 0 (no sharpening) and 100 (maximum sharpness).
     *
     * @return self Returns the current instance with the sharpened image.
     */
    public function sharpen($sharpness): self;

    /**
     * Crops an image from its center.
     *
     * @param  int  $cropStartX  Start position on the X axis for the crop
     * @param  int  $cropStartY  Start position on the Y axis for the crop
     * @param  int  $newWidth  The desired width of the cropped image
     * @param  int  $newHeight  The desired height of the cropped image
     * @param  int  $srcWidth  Source width of the area to crop, defaults to $newWidth if not provided
     * @param  int  $srcHeight  Source height of the area to crop, defaults to $newHeight if not provided
     */
    public function crop($cropStartX, $cropStartY, $newWidth, $newHeight, $srcWidth = null, $srcHeight = null): self;
}
