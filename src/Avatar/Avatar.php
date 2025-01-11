<?php

declare(strict_types=1);

namespace Maginium\Framework\Avatar;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use Maginium\Foundation\Enums\FileExtension;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Avatar\Generators\DefaultGenerator;
use Maginium\Framework\Avatar\Interfaces\AvatarInterface;
use Maginium\Framework\Avatar\Interfaces\GeneratorInterface;
use Maginium\Framework\Avatar\Traits\AttributeSetter;
use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Cache;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Media;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Random\RandomException;

/**
 * Class Avatar.
 *
 * This class is responsible for generating and managing avatar images.
 * It supports different shapes, fonts, background and foreground colors,
 * and various customization options for the avatar's appearance.
 */
class Avatar extends ObjectModel implements AvatarInterface
{
    use AttributeSetter;

    /**
     * @var string|null Name for the avatar (e.g. for initials).
     */
    protected ?string $name = '';

    /**
     * @var int Number of characters to display as initials in the avatar.
     */
    protected int $chars;

    /**
     * @var string Shape of the avatar ('circle' or 'square').
     */
    protected string $shape;

    /**
     * @var int Width of the avatar image.
     */
    protected int $width;

    /**
     * @var int Height of the avatar image.
     */
    protected int $height;

    /**
     * @var array List of available background colors for the avatar.
     */
    protected array $availableBackgrounds = [];

    /**
     * @var array List of available foreground colors for the avatar.
     */
    protected array $availableForegrounds = [];

    /**
     * @var array List of fonts available for the avatar initials.
     */
    protected array $fonts = [];

    /**
     * @var float Font size for the initials in the avatar.
     */
    protected float $fontSize;

    /**
     * @var string|null The font family to use for the initials.
     */
    protected ?string $fontFamily = null;

    /**
     * @var int Border size around the avatar.
     */
    protected int $borderSize = 0;

    /**
     * @var string Border color (can be a predefined color or hex).
     */
    protected string $borderColor;

    /**
     * @var int Border radius (for rounding corners in SVG avatars).
     */
    protected int $borderRadius = 0;

    /**
     * @var bool Whether to replace characters with their closest ASCII equivalents.
     */
    protected bool $ascii = false;

    /**
     * @var bool Whether to display initials in uppercase.
     */
    protected bool $uppercase = false;

    /**
     * @var bool Whether to enable support for Right-to-Left (RTL) languages.
     */
    protected bool $rtl = false;

    /**
     * @var Image Image instance used to create and manage avatar images.
     */
    protected Image $image;

    /**
     * @var string|null Font used for avatar initials.
     */
    protected ?string $font;

    /**
     * @var string Background color of the avatar.
     */
    protected string $background = '#CCCCCC';

    /**
     * @var string Foreground color of the avatar (initials).
     */
    protected string $foreground = '#FFFFFF';

    /**
     * @var string Initials to be displayed in the avatar.
     */
    protected string $initials = '';

    /**
     * @var mixed Image driver to use for image processing (GD or Imagick).
     */
    protected mixed $driver;

    /**
     * @var GeneratorInterface Generator used for creating initials on the avatar.
     */
    protected GeneratorInterface $initialGenerator;

    /**
     * @var string Default font path for avatar initials.
     */
    protected string $defaultFont = __DIR__ . '/view/adminhtml/web/fonts/OpenSans-Bold.ttf';

    /**
     * @var array Predefined themes for avatar customization.
     */
    protected array $themes = [];

    /**
     * @var string|array|null The selected theme(s) for the avatar.
     */
    protected string|array|null $theme;

    /**
     * @var array Default theme settings.
     */
    protected array $defaultTheme = [];

    /**
     * The directory path where generated avatar images are stored.
     *
     * @var string
     */
    private string $mediaDir;

