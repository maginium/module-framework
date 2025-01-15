<?php

declare(strict_types=1);

namespace Maginium\Framework\Figlet\Helpers;

use Maginium\Foundation\Enums\FileExtensions;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Figlet\Interfaces\Data\FontInterfaceFactory;
use Maginium\Framework\Figlet\Models\Font;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Path;

/**
 * Class FontManager.
 *
 * Responsible for managing and loading fonts in the Figlet format.
 * It handles loading, validating, and parsing font files used to generate text art.
 */
class FontManager
{
    /**
     * Defines Figlet file format extension.
     */
    public const FIGLET_FORMAT = 'flf';

    /**
     * The valid Figlet font signature that should be found at the beginning of the font file.
     */
    public const VALID_FONT_SIGNATURE = 'flf2a';

    /**
     * @var Font
     */
    private Font $font;

    /**
     * @var FontInterfaceFactory
     */
    private FontInterfaceFactory $fontFactory;

    /**
     * FontManager constructor.
     *
     * @param FontInterfaceFactory $fontFactory The font instance to be injected.
     */
    public function __construct(FontInterfaceFactory $fontFactory)
    {
        $this->fontFactory = $fontFactory;
    }

    /**
     * Loads a font if necessary or returns the current loaded font.
     *
     * @param string $fontName The name of the font to load.
     * @param string $fontDirectory The directory where the font files are stored.
     *
     * @throws Exception If the font cannot be loaded or found.
     *
     * @return Font The loaded font.
     */
    public function loadFont(string $fontName, string $fontDirectory): Font
    {
        // Load the font if it's not already loaded or if a different font is requested.
        if ($this->needLoad($fontName)) {
            return $this->createFont($fontName, $fontDirectory);
        }

        // Return the current font if it has already been loaded.
        return $this->currentFont();
    }

    /**
     * Returns the currently loaded font.
     *
     * @return Font|null The currently loaded font or null if no font is loaded.
     */
    private function currentFont(): ?Font
    {
        return $this->font;
    }

    /**
     * Creates and loads a new font by reading the font file and extracting its properties.
     *
     * @param string $fontName The name of the font to load.
     * @param string $fontDirectory The directory where the font files are stored.
     *
     * @throws Exception If the font file cannot be read or parsed.
     *
     * @return Font The newly created font.
     */
    private function createFont(string $fontName, string $fontDirectory): Font
    {
        // Get the file path for the font.
        $fileName = $this->getFileName($fontName, $fontDirectory);

        // Read the font file into an array of lines.
        $fileCollection = file($fileName);

        $font = $this->fontFactory->create();

        // Set the font's collection of file lines.
        $font->setFileCollection($fileCollection);
        $font->setName($fontName);

        // Extract and set the font parameters.
        $font = $this->setFontParameters($font);

        // Set the current loaded font.
        $this->setCurrentFont($font);

        return $font;
    }

    /**
     * Sets various font parameters by extracting them from the font file's header.
     *
     * @param Font $font The font object to set the parameters for.
     *
     * @return Font The font object with updated parameters.
     */
    private function setFontParameters(Font $font): Font
    {
        // Extract the font's header parameters.
        $parameters = $this->extractHeadlineParameters($font->getFileCollection());

        // Set the extracted parameters on the font object.
        $font
            ->setHeight($parameters['height'])
            ->setSignature($parameters['signature'])
            ->setHardBlank($parameters['hard_blank'])
            ->setMaxLength($parameters['max_length'])
            ->setOldLayout($parameters['old_layout'])
            ->setFullLayout($parameters['full_layout'])
            ->setCommentLines($parameters['comment_lines'])
            ->setPrintDirection($parameters['print_direction']);

        return $font;
    }

    /**
     * Extracts the Figlet font's header parameters from the file collection.
     *
     * @param array $fileCollection The array of lines read from the font file.
     *
     * @throws InvalidArgumentException If the font signature is invalid.
     *
     * @return array An associative array of extracted parameters.
     */
    private function extractHeadlineParameters(array $fileCollection): array
    {
        $parameters = [];

        // Use sscanf to parse the font's header line and extract parameters.
        sscanf(
            $fileCollection[0],
            '%5s%c %d %*d %d %d %d %d %d',
            $parameters['height'],
            $parameters['signature'],
            $parameters['hard_blank'],
            $parameters['max_length'],
            $parameters['old_layout'],
            $parameters['full_layout'],
            $parameters['comment_lines'],
            $parameters['print_direction'],
        );

        // Validate the font signature.
        if ($parameters['signature'] !== self::VALID_FONT_SIGNATURE) {
            // Throw an exception if the signature is invalid.
            throw InvalidArgumentException::make(
                __('Invalid font file signature: ' . $parameters['signature']),
            );
        }

        return $parameters;
    }

    /**
     * Checks if the font needs to be loaded (i.e., if it's not already the current font).
     *
     * @param string $fontName The name of the font to check.
     *
     * @return bool True if the font needs to be loaded, false otherwise.
     */
    private function needLoad(string $fontName): bool
    {
        return $this->currentFont() === null || $fontName !== $this->currentFont()->getName();
    }

    /**
     * Retrieves the full file path for the font.
     *
     * @param string $fontName The name of the font.
     * @param string $fontDirectory The directory where the font files are stored.
     *
     * @throws Exception If the font file cannot be found.
     *
     * @return string The full path to the font file.
     */
    private function getFileName(string $fontName, string $fontDirectory): string
    {
        // Construct the full file name with the directory and font name.
        $fileName = Path::join($fontDirectory, $fontName, FileExtensions::FLF);

        // Check if the font file exists.
        if (! Filesystem::exists($fileName)) {
            // Throw an exception if the font file does not exist.
            throw Exception::make('Could not open ' . $fileName);
        }

        return $fileName;
    }

    /**
     * Sets the current font to the specified font object.
     *
     * @param Font $font The font to set as the current font.
     */
    private function setCurrentFont(Font $font): void
    {
        $this->font = $font;
    }
}
