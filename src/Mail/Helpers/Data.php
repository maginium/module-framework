<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Helpers;

use Maginium\Foundation\Enums\Orientations;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Media;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;

class Data
{
    /**
     * The directory where fonts are stored.
     */
    public const FONTS_DIRECTORY = 'fonts';

    /**
     * Configuration path for whether to use custom font in mail settings.
     */
    public const FONT_SETTING_USE_CUSTOM_FONT = 'mail/font_settings/use_custom_font';

    /**
     * Configuration path for font family in mail settings.
     */
    public const FONT_SETTING_FONT_FAMILY = 'mail/font_settings/font_family';

    /**
     * Configuration path for font family character set in mail settings.
     */
    public const FONT_SETTING_FONT_FAMILY_CHARACTERSET = 'mail/font_settings/font_family_characterset';

    /**
     * Configuration path for font family fallback in mail settings.
     */
    public const FONT_SETTING_FONT_FAMILY_FALLBACK = 'mail/font_settings/font_family_fallback';

    /**
     * Configuration path for normal font name in mail settings.
     */
    public const FONT_SETTING_NORMAL_FONT_NAME = 'mail/font_settings/noraml/font_name';

    /**
     * Configuration path for normal font TTF file in mail settings.
     */
    public const FONT_SETTING_NORMAL_TTF_FILE = 'mail/font_settings/noraml/ttf_file';

    /**
     * Configuration path for normal font EOT file in mail settings.
     */
    public const FONT_SETTING_NORMAL_EOT_FILE = 'mail/font_settings/noraml/eot_file';

    /**
     * Configuration path for normal font WOFF file in mail settings.
     */
    public const FONT_SETTING_NORMAL_WOFF_FILE = 'mail/font_settings/noraml/woff_file';

    /**
     * Configuration path for normal font WOFF2 file in mail settings.
     */
    public const FONT_SETTING_NORMAL_WOFF_TWO_FILE = 'mail/font_settings/noraml/woff_two_file';

    /**
     * Configuration path for medium font name in mail settings.
     */
    public const FONT_SETTING_MEDIUM_FONT_NAME = 'mail/font_settings/medium/font_name';

    /**
     * Configuration path for medium font TTF file in mail settings.
     */
    public const FONT_SETTING_MEDIUM_TTF_FILE = 'mail/font_settings/medium/ttf_file';

    /**
     * Configuration path for medium font EOT file in mail settings.
     */
    public const FONT_SETTING_MEDIUM_EOT_FILE = 'mail/font_settings/medium/eot_file';

    /**
     * Configuration path for medium font WOFF file in mail settings.
     */
    public const FONT_SETTING_MEDIUM_WOFF_FILE = 'mail/font_settings/medium/woff_file';

    /**
     * Configuration path for medium font WOFF2 file in mail settings.
     */
    public const FONT_SETTING_MEDIUM_WOFF_TWO_FILE = 'mail/font_settings/medium/woff_two_file';

    /**
     * Configuration path for bold font name in mail settings.
     */
    public const FONT_SETTING_BOLD_FONT_NAME = 'mail/font_settings/bold/font_name';

    /**
     * Configuration path for bold font TTF file in mail settings.
     */
    public const FONT_SETTING_BOLD_TTF_FILE = 'mail/font_settings/bold/ttf_file';

    /**
     * Configuration path for bold font EOT file in mail settings.
     */
    public const FONT_SETTING_BOLD_EOT_FILE = 'mail/font_settings/bold/eot_file';

    /**
     * Configuration path for bold font WOFF file in mail settings.
     */
    public const FONT_SETTING_BOLD_WOFF_FILE = 'mail/font_settings/bold/woff_file';

    /**
     * Configuration path for bold font WOFF2 file in mail settings.
     */
    public const FONT_SETTING_BOLD_WOFF_TWO_FILE = 'mail/font_settings/bold/woff_two_file';

    /**
     * Base URL for Google Fonts API.
     */
    public const GOOGLE_FONT_BASE_URL = 'https://fonts.googleapis.com/css?family=';