    /**
     * Avatar constructor.
     *
     * This constructor initializes the avatar with provided configuration settings.
     * It applies themes, selects an image driver, and sets up default configurations.
     *
     * @param array $config Configuration settings for avatar customization.
     */
    public function __construct(array $config = [])
    {
        // Determine the image processing driver (GD or Imagick)
        $this->driver = $config['driver'] ?? 'gd';

        // Apply the theme provided in the configuration
        $this->theme = $config['theme'] ?? null;

        // Validate and apply the default theme
        $this->defaultTheme = $this->validateConfig($config);

        // Apply the validated theme to the avatar
        $this->applyTheme($this->defaultTheme);

        // Initialize the default generator for initials
        $this->initialGenerator = new DefaultGenerator;

        // Resolve additional themes provided in the configuration
        $themes = $this->resolveTheme('*', $config['themes'] ?? []);

        // Add the resolved themes to the list of available themes
        foreach ($themes as $name => $conf) {
            $this->addTheme($name, $conf);
        }

        // Initialize the selected theme settings
        $this->initTheme();

        // Set the media directory for avatar images
        $this->mediaDir = Media::absolutePath('customer/avatar');
    }

    /**
     * Create an avatar with the given name.
     *
     * This method initializes the avatar creation process by setting the avatar's name
     * and applying the theme settings. It returns the current instance to allow method chaining.
     *
     * @param string $name The name to be used for the avatar.
     *
     * @return static The current instance of the Avatar class for method chaining.
     */
    public function create(string $name): static
    {
        $this->name = $name;

        // Initialize the theme configuration for the avatar
        $this->initTheme();

        return $this;
    }

    /**
     * Save the generated avatar image to a file.
     *
     * This method generates the avatar and saves it to the specified path with the given quality.
     * It validates the provided file path and checks if the file already exists.
     *
     * @param string|null $path The file path where the avatar will be saved.
     * @param int $quality The quality of the saved image (1-100). Defaults to 90.
     * @param bool $overwrite Whether to overwrite the file if it already exists. Defaults to true.
     *
     * @throws InvalidArgumentException If the provided path is invalid or not writable.
     *
     * @return ImageInterface The saved image instance, or null if the file exists and overwrite is false.
     */
    public function save(?string $path, int $quality = 90, bool $overwrite = true): ?ImageInterface
    {
        // Ensure the avatar is built before saving
        $this->buildAvatar();

        // Validate the provided file path
        if (empty($path)) {
            throw InvalidArgumentException::make('The provided file path cannot be empty.');
        }

        // Get the directory from the provided file path
        $directory = Filesystem::dirname($path);

        // Check if the directory exists
        if (! Filesystem::isDirectory($directory)) {
            throw InvalidArgumentException::make('The directory for the provided file path does not exist: ' . $directory);
        }

        // Check if the directory is writable
        if (! Filesystem::isWritable($directory)) {
            throw InvalidArgumentException::make('The directory for the provided file path is not writable: ' . $directory);
        }

        // Check if the file already exists
        if (Filesystem::exists($path)) {
            if (! $overwrite) {
                // Skip saving and return null if overwrite is disabled
                return null;
            }
        }

        // Save the image to the specified path with the given quality
        return $this->image->save($path, $quality);
    }

    /**
     * Set a custom generator for the avatar creation.
     *
     * This method allows overriding the default avatar generator with a custom one.
     * The generator is responsible for how the avatar image is created.
     *
     * @param GeneratorInterface $generator The custom avatar generator.
     *
     * @return void
     */
    public function setGenerator(GeneratorInterface $generator): void
    {
        $this->initialGenerator = $generator;
    }

    /**
     * Apply theme settings to the avatar.
     *
     * This method validates and applies the configuration options for the avatar's theme.
     * The configuration includes settings like shape, size, font, border, and background.
     *
     * @param array $config The theme configuration.
     *
     * @return void
     */
    public function applyTheme(array $config): void
    {
        // Validate and apply the theme configuration
        $config = $this->validateConfig($config);

        // Apply individual theme settings
        $this->font = $this->defaultFont;
        $this->rtl = $config['rtl'];
        $this->shape = $config['shape'];
        $this->chars = $config['chars'];
        $this->fonts = $config['fonts'];
        $this->width = $config['width'];
        $this->ascii = $config['ascii'];
        $this->height = $config['height'];
        $this->fontSize = $config['fontSize'];
        $this->uppercase = $config['uppercase'];
        $this->borderSize = $config['border']['size'];
        $this->borderColor = $config['border']['color'];
        $this->borderRadius = $config['border']['radius'];
        $this->availableBackgrounds = $config['backgrounds'];
        $this->availableForegrounds = $config['foregrounds'];
    }

