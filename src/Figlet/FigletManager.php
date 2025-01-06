<?php

declare(strict_types=1);

namespace Maginium\Framework\Figlet;

use Maginium\Framework\Figlet\Helpers\ColorManager;
use Maginium\Framework\Figlet\Helpers\FontManager;
use Maginium\Framework\Figlet\Interfaces\Data\FontInterface;
use Maginium\Framework\Figlet\Interfaces\FigletInterface;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Class FigletManager.
 *
 * Responsible for managing the rendering of text in the Figlet format with options for color,
 * font selection, and stretching. Uses external managers for font and color handling.
 */
class FigletManager implements FigletInterface
{
    /**
     * The background color to be applied to the rendered Figlet text.
     *
     * @var string
     */
    private string $backgroundColor;

    /**
     * The color of the font used to render the Figlet text.
     *
     * @var string
     */
    private string $fontColor;

    /**
     * The name of the font to be used for rendering the Figlet text.
     *
     * @var string
     */
    private string $fontName;

    /**
     * The directory path where fonts are stored.
     *
     * @var string
     */
    private string $fontDir;

    /**
     * The stretching factor to adjust the width/height of the generated Figlet text.
     *
     * @var int
     */
    private int $stretching;

    /**
     * Holds the characters used in the rendered Figlet text.
     *
     * @var array
     */
    private array $characters = [];

    /**
     * Manages color-related functionalities such as setting text and background colors.
     *
     * @var ColorManager
     */
    private ColorManager $colorManager;

    /**
     * Manages font-related functionalities such as loading and selecting fonts.
     *
     * @var FontManager
     */
    private FontManager $fontManager;

    /**
     * Holds the loaded font object for rendering text in Figlet format.
     *
     * @var FontInterface
     */
    private FontInterface $font;

    /**
     * FigletManager constructor.
     *
     * Initializes the stretching factor, font name, font directory, and required managers for
     * font and color management.
     *
     * @param FontManager $fontManager The manager responsible for handling font-related functionality.
     * @param ColorManager $colorManager The manager responsible for handling color-related functionality.
     */
    public function __construct(
        FontManager $fontManager,
        ColorManager $colorManager,
    ) {
        // Assign passed FontManager and ColorManager to the respective class properties
        $this->fontManager = $fontManager;
        $this->colorManager = $colorManager;

        // Default values for stretching factor, font name, and font directory
        $this->stretching = 0;
        $this->fontName = 'big';
        $this->fontDir = Path::join(Filesystem::dirname(__DIR__), 'view', 'base', 'web', 'fonts');
    }

    /**
     * Clears the internal cache by unsetting certain arrays and objects.
     *
     * Specifically, it clears the characters array and the font object.
     */
    public function clear(): void
    {
        // Unset characters and font to free memory
        unset($this->characters, $this->font);
    }

    /**
     * Writes the rendered Figlet text to the output.
     *
     * This method echoes the generated Figlet text followed by a newline.
     * It acts as a wrapper around the `render()` method for directly outputting the text.
     *
     * @param string $text The text to be rendered into Figlet format.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function write($text): FigletInterface
    {
        // Render the Figlet text and echo it with a newline
        echo $this->render($text) . PHP_EOL;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Renders the given text in Figlet format.
     *
     * This method loads the font using the font manager, generates the Figlet text,
     * and applies any color settings (if defined).
     *
     * @param string $text The text to be rendered into Figlet format.
     *
     * @return string The Figlet-formatted text, potentially colorized.
     */
    public function render($text): string
    {
        // Load the font using the font manager based on the configured font name and directory
        $this->font = $this->fontManager->loadFont(
            $this->fontName,
            $this->fontDir,
        );

        // Generate the Figlet text from the provided string
        $figletText = $this->generateFigletText($text);

        // If colors are set, apply them to the generated Figlet text
        if ($this->fontColor || $this->backgroundColor) {
            $figletText = $this->colorize($figletText);
        }

        // Return the generated Figlet text
        return $figletText;
    }

