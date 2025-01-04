<?php

declare(strict_types=1);

namespace Maginium\Framework\Locale\Interfaces;

use Magento\Framework\App\Area;
use Magento\Framework\Translate;
use Magento\Framework\TranslateInterface;
use Maginium\Foundation\Exceptions\Exception;

/**
 * Interface TranslationInterface.
 *
 * Provides utilities for managing locale-specific functionalities, including
 * language directionality (right-to-left or left-to-right), translation loading,
 * and text formatting with arguments.
 */
interface TranslationInterface extends TranslateInterface
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
    public function load(string $areaCode = Area::AREA_FRONTEND, bool $forceReload = false): void;

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
    public function getTranslations(): array;

    /**
     * Translate a string using the translation system, with optional arguments for dynamic replacements.
     *
     * @param string $text The text to translate
     * @param array|null $arguments Optional arguments for dynamic replacements in the translation
     *
     * @return string The translated string
     */
    public function translate(string $text, ?array $arguments = []): string;
}
