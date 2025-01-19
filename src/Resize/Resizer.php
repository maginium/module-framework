<?php

declare(strict_types=1);

namespace Maginium\Framework\Resize;

use GdImage;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Resize\Interfaces\ResizerInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Validator;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\File as FileObj;

/**
 * Resizer class handles the process of opening, resizing, and saving images.
 * It also supports various image manipulations such as quality adjustments, cropping, and format conversion.
 */
class Resizer implements ResizerInterface
{
    /**
     * @var string extension of the uploaded file
     */
    protected string $extension;

    /**
     * @var string mime type of the uploaded file
     */
    protected string $mime;

    /**
     * @var int width of the original image being resized
     */
    protected int $width;

    /**
     * @var int height of the original image being resized
     */
    protected int $height;

    /**
     * @var int|null orientation (Exif) of image
     */
    protected ?int $orientation;

    /**
     * @var array options used for resizing
     */
    protected array $options = [];

    /**
     * @var FileObj file the symfony uploaded file object
     */
    protected FileObj $file;

    /**
     * @var GdImage image (on disk) that's being resized
     */
    protected GdImage $image;

    /**
     * @var GdImage originalImage cached
     */
    protected GdImage $originalImage;

    /**
     * __construct instantiates the Resizer and receives the path to an image we're working with.
     * The file can be either Input::file('field_name') or a path to a file.
     *
     * @param  mixed  $file  The image file or path to an image.
     *
     * @throws Exception If the GD extension is not loaded or the file is empty.
     */
    public function __construct($file)
    {
        // Ensure GD extension is loaded for image manipulation
        if (! extension_loaded('gd')) {
            echo 'GD PHP library required.' . PHP_EOL;

            exit(1);  // Exit if GD is not available
        }

        // Check if file is provided
        if (! $file) {
            throw Exception::make('Opened resizer on an empty file');
        }

        // If the file is a string (path), convert it to a FileObj instance
        if (Validator::isString($file)) {
            $file = new FileObj($file);
        }

        // Store the file object
        $this->file = $file;

        // Get the file's extension and mime type
        $this->extension = $file->guessExtension();
        $this->mime = $file->getMimeType();

        // Open the image using the provided file
        $this->image = $this->originalImage = $this->openImage($file);

        // Get image orientation
        $this->orientation = $this->getOrientation($file);

        // Get image dimensions
        $this->width = $this->getWidth();
        $this->height = $this->getHeight();

        // Set default options for resizing
        $this->setOptions([]);
    }

    /**
     * open is a static constructor that returns a new instance of Resizer.
     *
     * @param  mixed  $file  The image file or path to an image.
     *
     * @return self Returns a new Resizer instance.
     */
    public static function open($file): self
    {
        return Container::make(self::class, ['file' => $file]);
    }

