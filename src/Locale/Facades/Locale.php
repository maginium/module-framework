<?php

declare(strict_types=1);

namespace Maginium\Framework\Locale\Facades;

use Magento\Framework\Locale\ResolverInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Locale\Interfaces\LocaleInterface;
use Maginium\Framework\Locale\Interfaces\TranslationInterface;
use Maginium\Framework\Support\Facade;
use Maginium\Framework\Support\Reflection;

/**
 * Class Locale.
 *
 * This facade provides a simple interface to interact with the Locale service.
 * It proxies calls to the underlying LocaleInterface instance for managing
 * locale settings, including getting and setting the default locale, the
 * current locale, emulation of locales for specific scopes, and reverting to
 * previous locale settings.
 *
 * @method static bool isRtl(string $locale) Determine if the given locale uses a right-to-left writing system.
 * @method static bool isArabic(string $text) Check if the given text is in Arabic script.
 * @method static bool isLatin(string $text) Check if the given text is in Latin script.
 * @method static bool isArmenian(string $text) Check if the given text is in Armenian script.
 * @method static bool isBengali(string $text) Check if the given text is in Bengali script.
 * @method static bool isBopomofo(string $text) Check if the given text is in Bopomofo script.
 * @method static bool isBraille(string $text) Check if the given text is in Braille script.
 * @method static bool isBuhid(string $text) Check if the given text is in Buhid script.
 * @method static bool isCanadianAboriginal(string $text) Check if the given text is in Canadian Aboriginal script.
 * @method static bool isCherokee(string $text) Check if the given text is in Cherokee script.
 * @method static bool isCyrillic(string $text) Check if the given text is in Cyrillic script.
 * @method static bool isDevanagari(string $text) Check if the given text is in Devanagari script.
 * @method static bool isEthiopic(string $text) Check if the given text is in Ethiopic script.
 * @method static bool isGeorgian(string $text) Check if the given text is in Georgian script.
 * @method static bool isGreek(string $text) Check if the given text is in Greek script.
 * @method static bool isGujarati(string $text) Check if the given text is in Gujarati script.
 * @method static bool isGurmukhi(string $text) Check if the given text is in Gurmukhi script.
 * @method static bool isHan(string $text) Check if the given text is in Han script (Chinese, Japanese, Korean).
 * @method static bool isHangul(string $text) Check if the given text is in Hangul script (Korean).
 * @method static bool isHanunoo(string $text) Check if the given text is in Hanunoo script.
 * @method static bool isHebrew(string $text) Check if the given text is in Hebrew script.
 * @method static bool isHiragana(string $text) Check if the given text is in Hiragana script (Japanese).
 * @method static bool isInherited(string $text) Check if the given text is in an inherited script.
 * @method static bool isKannada(string $text) Check if the given text is in Kannada script.
 * @method static bool isKatakana(string $text) Check if the given text is in Katakana script (Japanese).
 * @method static bool isKhmer(string $text) Check if the given text is in Khmer script.
 * @method static bool isLao(string $text) Check if the given text is in Lao script.
 * @method static bool isLimbu(string $text) Check if the given text is in Limbu script.
 * @method static bool isMalayalam(string $text) Check if the given text is in Malayalam script.
 * @method static bool isMongolian(string $text) Check if the given text is in Mongolian script.
 * @method static bool isMyanmar(string $text) Check if the given text is in Myanmar script.
 * @method static bool isOgham(string $text) Check if the given text is in Ogham script.
 * @method static bool isOriya(string $text) Check if the given text is in Oriya script.
 * @method static bool isRunic(string $text) Check if the given text is in Runic script.
 * @method static bool isSinhala(string $text) Check if the given text is in Sinhala script.
 * @method static bool isSyriac(string $text) Check if the given text is in Syriac script.
 * @method static bool isTagalog(string $text) Check if the given text is in Tagalog script.
 * @method static bool isTagbanwa(string $text) Check if the given text is in Tagbanwa script.
 * @method static bool isTaiLe(string $text) Check if the given text is in Tai Le script.
 * @method static bool isTamil(string $text) Check if the given text is in Tamil script.
 * @method static bool isTelugu(string $text) Check if the given text is in Telugu script.
 * @method static bool isThaana(string $text) Check if the given text is in Thaana script (Maldivian).
 * @method static bool isThai(string $text) Check if the given text is in Thai script.
 * @method static bool isTibetan(string $text) Check if the given text is in Tibetan script.
 * @method static bool isYi(string $text) Check if the given text is in Yi script.
 * @method static bool isChinese(string $text) Check if the given text is in Chinese script.
 * @method static bool isJapanese(string $text) Check if the given text is in Japanese script.
 * @method static string getDefaultLocalePath() Return the path to the default locale.
 * @method static void setDefaultLocale(string $locale) Set the default locale code.
 * @method static string getDefaultLocale() Retrieve the default locale code.
 * @method static void setLocale(string $locale = null) Set the current locale.
 * @method static string getLocale() Retrieve the current locale code.
 * @method static void emulate(int $scopeId) Emulate a locale for a specific scope.
 * @method static void revert() Revert to the last locale used before the most recent emulation.
 * @method static void load(string $areaCode = Area::AREA_FRONTEND, bool $forceReload = false) Load translations for a specified area, optionally forcing a reload.
 * @method static array getTranslations() Get all loaded translations as an associative array.
 * @method static string translate(string $text, ?array $arguments = []) Translate a string with optional dynamic replacements.
 */
class Locale extends Facade
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
        return ResolverInterface::class;
    }

    /**
     * Proxy method calls to the locale interface.
     *
     * @param string $method The method name being called.
     * @param string[] $args The arguments passed to the method.
     *
     * @throws InvalidArgumentException If the method does not exist on the facade or the locale interface.
     *
     * @return mixed The result of the method call.
     */
    public static function __callStatic($method, $args)
    {
        // Resolve the underlying service instance
        $localeService = static::resolve(LocaleInterface::class);
        $translatorService = static::resolve(TranslationInterface::class);

        // Check if the method exists in the facade class
        if (Reflection::methodExists(static::getFacadeRoot(), $method)) {
            return call_user_func_array([static::getFacadeRoot(), $method], $args);
        }

        // Check if the method exists in the facade class
        if (Reflection::methodExists($translatorService, $method)) {
            return call_user_func_array([$translatorService, $method], $args);
        }

        // Check if the method exists on the LocaleInterface instance
        if (Reflection::methodExists($localeService, $method)) {
            return call_user_func_array([$localeService, $method], $args);
        }

        // If neither method exists, throw an exception
        throw InvalidArgumentException::make(
            __(
                'Method %1 does not exist on %2 or the underlying LocaleInterface.',
                $method,
                $localeService,
            ),
        );
    }
}