    /**
     * Sets the background color for the rendered Figlet text.
     *
     * This method allows the user to specify the background color that will be applied
     * when rendering the Figlet text.
     *
     * @param string $color The background color to be applied.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setBackgroundColor($color): FigletInterface
    {
        // Set the background color
        $this->backgroundColor = $color;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Sets the font color for rendering Figlet text.
     *
     * This method allows the user to specify the color for the font used in rendering
     * the Figlet text. It returns the current instance for method chaining.
     *
     * @param string $color The color to be applied to the font.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFontColor($color): FigletInterface
    {
        // Set the font color property to the provided color
        $this->fontColor = $color;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Sets the font to be used for rendering Figlet text.
     *
     * This method allows the user to specify which font to use when rendering the
     * Figlet text. It returns the current instance for method chaining.
     *
     * @param string $fontName The name of the font to be used.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFont($fontName): FigletInterface
    {
        // Set the font name property to the provided font name
        $this->fontName = $fontName;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Sets the directory where the font files are located.
     *
     * This method allows the user to specify the directory where the font files
     * are stored. It returns the current instance for method chaining.
     *
     * @param string $fontDir The path to the directory containing font files.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFontDir($fontDir): FigletInterface
    {
        // Set the font directory property to the provided path
        $this->fontDir = $fontDir;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Sets the stretching factor for the Figlet text.
     *
     * This method allows the user to set how much the Figlet text should be stretched
     * in width or height. It returns the current instance for method chaining.
     *
     * @param int $stretching The stretching factor (positive or negative value).
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFontStretching($stretching): FigletInterface
    {
        // Set the stretching property to the provided value
        $this->stretching = $stretching;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Generates the Figlet text from the provided string.
     *
     * This method takes the input string, processes it, and returns the rendered
     * Figlet text by combining individual character representations.
     *
     * @param string $text The text to be converted into Figlet format.
     *
     * @return string The generated Figlet text.
     */
    private function generateFigletText($text): string
    {
        // Retrieve the individual Figlet characters for the provided text
        $figletCharacters = $this->getFigletCharacters($text);

        // Combine the characters into the final Figlet output
        return $this->combineFigletCharacters($figletCharacters);
    }

    /**
     * Retrieves the Figlet characters for the provided text.
     *
     * This method breaks the input text into individual characters and fetches
     * the corresponding Figlet representation for each character.
     *
     * @param string $text The text to be processed into Figlet characters.
     *
     * @return array An array containing the Figlet representations of the characters.
     */
    private function getFigletCharacters($text): array
    {
        // Initialize an empty array to hold the Figlet characters
        $figletCharacters = [];

        // Iterate over each character in the text (using mb_str_split for multibyte characters)
        foreach (mb_str_split($text) as $character) {
            // Add the Figlet character representation to the array
            $figletCharacters[] = $this->getFigletCharacter($character);
        }

        // Return the array of Figlet characters
        return $figletCharacters;
    }

    /**
     * Retrieves the Figlet character representation for a single character.
     *
     * This method checks if the character has already been processed and cached.
     * If not, it processes the character, caches it, and returns the Figlet representation.
     *
     * @param string $character The character to be converted to its Figlet representation.
     *
     * @return array An array representing the Figlet version of the character.
     */
    private function getFigletCharacter($character): array
    {
        // Check if the Figlet character has already been cached
        if (isset($this->characters[$this->fontName][$character])) {
            // Return the cached Figlet character if it exists
            return $this->characters[$this->fontName][$character];
        }

        // Initialize an empty array for the Figlet character
        $figletCharacter = [];

        // Retrieve the lines that make up the Figlet character
        $lines = $this->getFigletCharacterLines($character);

        // Process each line to replace the special characters with appropriate symbols
        foreach ($lines as $line) {
            $figletCharacter[] = Str::replace(
                ['@', $this->font->getHardBlank()],
                ['', ' '],
                $line,
            );
        }

        // Cache the processed Figlet character for future use
        $this->characters[$this->fontName][$character] = $figletCharacter;

        // Return the processed Figlet character
        return $figletCharacter;
    }