    /**
     * Add a new theme to the avatar system.
     *
     * This method allows the addition of new theme configurations that can be applied
     * to avatars. The theme will be validated and added to the list of available themes.
     *
     * @param string $name The name of the new theme.
     * @param array $config The configuration settings for the new theme.
     *
     * @return static The current instance of the Avatar class for method chaining.
     */
    public function addTheme(string $name, array $config): static
    {
        // Validate and add the theme configuration
        $this->themes[$name] = $this->validateConfig($config);

        return $this;
    }

    /**
     * Generate the avatar as a Base64 encoded string.
     *
     * This method generates the avatar image and returns it as a Base64-encoded PNG string.
     * The Base64 string can be used directly in HTML or other applications.
     *
     * The result is cached to avoid regenerating the same avatar multiple times.
     *
     * @return string The Base64-encoded PNG representation of the avatar.
     */
    public function toBase64(): string
    {
        // Generate a cache key based on the avatar's configuration
        $key = $this->cacheKey();

        // Check if the SVG is cached
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        // Build the avatar if not cached
        $this->buildAvatar();

        // Convert the image to PNG and encode it as a Base64 string
        $base64 = $this->image->toPng()->toDataUri();

        // Cache the Base64 string for future use
        Cache::forever($key, $base64);

        return $base64;
    }

    /**
     * Generate and return the avatar as a PNG file path.
     *
     * This method builds the avatar, converts it to PNG format, and saves it to a file.
     * If the PNG already exists, it returns the path to the existing file without regenerating the avatar.
     *
     * The result is cached using a unique key based on the avatar's configuration to avoid
     * regenerating the same avatar multiple times, improving performance and reducing unnecessary processing.
     *
     * @return string The relative path to the saved PNG avatar.
     */
    public function toPng(): string
    {
        // Ensure the directory exists, create it if necessary
        if (! Filesystem::exists($this->mediaDir)) {
            Filesystem::makeDirectory($this->mediaDir);
        }

        // Define the full path for the avatar PNG image, using the avatar's name
        $filePath = Path::join($this->mediaDir, Str::snake($this->name), FileExtension::PNG);

        // Convert the image to PNG format and save it to the defined file path if it doesn't exist
        $this->save($filePath);

        // Get the relative URL path to the saved PNG image
        $png = Media::url(Media::relativePath($filePath));

        // Return the relative path to the saved PNG image
        return $png;
    }

    /**
     * Generate and return the avatar as a JPG file path.
     *
     * This method builds the avatar, converts it to JPG format, and saves it to a file.
     * If the JPG already exists, it returns the path to the existing file without regenerating the avatar.
     *
     * The result is cached using a unique key based on the avatar's configuration to avoid
     * regenerating the same avatar multiple times, improving performance and reducing unnecessary processing.
     *
     * @return string The relative path to the saved JPG avatar.
     */
    public function toJpg(): string
    {
        // Ensure the directory exists, create it if necessary
        if (! Filesystem::exists($this->mediaDir)) {
            Filesystem::makeDirectory($this->mediaDir);
        }

        // Define the full path for the avatar JPG image, using the avatar's name
        $filePath = Path::join($this->mediaDir, Str::snake($this->name), FileExtension::JPG);

        // Convert the image to JPG format and save it to the defined file path if it doesn't exist
        $this->save($filePath, 85); // Using quality 85 for JPG

        // Get the relative URL path to the saved JPG image
        $jpg = Media::url(Media::relativePath($filePath));

        // Return the relative path to the saved JPG image
        return $jpg;
    }