    /**
     * save saves the image to a specified path based on its file type.
     *
     * @param  string  $savePath  The path where the image will be saved.
     *
     * @throws Exception If an invalid image type is provided.
     */
    public function save($savePath)
    {
        // Get the image resource to save
        $image = $this->image;

        // Get the quality option for saving the image
        $imageQuality = $this->getOption('quality');

        // Ensure image quality is within valid bounds (0-100)
        $imageQuality = max(min($imageQuality, 100), 0);

        // Apply interlacing if enabled
        if ($this->getOption('interlace')) {
            imageinterlace($image, true);
        }

        // Determine the extension of the file to save
        $extension = pathinfo($savePath, PATHINFO_EXTENSION) ?: $this->extension;

        // Switch based on the file extension and save accordingly
        switch (mb_strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                // Check if JPG support is enabled
                if (imagetypes() & IMG_JPG) {
                    $width = imagesx($image);
                    $height = imagesy($image);

                    // Create a canvas for JPG image with white background
                    $imageCanvas = imagecreatetruecolor($width, $height);
                    $white = imagecolorallocate($imageCanvas, 255, 255, 255);
                    imagefill($imageCanvas, 0, 0, $white);

                    // Copy the image onto the canvas and save as JPG
                    imagecopy($imageCanvas, $image, 0, 0, 0, 0, $width, $height);
                    imagejpeg($imageCanvas, $savePath, $imageQuality);
                }

                break;

            case 'gif':
                // Check if GIF support is enabled
                if (imagetypes() & IMG_GIF) {
                    imagegif($image, $savePath);
                }

                break;

            case 'png':
                // Convert quality from 0-100 to 0-9 for PNG
                $scaleQuality = round(($imageQuality / 100) * 9);

                // Invert quality as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;

                // Check if PNG support is enabled
                if (imagetypes() & IMG_PNG) {
                    imagepng($image, $savePath, (int)$invertScaleQuality);
                }

                break;

            case 'webp':
                // Check if WEBP support is enabled
                if (imagetypes() & IMG_WEBP) {
                    imagewebp($image, $savePath, $imageQuality);
                }

                break;

            default:
                // If the extension is invalid, throw an exception
                throw Exception::make(sprintf(
                    'Invalid image type: %s. Accepted types: jpg, gif, png, webp.',
                    $extension,
                ));
        }

        // Clean up by destroying the image resource
        imagedestroy($image);
    }

