<?php

declare(strict_types=1);

namespace Maginium\Framework\Locale;

use Magento\Framework\App\Area;
use Magento\Framework\Translate;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Locale\Interfaces\TranslationInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Log;
use MessageFormatter;

/**
 * Class TranslatorManager.
 *
 * Provides utilities for managing locale-specific functionalities, including
 * language directionality (right-to-left or left-to-right), translation loading,
 * and text formatting with arguments.
 */
class TranslatorManager extends Translate implements TranslationInterface
{
    /**
     * Load translations for a specified area (e.g., frontend, adminhtml).
     *
     * This method sets the area code and loads translations for the current area.
     * Optionally, translations can be reloaded by forcing a reload.
     *
     * @param string $areaCode
     * @param bool $forceReload
     *
     * @return void
     */
    public function load(?string $areaCode = Area::AREA_FRONTEND, bool $forceReload = false): void
    {
        try {
            // Load translations for the specified area
            $this->loadData($areaCode, $forceReload);
        } catch (Exception $e) {
            // Log the error for further analysis
            Log::critical("Error loading translations for area {$areaCode}: " . $e->getMessage());

            throw Exception::make("Error loading translations for area {$areaCode}",  $e);
        }
    }

    /**
     * Get all loaded translations.
     *
     * This method ensures that translations are loaded if they haven't been already.
     * It then retrieves the translations data, which is usually an associative array
     * containing all translations for the current locale and area.
     *
     * @throws Exception If loading translations fails.
     *
     * @return array The loaded translations, typically an associative array.
     */
    public function getTranslations(): array
    {
        return $this->getData();
    }

    /**
     * Translate a string using the translation system, with optional arguments for dynamic replacements.
     *
     * @param string $text The text to translate
     * @param array|null $arguments Optional arguments for dynamic replacements in the translation
     *
     * @return string The translated string
     */
    public function trans(string $text, ?array $arguments = []): string
    {
        try {
            // Format the translated string with the provided arguments
            return $this->translate($text, $arguments);
        } catch (Exception $e) {
            // Log the error and rethrow the exception
            Log::critical("Translation failed for text: {$text} - " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Translate a string using the translation system, with optional arguments for dynamic replacements.
     *
     * @param string $text The text to translate
     * @param array|null $arguments Optional arguments for dynamic replacements in the translation
     *
     * @return string The translated string
     */
    public function translate(string $text, ?array $arguments = []): string
    {
        // Handle escaped quotes in strings by converting them back to their original form
        $text = strtr($text, ['\"' => '"', "\\'" => "'"]);

        try {
            // Get the available translations
            $translations = $this->getTranslations();
            // Fallback to the original text if no translation is available
            $translatedText = Arr::keyExists($text, $translations) ? $translations[$text] : $text;

            // Format the translated string with the provided arguments
            return $this->format($translatedText, $arguments);
        } catch (Exception $e) {
            // Log the error and rethrow the exception
            Log::critical("Translation failed for text: {$text} - " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Format a string with the given arguments using MessageFormatter.
     *
     * @param string $text The text to format
     * @param array $arguments Arguments to inject into the formatted string
     *
     * @return string The formatted string
     */
    private function format(string $text, array $arguments): string
    {
        // Check if 'locale' option is present in arguments
        $locale = $arguments['locale'] ?? $this->getLocale(); // Fallback to default locale

        // Skip formatting if no placeholders are found in the text
        if (! str_contains($text, '{')) {
            return $text;
        }

        // Format the string with the arguments using the MessageFormatter class
        $formattedText = MessageFormatter::formatMessage($locale, $text, $arguments);

        // If formatting fails, return the original text as a fallback
        return $formattedText !== false ? $formattedText : $text;
    }
}