    /**
     * Generate and return the avatar as a WebP file path.
     *
     * This method builds the avatar, converts it to WebP format, and saves it to a file.
     * If the WebP already exists, it returns the path to the existing file without regenerating the avatar.
     *
     * The result is cached using a unique key based on the avatar's configuration to avoid
     * regenerating the same avatar multiple times, improving performance and reducing unnecessary processing.
     *
     * @return string The relative path to the saved WebP avatar.
     */
    public function toWebp(): string
    {
        // Ensure the directory exists, create it if necessary
        if (! Filesystem::exists($this->mediaDir)) {
            Filesystem::makeDirectory($this->mediaDir);
        }

        // Define the full path for the avatar WebP image, using the avatar's name
        $filePath = Path::join($this->mediaDir, Str::snake($this->name), FileExtension::WEBP);

        // Convert the image to WebP format and save it to the defined file path if it doesn't exist
        $this->save($filePath, 90); // Using quality 90 for WebP

        // Get the relative URL path to the saved WebP image
        $webp = Media::url(Media::relativePath($filePath));

        // Return the relative path to the saved WebP image
        return $webp;
    }

    /**
     * Generate the avatar as an SVG string.
     *
     * This method generates the avatar as an SVG image. The SVG format is vector-based
     * and can be scaled without loss of quality. It includes an optional border and background
     * depending on the configuration. The result is cached to avoid regenerating the same SVG
     * avatar multiple times.
     *
     * @return string The generated SVG representation of the avatar.
     */
    public function toSvg(): string
    {
        // Generate a cache key based on the avatar's configuration
        $key = $this->cacheKey('svg');

        // Check if the SVG is cached
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        // Build the initial avatar settings before generating SVG
        $this->buildInitial();

        // Set positions and sizes for the SVG elements
        $x = $y = $this->borderSize / 2;
        $width = $height = $this->width - $this->borderSize;
        $radius = ($this->width - $this->borderSize) / 2;
        $center = $this->width / 2;

        // Start the SVG string
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $this->width . '" height="' . $this->height . '" viewBox="0 0 ' . $this->width . ' ' . $this->height . '">';

        // Add a rectangular or circular shape based on the avatar's configuration
        if ($this->shape === 'square') {
            $svg .= '<rect x="' . $x
                . '" y="' . $y
                . '" width="' . $width . '" height="' . $height
                . '" stroke="' . $this->getBorderColor()
                . '" stroke-width="' . $this->borderSize
                . '" rx="' . $this->borderRadius
                . '" fill="' . $this->background . '" />';
        } elseif ($this->shape === 'circle') {
            $svg .= '<circle cx="' . $center
                . '" cy="' . $center
                . '" r="' . $radius
                . '" stroke="' . $this->getBorderColor()
                . '" stroke-width="' . $this->borderSize
                . '" fill="' . $this->background . '" />';
        }

        // Add the initials text in the center of the avatar
        $svg .= '<text font-size="' . $this->fontSize;

        // Add font family if specified
        if ($this->fontFamily) {
            $svg .= '" font-family="' . $this->fontFamily;
        }

        // Complete the text styling and insert the initials
        $svg .= '" fill="' . $this->foreground . '" x="50%" y="50%" dy=".1em" style="line-height:1" alignment-baseline="middle" text-anchor="middle" dominant-baseline="central">';
        $svg .= $this->getInitial();
        $svg .= '</text>';

        // Close the SVG tag
        $svg .= '</svg>';

        // Cache the generated SVG for future use
        Cache::forever($key, $svg);

        return $svg;
    }

    /**
     * Generates a Gravatar URL for the user's avatar.
     * The URL is generated based on the user's name and optional parameters (like width).
     *
     * @param array|null $param Optional parameters to be added to the Gravatar URL (e.g., size, rating).
     *
     * @return string The full URL to the user's Gravatar image.
     */
    public function toGravatar(?array $param = null): string
    {
        // Hash generation for Gravatar, based on the user's name (in lowercase)
        // This is to uniquely identify the avatar based on the name.
        $hash = hash('sha256', mb_strtolower(trim($this->name)));

        // Initialize attributes for the Gravatar URL (like size, rating, etc.)
        $attributes = [];

        // Add the width parameter if it's set
        if ($this->width) {
            $attributes['s'] = $this->width;
        }

        // Merge additional parameters with existing attributes (if any)
        if (! empty($param)) {
            $attributes = $param + $attributes;
        }

        // Construct the base URL for Gravatar
        $url = Str::format('https://www.gravatar.com/avatar/%s', $hash);

        // If there are any additional attributes, add them to the URL
        if (! empty($attributes)) {
            $url .= '?';
            ksort($attributes);

            // Append each key-value pair as a query parameter
            foreach ($attributes as $key => $value) {
                $url .= "{$key}={$value}&";
            }
            // Remove the trailing '&' character
            $url = mb_substr($url, 0, -1);
        }

        return $url;
    }