    /**
     * Retrieves the lines that represent a Figlet character from the font file.
     *
     * This method calculates the starting position of the character in the font file,
     * slices the array of font lines from that position, and returns the corresponding
     * lines for the given character.
     *
     * @param string $character The character for which Figlet lines are being retrieved.
     *
     * @return array An array of strings representing the lines for the Figlet character.
     */
    private function getFigletCharacterLines($character): array
    {
        // Get the starting position of the character in the font file
        $letterStartPosition = $this->getLetterStartPosition($character);

        // Slice the font file collection to retrieve the character's lines
        $lines = Php::arraySlice(
            $this->font->getFileCollection(),
            $letterStartPosition,
            $this->font->getHeight(),
        );

        // Return the lines for the character
        return $lines;
    }

    /**
     * Combines the Figlet characters into a single string of text.
     *
     * This method takes an array of Figlet character representations and combines
     * them into a single string, line by line, adding any necessary stretching spaces.
     *
     * @param array $figletCharacters The array of Figlet characters to be combined.
     *
     * @return string The combined Figlet text as a single string.
     */
    private function combineFigletCharacters($figletCharacters): string
    {
        // Initialize an empty string to hold the combined Figlet text
        $figletText = '';

        // Get the height of the font (i.e., the number of lines per character)
        $height = $this->font->getHeight();

        // Loop through each line of the Figlet characters (from top to bottom)
        for ($line = 0; $line < $height; $line++) {
            // Initialize a string for the current line
            $singleLine = '';

            // Loop through each character's lines and append the corresponding line
            foreach ($figletCharacters as $charactersLines) {
                $singleLine .= $charactersLines[$line] . $this->addStretching(); // Add stretching if needed
            }

            // Remove any newline characters from the single line
            $singleLine = $this->removeNewlines($singleLine);

            // Append the formatted line to the final Figlet text, with a newline at the end
            $figletText .= $singleLine . "\n";
        }

        // Return the combined Figlet text
        return $figletText;
    }

    /**
     * Colorizes the Figlet text by applying the specified foreground and background colors.
     *
     * This method uses the ColorManager to colorize the text based on the configured
     * font color and background color.
     *
     * @param string $figletText The Figlet text to be colorized.
     *
     * @return string The colorized Figlet text.
     */
    private function colorize($figletText): string
    {
        // Use the ColorManager to apply the font and background colors
        $figletText = $this->colorManager->colorize(
            $figletText,
            $this->fontColor,
            $this->backgroundColor,
        );

        // Return the colorized Figlet text
        return $figletText;
    }

    /**
     * Removes any newline characters from a string.
     *
     * This method removes all occurrences of carriage return (`\r`) and newline (`\n`)
     * characters from the provided string.
     *
     * @param string $singleLine The string from which newline characters will be removed.
     *
     * @return string The string with all newline characters removed.
     */
    private function removeNewlines($singleLine): string
    {
        // Remove carriage return and newline characters using a regular expression
        return Php::pregReplace('/[\\r\\n]*/', '', $singleLine);
    }

    /**
     * Adds stretching spaces to a Figlet character if needed.
     *
     * This method adds extra spaces at the end of a Figlet character if the stretching
     * factor is greater than 0. The number of spaces added corresponds to the stretching
     * factor. If stretching is not needed, it returns an empty string.
     *
     * @return string A string containing the added stretching spaces (if any).
     */
    private function addStretching(): string
    {
        // Check if the stretching factor is numeric and greater than 0
        if (Validator::isNumeric($this->stretching) && $this->stretching > 0) {
            // If stretching is needed, return the appropriate number of spaces
            $stretchingSpace = ' ';
        } else {
            // If no stretching is needed, reset the stretching factor and return an empty string
            $stretchingSpace = '';
            $this->stretching = 0;
        }

        // Return the stretching spaces (if any)
        return Str::repeat($stretchingSpace, $this->stretching);
    }

    /**
     * Calculates the starting position of a character in the font file.
     *
     * This method computes the starting position of a character in the font file
     * based on the ASCII value of the character, the height of the font, and any
     * comment lines in the font file.
     *
     * @param string $character The character whose starting position is being calculated.
     *
     * @return int The calculated starting position of the character in the font file.
     */
    private function getLetterStartPosition($character): int
    {
        // Calculate the starting position of the character using its ASCII value
        return (ord($character) - static::FIRST_ASCII_CHARACTER) *
            $this->font->getHeight() +
            1 + // Account for the first line of the character
            $this->font->getCommentLines(); // Account for any comment lines in the font file
    }
}