    /**
     * reset resets the image back to the original state.
     *
     * @return self Returns the Resizer instance for method chaining.
     */
    public function reset(): self
    {
        // Reset the image to the original
        $this->image = $this->originalImage;

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setOptions(array $options): self
    {
        // Merge user-provided options with the default values
        $this->options = Arr::merge([
            'mode' => 'auto', // Default mode is 'auto' (preserves aspect ratio and fits within given dimensions)
            'offset' => [0, 0], // Default crop offset is [0, 0]
            'sharpen' => 0, // Default sharpen value is 0 (no sharpening)
            'interlace' => false, // Default interlace is false (no interlacing)
            'quality' => 90, // Default quality is 90
        ], $options);

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * resize and/or crop an image to the given dimensions while preserving the options set for the image.
     *
     * @param  int  $newWidth  The new width of the image.
     * @param  int  $newHeight  The new height of the image.
     * @param  array  $options  Additional resizing options, such as mode or offset.
     *
     * @return self Returns the instance with the resized (and optionally cropped) image.
     */
    public function resize($newWidth, $newHeight, $options = []): self
    {
        // Set options for resizing, with potential overrides from the provided options
        $this->setOptions($options);

        // Sanitize and cast width and height to integers
        $newWidth = (int)$newWidth;
        $newHeight = (int)$newHeight;

        // If neither width nor height are provided, default to original dimensions
        if (! $newWidth && ! $newHeight) {
            $newWidth = $this->width;
            $newHeight = $this->height;
        } elseif (! $newWidth) { // If width is not provided, calculate it based on the height
            $newWidth = $this->getSizeByFixedHeight($newHeight);
        } elseif (! $newHeight) { // If height is not provided, calculate it based on the width
            $newHeight = $this->getSizeByFixedWidth($newWidth);
        }

        // Get optimal dimensions based on the resizing mode
        [$optimalWidth, $optimalHeight] = $this->getDimensions($newWidth, $newHeight);

        // Get the rotated original image based on EXIF orientation data (for correcting image rotation)
        $rotatedOriginal = $this->getRotatedOriginal();

        // If the image is a GIF, use imagescale for better results
        if ($this->mime === 'image/gif') {
            $imageResized = imagescale($rotatedOriginal, (int)$optimalWidth, (int)$optimalHeight, IMG_NEAREST_NEIGHBOUR);

            // Retain transparency for GIFs
            $this->retainImageTransparency($imageResized);
        } else {
            // For other image types, create a truecolor image and resample it
            $imageResized = imagecreatetruecolor((int)$optimalWidth, (int)$optimalHeight);

            // Retain transparency for formats like PNG
            $this->retainImageTransparency($imageResized);

            // Resample the image to fit the new dimensions
            imagecopyresampled(
                $imageResized,
                $rotatedOriginal,
                0,
                0,
                0,
                0,
                (int)$optimalWidth,
                (int)$optimalHeight,
                (int)$this->width,
                (int)$this->height,
            );
        }

        // Set the resized image as the current image
        $this->image = $imageResized;

        // Apply sharpening if specified
        if ($sharpen = $this->getOption('sharpen')) {
            $this->sharpen($sharpen);
        }

        // If the mode is 'crop', crop the image to the specified size and position
        if ($this->getOption('mode') === 'crop') {
            $offset = $this->getOption('offset');
            $cropStartX = ($optimalWidth / 2) - ($newWidth / 2) - $offset[0];
            $cropStartY = ($optimalHeight / 2) - ($newHeight / 2) - $offset[1];
            $this->crop($cropStartX, $cropStartY, $newWidth, $newHeight);
        }

        // Return the instance for method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * sharpen the image to a specified level of sharpness, from 0 to 100.
     *
     * @param  int  $sharpness  The sharpness level, between 0 (no sharpening) and 100 (maximum sharpness).
     *
     * @return self Returns the current instance with the sharpened image.
     */
    public function sharpen($sharpness): self
    {
        // If sharpness is out of range (0-100), return the image unmodified
        if ($sharpness <= 0 || $sharpness > 100) {
            // Return the current instance to allow method chaining
            return $this;
        }

        // Get the current image resource
        $image = $this->image;

        // Normalize the sharpness value to a suitable kernel center value
        $kernelCenter = exp((80 - ((float)$sharpness)) / 18) + 9;

        // Define the sharpening kernel matrix
        $matrix = [
            [-1, -1, -1],
            [-1, $kernelCenter, -1],
            [-1, -1, -1],
        ];

        // Calculate the divisor for normalization (the sum of all matrix elements)
        $divisor = Arr::sum(Arr::map($matrix, 'Arr::sum'));

        // Apply convolution to sharpen the image
        imageconvolution($image, $matrix, $divisor, 0);

        // Set the sharpened image as the current image
        $this->image = $image;

        // Return the instance with the sharpened image for method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function crop($cropStartX, $cropStartY, $newWidth, $newHeight, $srcWidth = null, $srcHeight = null): self
    {
        $image = $this->image;

        // If source width or height is not provided, default to new width and height
        if ($srcWidth === null) {
            $srcWidth = $newWidth;
        }

        if ($srcHeight === null) {
            $srcHeight = $newHeight;
        }

        // Create a new canvas with the specified width and height for cropping
        $imageResized = imagecreatetruecolor((int)$newWidth, (int)$newHeight);
        $this->retainImageTransparency($imageResized);

        // Crop the image based on the specified parameters
        imagecopyresampled(
            $imageResized,
            $image,
            0,
            0,
            (int)$cropStartX,
            (int)$cropStartY,
            (int)$newWidth,
            (int)$newHeight,
            (int)$srcWidth,
            (int)$srcHeight,
        );

        // Store the cropped image
        $this->image = $imageResized;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * retainImageTransparency manipulates an image resource in order to keep
     * transparency for PNG and GIF files.
     */
    protected function retainImageTransparency($img)
    {
        // Check if the image resource is valid
        if (! $img) {
            return;
        }

        // Handle transparency for GIF images
        if ($this->mime === 'image/gif') {
            // Define the default alpha color (fully transparent)
            $alphaColor = ['red' => 0, 'green' => 0, 'blue' => 0];

            // Get the transparent color index for GIF images
            $alphaIndex = imagecolortransparent($img);

            // If a transparent color index is found, retrieve the associated color values
            if ($alphaIndex >= 0) {
                $alphaColor = imagecolorsforindex($img, $alphaIndex);
            }

            // Allocate a transparent color using the extracted alpha values
            $alphaIndex = imagecolorallocatealpha($img, $alphaColor['red'], $alphaColor['green'], $alphaColor['blue'], 127);

            // Fill the image with the transparent color to apply transparency
            imagefill($img, 0, 0, $alphaIndex);

            // Set the image's transparent color to the newly allocated transparent color
            imagecolortransparent($img, $alphaIndex);
        }
        // Handle transparency for PNG and WEBP images
        elseif ($this->mime === 'image/png' || $this->mime === 'image/webp') {
            // Disable alpha blending to retain transparency
            imagealphablending($img, false);

            // Enable saving of alpha channel (transparency) for PNG and WEBP images
            imagesavealpha($img, true);
        }
    }

    /**
     * Sets a specific option for the image resizer.
     *
     * @param  string  $option  Option name to set (e.g., 'mode', 'quality', etc.)
     * @param  mixed  $value  The value to assign to the option
     */
    protected function setOption($option, $value): self
    {
        $this->options[$option] = $value;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Retrieves a specific option from the image resizer.
     *
     * @param  string  $option  The option name to retrieve
     *
     * @return mixed The value of the requested option
     */
    protected function getOption($option)
    {
        return Arr::get($this->options, $option);
    }

    /**
     * Retrieves the image's EXIF orientation (if available) for proper image rotation.
     *
     * @param  File  $file  The image file to check for EXIF data
     *
     * @return int|null The EXIF orientation value or null if not available
     */
    protected function getOrientation($file)
    {
        // Get the file path from the provided File instance
        $filePath = $file->getPathname();

        // Only process JPEG images and if the exif_read_data function exists
        if ($this->mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return;
        }

        // Attempt to read EXIF data from the image file
        $exif = @exif_read_data($filePath);

        // Check if the EXIF data contains an 'Orientation' key
        if (! isset($exif['Orientation'])) {
            return;
        }

        // Only consider certain rotation orientations (no mirroring)
        if (! in_array($exif['Orientation'], [1, 3, 6, 8], true)) {
            return;
        }

        // Return the EXIF orientation value (1, 3, 6, or 8)
        return $exif['Orientation'];
    }

    /**
     * Retrieves the image's width, accounting for any rotation defined by EXIF orientation.
     *
     * @return int The width of the image after considering EXIF orientation
     */
    protected function getWidth()
    {
        // Switch based on the image's EXIF orientation
        switch ($this->orientation) {
            case 6: // Rotate 270 degrees
            case 8: // Rotate 90 degrees
                // Height becomes the width after rotation
                return imagesy($this->image);

            case 1: // No rotation
            case 3: // Rotate 180 degrees
            default:
                // Normal width
                return imagesx($this->image);
        }
    }

    /**
     * Retrieves the image's height, accounting for any rotation defined by EXIF orientation.
     *
     * @return int The height of the image after considering EXIF orientation
     */
    protected function getHeight()
    {
        // Switch based on the image's EXIF orientation
        switch ($this->orientation) {
            case 6: // Rotate 270 degrees
            case 8: // Rotate 90 degrees
                // Width becomes the height after rotation
                return imagesx($this->image);

            case 1: // No rotation
            case 3: // Rotate 180 degrees
            default:
                // Normal height
                return imagesy($this->image);
        }
    }

    /**
     * Returns the rotated version of the original image according to EXIF orientation.
     *
     * @return GdImage The rotated image resource
     */
    protected function getRotatedOriginal()
    {
        // Set the rotation angle based on the EXIF orientation value
        switch ($this->orientation) {
            case 6: // Rotate 270 degrees
                $angle = 270.0;

                break;

            case 8: // Rotate 90 degrees
                $angle = 90.0;

                break;

            case 3: // Rotate 180 degrees
                $angle = 180.0;

                break;

            case 1: // No rotation
            default:
                // Return the original image if no rotation is needed
                return $this->image;
        }

        // Allocate a black background color for areas after rotation
        $bgcolor = imagecolorallocate($this->image, 0, 0, 0);

        // Rotate the image by the calculated angle and return the rotated image
        return imagerotate($this->image, $angle, $bgcolor);
    }

    /**
     * Opens an image file, detects its MIME type, and creates an image resource from it.
     *
     * This method processes an image file to create an image resource using GD library functions.
     * It supports the following MIME types: `image/jpeg`, `image/gif`, `image/png`, and `image/webp`.
     * If the MIME type is unsupported or the image resource creation fails, an exception is thrown.
     *
     * @param  File  $file  The image file instance to be opened.
     *
     * @throws Exception If the MIME type is unsupported or the file cannot be read.
     *
     * @return resource The image resource created from the file.
     */
    protected function openImage($file)
    {
        // Get the absolute path of the file from the File instance
        $filePath = $file->getPathname();

        // Declare a variable to hold the image resource
        $img = null;

        // Handle the image based on its MIME type
        switch ($this->mime) {
            case 'image/jpeg':
                // Attempt to create an image resource from a JPEG file
                $img = @imagecreatefromjpeg($filePath);

                break;

            case 'image/gif':
                // Attempt to create an image resource from a GIF file
                $img = @imagecreatefromgif($filePath);

                break;

            case 'image/png':
                // Attempt to create an image resource from a PNG file
                $img = @imagecreatefrompng($filePath);
                // Retain image transparency for PNG format
                $this->retainImageTransparency($img);

                break;

            case 'image/webp':
                // Attempt to create an image resource from a WebP file
                $img = @imagecreatefromwebp($filePath);
                // Retain image transparency for WebP format
                $this->retainImageTransparency($img);

                break;

            default:
                // Throw an exception for unsupported MIME types
                throw Exception::make(
                    "Invalid mime type: {$this->mime}. Accepted types: image/jpeg, image/gif, image/png, image/webp.",
                );
        }

        // Check if the image resource creation failed
        if ($img === false) {
            // Throw an exception indicating failure to open the image file
            throw Exception::make(
                "Resizer failed opening the file for reading ({$this->mime}).",
            );
        }

        // Return the successfully created image resource
        return $img;
    }

    /**
     * Calculates the dimensions for resizing an image based on the selected resizing mode.
     *
     * This method determines the new dimensions for an image based on a specified mode. Supported modes include:
     * `exact`, `portrait`, `landscape`, `auto`, `crop`, and `fit`. Each mode calculates dimensions differently
     * depending on the desired width, height, and the aspect ratio of the original image.
     *
     * @param  int  $newWidth  The desired width of the image.
     * @param  int  $newHeight  The desired height of the image.
     *
     * @throws Exception If an invalid mode is specified.
     *
     * @return array An array containing the calculated width and height for the image.
     */
    protected function getDimensions($newWidth, $newHeight)
    {
        // Retrieve the resizing mode from the configured options
        $mode = $this->getOption('mode');

        // Calculate the dimensions based on the selected mode
        switch ($mode) {
            case 'exact':
                // Use the exact dimensions specified
                return [$newWidth, $newHeight];

            case 'portrait':
                // Maintain the aspect ratio and adjust the width based on the fixed height
                return [$this->getSizeByFixedHeight($newHeight), $newHeight];

            case 'landscape':
                // Maintain the aspect ratio and adjust the height based on the fixed width
                return [$newWidth, $this->getSizeByFixedWidth($newWidth)];

            case 'auto':
                // Automatically adjust dimensions to maintain aspect ratio
                return $this->getSizeByAuto($newWidth, $newHeight);

            case 'crop':
                // Crop the image to fit the desired dimensions, possibly losing parts of the image
                return $this->getOptimalCrop($newWidth, $newHeight);

            case 'fit':
                // Fit the image inside the bounding box without cropping
                return $this->getSizeByFit($newWidth, $newHeight);

            default:
                // Throw an exception for an unsupported or invalid mode
                throw Exception::make(
                    'Invalid dimension type. Accepted types: exact, portrait, landscape, auto, crop, fit.',
                );
        }
    }

    /**
     * Returns the width of the image based on a fixed height, maintaining aspect ratio.
     *
     * @param  int  $newHeight  The desired height of the image
     *
     * @return int The calculated width based on the aspect ratio
     */
    protected function getSizeByFixedHeight($newHeight)
    {
        // Calculate the width based on the aspect ratio of the image
        $ratio = $this->width / $this->height;

        // Return the new width based on the given height and aspect ratio
        return $newHeight * $ratio;
    }

    /**
     * Returns the height of the image based on a fixed width, maintaining aspect ratio.
     *
     * @param  int  $newWidth  The desired width of the image
     *
     * @return int The calculated height based on the aspect ratio
     */
    protected function getSizeByFixedWidth($newWidth)
    {
        // Calculate the height based on the aspect ratio of the image
        $ratio = $this->height / $this->width;

        // Return the new height based on the given width and aspect ratio
        return $newWidth * $ratio;
    }

    /**
     * Automatically adjusts the image size depending on whether it is portrait, landscape, or square.
     *
     * @param  int  $newWidth  The desired width of the image
     * @param  int  $newHeight  The desired height of the image
     *
     * @return array The optimal width and height for the image
     */
    protected function getSizeByAuto($newWidth, $newHeight): array
    {
        // Adjust width or height if they are less than 1 pixel
        if ($newWidth <= 1 && $newHeight <= 1) {
            $newWidth = $this->width;
            $newHeight = $this->height;
        } elseif ($newWidth <= 1) {
            $newWidth = $this->getSizeByFixedHeight($newHeight);
        } elseif ($newHeight <= 1) {
            $newHeight = $this->getSizeByFixedWidth($newWidth);
        }

        // Handle landscape images
        if ($this->height < $this->width) {
            $optimalWidth = $newWidth;
            $optimalHeight = $this->getSizeByFixedWidth($newWidth);
        }
        // Handle portrait images
        elseif ($this->height > $this->width) {
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight = $newHeight;
        }
        // Handle square images
        else {
            if ($newHeight < $newWidth) {
                $optimalWidth = $newWidth;
                $optimalHeight = $this->getSizeByFixedWidth($newWidth);
            } elseif ($newHeight > $newWidth) {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight = $newHeight;
            } else {
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
            }
        }

        return [$optimalWidth, $optimalHeight];
    }

    /**
     * Attempts to find the best way to crop the image based on portrait or landscape orientation.
     *
     * @param  int  $newWidth  The desired width of the image
     * @param  int  $newHeight  The desired height of the image
     *
     * @return array The optimal width and height for cropping
     */
    protected function getOptimalCrop($newWidth, $newHeight): array
    {
        // Calculate the height-to-width ratio for both the original image and the target dimensions
        $heightRatio = $this->height / $newHeight;
        $widthRatio = $this->width / $newWidth;

        // Select the ratio that results in the best crop
        $optimalRatio = ($heightRatio < $widthRatio) ? $heightRatio : $widthRatio;

        // Calculate the optimal width and height based on the chosen ratio
        $optimalHeight = round($this->height / $optimalRatio);
        $optimalWidth = round($this->width / $optimalRatio);

        return [$optimalWidth, $optimalHeight];
    }

    /**
     * Fits the image inside a bounding box with the given maximum width and height constraints.
     *
     * @param  int  $maxWidth  The maximum width for the image
     * @param  int  $maxHeight  The maximum height for the image
     *
     * @return array The optimal width and height that fit within the bounds
     */
    protected function getSizeByFit($maxWidth, $maxHeight): array
    {
        // Calculate the scaling ratios for width and height
        $ratioW = $maxWidth / $this->width;
        $ratioH = $maxHeight / $this->height;

        // Select the smaller ratio to ensure the image fits within the box
        $effectiveRatio = min($ratioW, $ratioH);

        // Calculate the final width and height using the effective ratio
        $optimalWidth = round($this->width * $effectiveRatio);
        $optimalHeight = round($this->height * $effectiveRatio);

        return [$optimalWidth, $optimalHeight];
    }
}