    /**
     * Retrieves the initials of the user (used for avatars).
     *
     * @return string The initials of the user.
     */
    public function getInitial(): string
    {
        return $this->initials;
    }

    /**
     * Returns the image object representing the avatar.
     * If the avatar hasn't been built yet, it will be constructed before returning the image object.
     *
     * @return Image The image object representing the avatar.
     */
    public function getImageObject(): Image
    {
        $this->buildAvatar();

        return $this->image;
    }

    /**
     * Builds the avatar image by generating the initials, applying the selected shape, and adding the text.
     * This method is called internally to create the avatar image if it doesn't already exist.
     *
     * @return static The current instance for method chaining.
     */
    public function buildAvatar(): static
    {
        // Build the initials (used as text in the avatar) if not already done
        $this->buildInitial();

        // Calculate the center of the avatar image
        $x = $this->width / 2;
        $y = $this->height / 2;

        // Select the image driver based on the current configuration ('gd' or 'imagick')
        $driver = $this->driver === 'gd' ? new Driver : new ImagickDriver;
        $manager = new ImageManager($driver);
        $this->image = $manager->create($this->width, $this->height);

        // Apply the chosen shape (circle, square, etc.) to the avatar
        $this->createShape();

        // If no initials were set, skip adding the initials text to the avatar
        if (empty($this->initials)) {
            return $this;
        }

        // Add the initials as text to the avatar image at the calculated center position
        $this->image->text(
            $this->initials,
            (int)$x,
            (int)$y,
            function(FontFactory $font) {
                // Configure the font settings for the initials text
                $font->file($this->font);
                $font->size($this->fontSize);
                $font->color($this->foreground);
                $font->align('center');
                $font->valign('middle');
            },
        );

        return $this;
    }

    /**
     * Sets a random theme for the avatar based on the available themes.
     *
     * @return void
     */
    protected function setRandomTheme(): void
    {
        // Resolve the themes to apply based on the theme configuration
        $themes = $this->resolveTheme($this->theme, $this->themes);

        // If any themes are resolved, apply a random theme from the list
        if (! empty($themes)) {
            $this->applyTheme($this->getRandomElement($themes, []));
        }
    }

    /**
     * Resolves the theme configurations based on the input theme name(s).
     *
     * @param array|string|null $theme The theme name(s) to resolve.
     * @param array $cfg The available theme configurations.
     *
     * @return array The resolved theme configurations.
     */
    protected function resolveTheme(array|string|null $theme, array $cfg): array
    {
        $config = collect($cfg);
        $themes = [];

        // Loop through the theme names and resolve them to their corresponding configurations
        foreach ((array)$theme as $themeName) {
            if (! Validator::isString($themeName)) {
                continue;
            }

            // If the theme is '*', include all available themes
            if ($themeName === '*') {
                foreach ($config as $name => $themeConfig) {
                    $themes[$name] = $themeConfig;
                }
            } else {
                // Otherwise, add the specific theme configuration to the themes array
                $themes[$themeName] = $config->get($themeName, []);
            }
        }

        return $themes;
    }

    /**
     * Retrieves a random background color from the available backgrounds.
     * Falls back to the default background color if no random background is available.
     *
     * @return string The random background color.
     */
    protected function getRandomBackground(): string
    {
        return $this->getRandomElement($this->availableBackgrounds, $this->background);
    }