    /**
     * General configuration path for sender name.
     */
    public const XML_PATH_EMAIL_SENDER_NAME = 'trans_email/ident_general/name';

    /**
     * General configuration path for sender email.
     */
    public const XML_PATH_EMAIL_SENDER_EMAIL = 'trans_email/ident_general/email';

    /**
     * @var string XML path configuration for page orientation in enhanced PDF settings.
     */
    public const XML_PATH_PAGE_ORIENTATION = 'mail/general/page_orientation';

    /**
     * @var string XML path configuration for font title in enhanced PDF settings.
     */
    public const XML_PATH_FONT_TITLE = 'mail/font/font_name';

    /**
     * @var string XML path configuration for font file path in enhanced PDF settings.
     */
    public const XML_PATH_FONT_FILE_PATH = 'mail/font/ttf_file';

    /**
     * @var string XML path configuration for font color in enhanced PDF settings.
     */
    public const XML_PATH_FONT_COLOR = 'mail/general/font_color';

    /**
     * @var string XML path configuration for background color in enhanced PDF settings.
     */
    public const XML_PATH_BG_COLOR = 'mail/general/background_color';

    /**
     * Configuration path for default CC emails.
     */
    public const CONFIG_PATH_DEFAULT_CC_EMAILS = 'your_config_path/default_cc_emails';

    /**
     * Configuration path for default BCC emails.
     */
    public const CONFIG_PATH_DEFAULT_BCC_EMAILS = 'your_config_path/default_bcc_emails';

    /**
     * Configuration path for default Reply-To email.
     */
    public const CONFIG_PATH_DEFAULT_REPLY_TO_EMAIL = 'trans_email/ident_support/email';

    /**
     * Configuration path for default Reply-To name.
     */
    public const CONFIG_PATH_DEFAULT_REPLY_TO_NAME = 'trans_email/ident_support/name';

    /**
     * Get Email Sender Name from configuration.
     *
     * @return string
     */
    public static function getFromName()
    {
        return Config::getString(self::XML_PATH_EMAIL_SENDER_NAME);
    }

    /**
     * Get Email Sender Email from configuration.
     *
     * @return string
     */
    public static function getFromEmail()
    {
        return Config::getString(self::XML_PATH_EMAIL_SENDER_EMAIL);
    }

    /**
     * Get Page Orientation from configuration.
     *
     * @return string
     */
    public static function getPageOrientation()
    {
        return Config::getString(self::XML_PATH_PAGE_ORIENTATION);
    }

    /**
     * Get Font Title from configuration.
     *
     * @return string
     */
    public static function getFontTitle()
    {
        return Config::getString(self::XML_PATH_FONT_TITLE);
    }

    /**
     * Get Font Color from configuration.
     *
     * @return string
     */
    public static function getFontColor()
    {
        return Config::getString(self::XML_PATH_FONT_COLOR);
    }

    /**
     * Get Background Color from configuration.
     *
     * @return string
     */
    public static function getBgColor()
    {
        return Config::getString(self::XML_PATH_BG_COLOR);
    }

    /**
     * Get whether to use custom font.
     *
     * @return bool
     */
    public static function getUseCustomFont(): bool
    {
        return Config::getBool(self::FONT_SETTING_USE_CUSTOM_FONT);
    }

    /**
     * Get font family from configuration.
     *
     * @return string
     */
    public static function getFontFamily(): string
    {
        return Config::getString(self::FONT_SETTING_FONT_FAMILY);
    }

    /**
     * Get font family character set from configuration.
     *
     * @return string|null
     */
    public static function getFontFamilyCharacterSet(): ?string
    {
        return Config::getString(self::FONT_SETTING_FONT_FAMILY_CHARACTERSET);
    }

    /**
     * Get font family fallback from configuration.
     *
     * @return string|null
     */
    public static function getFontFamilyFallback(): ?string
    {
        $fontFamilyFallback = Config::getString(self::FONT_SETTING_FONT_FAMILY_FALLBACK);

        if ($fontFamilyFallback) {
            $fontFallback = Php::explode(',', $fontFamilyFallback);
            $fontFallback = Arr::unique($fontFallback);

            return Php::implode(',', $fontFallback);
        }

        return null;
    }

