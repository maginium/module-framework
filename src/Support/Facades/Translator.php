<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Locale\Interfaces\TranslationInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for accessing translation services.
 *
 * @method static void loadTranslations(?string $areaCode = \Magento\Framework\App\Area::AREA_FRONTEND, ?bool $forceReload = false) Load translations for a specified area (e.g., frontend, adminhtml).
 * @method static array getTranslations() Get all loaded translations.
 * @method static string translate(string $text, ?array $arguments = []) Translate a string using the translation system, with optional arguments for dynamic replacements.
 * @method static string getLocale() Retrieve the current locale.
 * @method static TranslationInterface setLocale(string $locale) Set the locale and return the translation interface instance.
 *
 * @see TranslationInterface
 */
class Translator extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return TranslationInterface::class;
    }
}