    /**
     * Retrieves a random foreground color from the available foregrounds.
     * Falls back to the default foreground color if no random foreground is available.
     *
     * @return string The random foreground color.
     */
    protected function getRandomForeground(): string
    {
        return $this->getRandomElement($this->availableForegrounds, $this->foreground);
    }

    /**
     * Retrieves a random font from the available fonts.
     * Falls back to the default font if no random font is available.
     *
     * @return string The random font.
     */
    protected function getRandomFont(): string
    {
        return $this->getRandomElement($this->fonts, $this->defaultFont);
    }

    /**
     * Retrieves the border color for the avatar.
     * The border color can either be a fixed value or one of 'foreground' or 'background'.
     *
     * @return string The border color.
     */
    protected function getBorderColor(): string
    {
        if ($this->borderColor === 'foreground') {
            return $this->foreground;
        }

        if ($this->borderColor === 'background') {
            return $this->background;
        }

        return $this->borderColor;
    }

    /**
     * Creates the avatar shape based on the selected shape type (circle, square, etc.).
     * The appropriate method is dynamically selected based on the shape configuration.
     *
     * @throws InvalidArgumentException If the shape is not supported.
     *
     * @return void
     */
    protected function createShape(): void
    {
        // Dynamically select the shape creation method (e.g., createCircleShape, createSquareShape)
        $method = 'create' . ucfirst($this->shape) . 'Shape';

        // If the method exists, call it to create the shape
        if (method_exists($this, $method)) {
            $this->{$method}();
        } else {
            // If the shape is not supported, throw an exception
            throw InvalidArgumentException::make("Shape [{$this->shape}] currently not supported.");
        }
    }

    /**
     * Creates a circular shape for the avatar image.
     * The circle is drawn based on the calculated dimensions and border size.
     *
     * @return void
     */
    protected function createCircleShape(): void
    {
        // Calculate the diameter of the circle based on the width and border size
        $circleDiameter = (int)($this->width - $this->borderSize);
        $x = (int)($this->width / 2);
        $y = (int)($this->height / 2);

        // Draw the circle on the avatar image
        $this->image->drawCircle(
            $x,
            $y,
            function(CircleFactory $circle) use ($circleDiameter) {
                // Configure the circle's diameter, border, and background color
                $circle->diameter($circleDiameter);
                $circle->border($this->getBorderColor(), $this->borderSize);
                $circle->background($this->background);
            },
        );
    }

    /**
     * Creates a square shape for the avatar.
     * Adjusts the shape's size based on the border size and position, and draws the rectangle with the specified settings.
     *
     * @throws InvalidArgumentException If the border size or shape is invalid.
     */
    protected function createSquareShape(): void
    {
        // Calculate the edge of the square shape based on the border size.
        $edge = ceil($this->borderSize / 2);
        $x = $y = (int)$edge; // Position of the top-left corner of the square.
        $width = $this->width - $edge;  // Width of the square after subtracting the border.
        $height = $this->height - $edge;  // Height of the square after subtracting the border.

        // Draw the square using the calculated dimensions.
        $this->image->drawRectangle(
            $x,
            $y, // Top-left corner coordinates
            function(RectangleFactory $draw) use ($width, $height) {
                // Set the size and style of the rectangle.
                $draw->size((int)$width, (int)$height); // Set the width and height of the rectangle.
                $draw->background($this->background); // Set the background color of the rectangle.
                $draw->border($this->getBorderColor(), $this->borderSize); // Set the border color and size.
            },
        );
    }

    /**
     * Generates a cache key based on the current object's attributes.
     * This cache key will be unique for each combination of attributes that define the avatar.
     *
     * @param string $format The format for which the cache key is being generated (e.g., 'base64' or 'svg').
     *
     * @return string The MD5 hash representing the cache key.
     */
    protected function cacheKey(string $format = 'base64'): string
    {
        $keys = [];

        // List of attributes to be used for cache key generation.
        $attributes = [
            'name',
            'font',
            'shape',
            'chars',
            'width',
            'height',
            'fontSize',
            'initials',
            'borderSize',
            'borderColor',
        ];

        // Collect the current values of each attribute into the cache key.
        foreach ($attributes as $attr) {
            $keys[] = $this->{$attr};
        }

        // Append the format (base64 or svg) to make the key unique for each format
        $keys[] = $format;

        // Return the MD5 hash of the concatenated attribute values and format as the cache key.
        return md5(implode('-', $keys));
    }

