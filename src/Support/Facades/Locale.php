<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Locale\ResolverInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Locale\Interfaces\LocaleInterface;
use Maginium\Framework\Locale\Interfaces\TranslationInterface;
use Maginium\Framework\Support\Facade;
use Maginium\Framework\Support\Reflection;

/**
 * This facade provides a simple interface to interact with the Locale service.
 * It proxies calls to the underlying LocaleInterface instance for managing
 * locale settings, including getting and setting the default locale, the
 * current locale, emulation of locales for specific scopes, and reverting to
 * previous locale settings.
 *
 * @method static bool isRtl(string $locale)
 * @method static bool isArabic(string $text)
 * @method static bool isLatin(string $text)
 * @method static bool isArmenian(string $text)
 * @method static bool isBengali(string $text)
 * @method static bool isBopomofo(string $text)
 * @method static bool isBraille(string $text)
 * @method static bool isBuhid(string $text)
 * @method static bool isCanadianAboriginal(string $text)
 * @method static bool isCherokee(string $text)
 * @method static bool isCyrillic(string $text)
 * @method static bool isDevanagari(string $text)
 * @method static bool isEthiopic(string $text)
 * @method static bool isGeorgian(string $text)
 * @method static bool isGreek(string $text)
 * @method static bool isGujarati(string $text)
 * @method static bool isGurmukhi(string $text)
 * @method static bool isHan(string $text)
 * @method static bool isHangul(string $text)
 * @method static bool isHanunoo(string $text)
 * @method static bool isHebrew(string $text)
 * @method static bool isHiragana(string $text)
 * @method static bool isInherited(string $text)
 * @method static bool isKannada(string $text)
 * @method static bool isKatakana(string $text)
 * @method static bool isKhmer(string $text)
 * @method static bool isLao(string $text)
 * @method static bool isLimbu(string $text)
 * @method static bool isMalayalam(string $text)
 * @method static bool isMongolian(string $text)
 * @method static bool isMyanmar(string $text)
 * @method static bool isOgham(string $text)
 * @method static bool isOriya(string $text)
 * @method static bool isRunic(string $text)
 * @method static bool isSinhala(string $text)
 * @method static bool isSyriac(string $text)
 * @method static bool isTagalog(string $text)
 * @method static bool isTagbanwa(string $text)
 * @method static bool isTaiLe(string $text)
 * @method static bool isTamil(string $text)
 * @method static bool isTelugu(string $text)
 * @method static bool isThaana(string $text)
 * @method static bool isThai(string $text)
 * @method static bool isTibetan(string $text)
 * @method static bool isYi(string $text)
 * @method static bool isChinese(string $text)
 * @method static bool isJapanese(string $text)
 * @method static string getDefaultLocalePath()
 * @method static void setDefaultLocale(string $locale)
 * @method static string getDefaultLocale()
 * @method static void setLocale(string $locale = null)
 * @method static string getLocale()
 * @method static void emulate(int $scopeId)
 * @method static void revert()
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