    /**
     * Get normal font name from configuration.
     *
     * @return string
     */
    public static function getNormalFontName(): string
    {
        return Config::getString(self::FONT_SETTING_NORMAL_FONT_NAME);
    }

    /**
     * Get normal TTF file path.
     *
     * @return string
     */
    public static function getNormalTtfFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_NORMAL_TTF_FILE);
    }

    /**
     * Get normal EOT file path.
     *
     * @return string
     */
    public static function getNormalEotFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_NORMAL_EOT_FILE);
    }

    /**
     * Get normal WOFF file path.
     *
     * @return string
     */
    public static function getNormalWoffFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_NORMAL_WOFF_FILE);
    }

    /**
     * Get normal WOFF2 file path.
     *
     * @return string
     */
    public static function getNormalWoffTwoFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_NORMAL_WOFF_TWO_FILE);
    }

    /**
     * Get medium font name from configuration.
     *
     * @return string
     */
    public static function getMediumFontName(): string
    {
        return Config::getString(self::FONT_SETTING_MEDIUM_FONT_NAME);
    }

    /**
     * Get medium TTF file path.
     *
     * @return string
     */
    public static function getMediumTtfFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_MEDIUM_TTF_FILE);
    }

    /**
     * Get medium EOT file path.
     *
     * @return string
     */
    public static function getMediumEotFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_MEDIUM_EOT_FILE);
    }

    /**
     * Get medium WOFF file path.
     *
     * @return string
     */
    public static function getMediumWoffFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_MEDIUM_WOFF_FILE);
    }

    /**
     * Get medium WOFF2 file path.
     *
     * @return string
     */
    public static function getMediumWoffTwoFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_MEDIUM_WOFF_TWO_FILE);
    }

    /**
     * Get bold font name from configuration.
     *
     * @return string
     */
    public static function getBoldFontName(): string
    {
        return Config::getString(self::FONT_SETTING_BOLD_FONT_NAME);
    }

    /**
     * Get bold TTF file path.
     *
     * @return string
     */
    public static function getBoldTtfFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_BOLD_TTF_FILE);
    }

    /**
     * Get bold EOT file path.
     *
     * @return string
     */
    public static function getBoldEotFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_BOLD_EOT_FILE);
    }

    /**
     * Get bold WOFF file path.
     *
     * @return string
     */
    public static function getBoldWoffFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_BOLD_WOFF_FILE);
    }

    /**
     * Get bold WOFF2 file path.
     *
     * @return string
     */
    public static function getBoldWoffTwoFilePath(): string
    {
        return static::getFontPath(self::FONT_SETTING_BOLD_WOFF_TWO_FILE);
    }

    /**
     * Retrieve default or common variables used in the application.
     *
     * This method fetches various configuration values from Magento configuration
     * and constructs an array of variables commonly used throughout the application.
     *
     * @return array Array containing default or common variables
     */
    public static function getCommonVariables(): array
    {
        // Fetch font color from configuration; default to black (#000) if not configured
        $fontColor = static::getFontColor();

        // Fetch background color from configuration; default to white (#fff) if not configured
        $backgroundColor = static::getBackgroundColor();

        // Fetch page orientation from configuration
        $pageOrientation = static::getPageOrientation();

        // Determine if the page orientation is landscape
        $isLandscape = $pageOrientation === Orientations::LANDSCAPE;

        // Construct and return an array of common variables
        $variables = [
            'title' => '', // Placeholder for title, to be filled as needed
            'bottom_content' => '', // Placeholder for bottom content, to be filled as needed
            'is_landscape' => $isLandscape, // Flag indicating if the orientation is landscape
            'color' => [
                'font' => $fontColor, // Color of the font, defaulting to black if not configured
                'background' => $backgroundColor, // Background color, defaulting to white if not configured
            ],
            'font' => static::getCustomFonts(),
        ];

        return $variables;
    }

    /**
     * Get custom fonts configuration.
     *
     * @return array
     */
    public static function getCustomFonts(): array
    {
        $useCustomFont = static::getUseCustomFont();
        $googleFontFamilyName = static::getFontFamily();
        $googleFontChara = static::getFontFamilyCharacterSet();
        $fontFamilyFallback = static::getFontFamilyFallback();

        // If using custom fonts
        if ($useCustomFont) {
            $fontNameNormal = static::getFontName(static::getNormalFontName(), $fontFamilyFallback);
            $fontNameMedium = static::getFontName(static::getMediumFontName(), $fontFamilyFallback);
            $fontNameBold = static::getFontName(static::getBoldFontName(), $fontFamilyFallback);

            return [
                // Custom font flag
                'is_custom_font' => true,

                // Font names
                'font_name_n' => $fontNameNormal,
                'font_name_m' => $fontNameMedium,
                'font_name_b' => $fontNameBold,

                // Font paths for normal style
                'font_path_n_ttf' => static::getFontPath(static::getNormalTtfFilePath()),
                'font_path_n_eot' => static::getFontPath(static::getNormalEotFilePath()),
                'font_path_n_woff' => static::getFontPath(static::getNormalWoffFilePath()),
                'font_path_n_woff_two' => static::getFontPath(static::getNormalWoffTwoFilePath()),

                // Font paths for medium style
                'font_path_m_ttf' => static::getFontPath(static::getMediumTtfFilePath()),
                'font_path_m_eot' => static::getFontPath(static::getMediumEotFilePath()),
                'font_path_m_woff' => static::getFontPath(static::getMediumWoffFilePath()),
                'font_path_m_woff_two' => static::getFontPath(static::getMediumWoffTwoFilePath()),

                // Font paths for bold style
                'font_path_b_ttf' => static::getFontPath(static::getBoldTtfFilePath()),
                'font_path_b_eot' => static::getFontPath(static::getBoldEotFilePath()),
                'font_path_b_woff' => static::getFontPath(static::getBoldWoffFilePath()),
                'font_path_b_woff_two' => static::getFontPath(static::getBoldWoffTwoFilePath()),
            ];
        }

        // If not using custom fonts, fallback to Google Fonts
        $googleFontFamily = Str::replace(' ', '+', $googleFontFamilyName);
        $fontPath = $googleFontChara ? self::GOOGLE_FONT_BASE_URL . $googleFontFamily . '&subset=' . $googleFontChara : self::GOOGLE_FONT_BASE_URL . $googleFontFamily;

        return [
            'font_path' => $fontPath,
            'is_custom_font' => false,
            'font_name_n' => $fontFamilyFallback ? $googleFontFamilyName . ',' . $fontFamilyFallback : $googleFontFamilyName,
            'font_name_b' => $fontFamilyFallback ? $googleFontFamilyName . ',' . $fontFamilyFallback : $googleFontFamilyName,
            'font_name_m' => $fontFamilyFallback ? $googleFontFamilyName . ',' . $fontFamilyFallback : $googleFontFamilyName,
        ];
    }

    /**
     * Get background color from configuration.
     *
     * @return string
     */
    private static function getBackgroundColor(): string
    {
        return Config::getString(self::XML_PATH_BG_COLOR, '#fff');
    }

    /**
     * Get font name with fallback.
     *
     * @param string $configPath Configuration path for font name.
     * @param string|null $fontFamilyFallback Fallback font family.
     *
     * @return string
     */
    private static function getFontName(string $configPath, ?string $fontFamilyFallback): string
    {
        $fontName = Config::getString($configPath);

        return $fontFamilyFallback ? $fontName . ',' . $fontFamilyFallback : $fontName;
    }

    /**
     * Get font file path with media URL.
     *
     * @param string $configPath Configuration path for font file.
     *
     * @return string
     */
    private static function getFontPath(string $configPath): string
    {
        return Media::absolutePath(Path::join(self::FONTS_DIRECTORY,  Config::getString($configPath)));
    }
}