    /**
     * Retrieves a random element from the provided array based on a randomized selection
     * influenced by the object's name. If no name is set, it generates a random character.
     *
     * @param array $array The array from which to pick a random element.
     * @param mixed $default The default value to return if the array is empty or no element is chosen.
     *
     * @throws RandomException If an error occurs during random number generation.
     *
     * @return mixed A randomly selected element from the array, or the default value.
     */
    protected function getRandomElement(array $array, mixed $default): mixed
    {
        // Convert the array to an indexed array (ignoring keys).
        $array = Arr::values($array);

        // Use the object's name to influence the random selection. If the name is empty, generate a default random character.
        $name = $this->name;

        if ($name === null || $name === '') {
            $name = chr(random_int(65, 90)); // Generate a random uppercase letter if no name is set.
        }

        // If the array is empty, return the default value.
        if (empty($array)) {
            return $default;
        }

        // Sum the ASCII values of the characters in the name to determine the random index.
        $number = ord($name[0]);
        $i = 1;
        $charLength = Str::length($name);

        // Add the ASCII value of each character to the sum.
        while ($i < $charLength) {
            $number += ord($name[$i]);
            $i++;
        }

        // Return the element from the array based on the computed index.
        return $array[$number % count($array)];
    }

    /**
     * Builds the initials for the avatar by generating the correct characters
     * based on the provided name and configuration.
     */
    protected function buildInitial(): void
    {
        // Generate the initials using the provided configuration options such as character count and case.
        $this->initials = $this->initialGenerator->make(
            $this->name,
            $this->chars,
            $this->uppercase,
            $this->ascii,
            $this->rtl,
        );
    }

    /**
     * Validates the configuration settings for the avatar.
     * Merges the user-provided configuration with default fallback settings
     * and returns the resulting configuration array.
     *
     * @param array $config The user-provided configuration array.
     *
     * @return array The merged configuration array.
     */
    protected function validateConfig(array $config): array
    {
        // Default configuration values for the avatar.
        $fallback = [
            'shape' => 'circle',  // Default shape is circle.
            'chars' => 2,  // Default initials character count is 2.
            'backgrounds' => [$this->background],  // Default background colors.
            'foregrounds' => [$this->foreground],  // Default foreground colors.
            'fonts' => [$this->defaultFont],  // Default font.
            'fontSize' => 48,  // Default font size.
            'width' => 100,  // Default width.
            'height' => 100,  // Default height.
            'ascii' => false,  // Default ASCII setting.
            'uppercase' => false,  // Default uppercase setting.
            'rtl' => false,  // Default right-to-left setting.
            'border' => [
                'size' => 1,  // Default border size.
                'color' => 'foreground',  // Default border color.
                'radius' => 0,  // Default border radius (no roundness).
            ],
        ];

        // Merge the user-provided 'border' configuration with the default border settings.
        $config['border'] = ($config['border'] ?? []) + ($this->defaultTheme['border'] ?? []) + $fallback['border'];

        // Merge the entire user configuration with the default theme and fallback settings.
        return $config + $this->defaultTheme + $fallback;
    }

    /**
     * Initializes the avatar's theme settings by randomly selecting values for
     * the foreground, background, font, and shape, based on the configuration.
     */
    protected function initTheme(): void
    {
        // Set a random theme for the avatar.
        $this->setRandomTheme();

        // Randomly select and set the foreground color.
        $this->setForeground($this->getRandomForeground());

        // Randomly select and set the background color.
        $this->setBackground($this->getRandomBackground());

        // Randomly select and set the font.
        $this->setFont($this->getRandomFont());
    }

    /**
     * Converts the avatar to a string representation by returning its base64 encoding.
     *
     * @return string The base64-encoded string representation of the avatar.
     */
    public function __toString()
    {
        // Return the base64-encoded version of the avatar.
        return (string)$this->toBase64();
    }
}
