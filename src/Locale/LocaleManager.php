<?php

declare(strict_types=1);

namespace Maginium\Framework\Locale;

use LasseRafn\StringScript;
use Maginium\Framework\Locale\Interfaces\LocaleInterface;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;

/**
 * Class LocaleManager.
 *
 * The `LocaleManager` class provides utilities for managing locale-specific
 * functionalities within the application. It includes methods for checking
 * language directionality (e.g., right-to-left or left-to-right) and managing
 * language codes.
 */
class LocaleManager implements LocaleInterface
{
    /**
     * List of RTL (Right-to-Left) language codes.
     *
     * @var array
     */
    protected static array $rtlLanguages = [
        'ar', // Arabic
        'fa', // Persian
        'he', // Hebrew
        'ur', // Urdu
        'dv', // Divehi
        'ps', // Pashto
        'syr', // Syriac
        'ug', // Uighur
        'sd', // Sindhi
        'ku', // Kurdish
    ];

    /**
     * Check if the given locale is RTL.
     *
     * @param string $locale The locale code (e.g., 'en_US', 'ar_SA').
     *
     * @return bool Returns true if the locale is RTL, false otherwise.
     */
    public function isRtl(string $locale): bool
    {
        // Extract the language code from the locale
        $languageCode = Str::substr($locale, 0, 2);

        // Check if the language code is in the RTL list
        return Php::inArray($languageCode, static::$rtlLanguages, true);
    }

    /**
     * Check if a given text contains Arabic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Arabic characters, false otherwise.
     */
    public function isArabic(string $text): bool
    {
        return StringScript::isArabic($text);
    }

    /**
     * Check if a given text contains Latin characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Latin characters, false otherwise.
     */
    public function isLatin(string $text): bool
    {
        return StringScript::isLatin($text);
    }

    /**
     * Check if a given text contains Armenian characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Armenian characters, false otherwise.
     */
    public function isArmenian(string $text): bool
    {
        return StringScript::isArmenian($text);
    }

    /**
     * Check if a given text contains Bengali characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Bengali characters, false otherwise.
     */
    public function isBengali(string $text): bool
    {
        return StringScript::isBengali($text);
    }

    /**
     * Check if a given text contains Bopomofo characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Bopomofo characters, false otherwise.
     */
    public function isBopomofo(string $text): bool
    {
        return StringScript::isBopomofo($text);
    }

    /**
     * Check if a given text contains Braille characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Braille characters, false otherwise.
     */
    public function isBraille(string $text): bool
    {
        return StringScript::isBraille($text);
    }

    /**
     * Check if a given text contains Buhid characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Buhid characters, false otherwise.
     */
    public function isBuhid(string $text): bool
    {
        return StringScript::isBuhid($text);
    }

    /**
     * Check if a given text contains Canadian Aboriginal characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Canadian Aboriginal characters, false otherwise.
     */
    public function isCanadianAboriginal(string $text): bool
    {
        return StringScript::isCanadian_Aboriginal($text);
    }

    /**
     * Check if a given text contains Cherokee characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Cherokee characters, false otherwise.
     */
    public function isCherokee(string $text): bool
    {
        return StringScript::isCherokee($text);
    }

    /**
     * Check if a given text contains Cyrillic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Cyrillic characters, false otherwise.
     */
    public function isCyrillic(string $text): bool
    {
        return StringScript::isCyrillic($text);
    }

    /**
     * Check if a given text contains Devanagari characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Devanagari characters, false otherwise.
     */
    public function isDevanagari(string $text): bool
    {
        return StringScript::isDevanagari($text);
    }

    /**
     * Check if a given text contains Ethiopic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Ethiopic characters, false otherwise.
     */
    public function isEthiopic(string $text): bool
    {
        return StringScript::isEthiopic($text);
    }

    /**
     * Check if a given text contains Georgian characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Georgian characters, false otherwise.
     */
    public function isGeorgian(string $text): bool
    {
        return StringScript::isGeorgian($text);
    }

    /**
     * Check if a given text contains Greek characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Greek characters, false otherwise.
     */
    public function isGreek(string $text): bool
    {
        return StringScript::isGreek($text);
    }

    /**
     * Check if a given text contains Gujarati characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Gujarati characters, false otherwise.
     */
    public function isGujarati(string $text): bool
    {
        return StringScript::isGujarati($text);
    }

    /**
     * Check if a given text contains Gurmukhi characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Gurmukhi characters, false otherwise.
     */
    public function isGurmukhi(string $text): bool
    {
        return StringScript::isGurmukhi($text);
    }

    /**
     * Check if a given text contains Han characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Han characters, false otherwise.
     */
    public function isHan(string $text): bool
    {
        return StringScript::isHan($text);
    }

    /**
     * Check if a given text contains Hangul characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hangul characters, false otherwise.
     */
    public function isHangul(string $text): bool
    {
        return StringScript::isHangul($text);
    }

    /**
     * Check if a given text contains Hanunoo characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hanunoo characters, false otherwise.
     */
    public function isHanunoo(string $text): bool
    {
        return StringScript::isHanunoo($text);
    }

    /**
     * Check if a given text contains Hebrew characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hebrew characters, false otherwise.
     */
    public function isHebrew(string $text): bool
    {
        return StringScript::isHebrew($text);
    }

    /**
     * Check if a given text contains Hiragana characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hiragana characters, false otherwise.
     */
    public function isHiragana(string $text): bool
    {
        return StringScript::isHiragana($text);
    }

    /**
     * Check if a given text contains Inherited characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Inherited characters, false otherwise.
     */
    public function isInherited(string $text): bool
    {
        return StringScript::isInherited($text);
    }

    /**
     * Check if a given text contains Kannada characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Kannada characters, false otherwise.
     */
    public function isKannada(string $text): bool
    {
        return StringScript::isKannada($text);
    }

    /**
     * Check if a given text contains Katakana characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Katakana characters, false otherwise.
     */
    public function isKatakana(string $text): bool
    {
        return StringScript::isKatakana($text);
    }

    /**
     * Check if a given text contains Khmer characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Khmer characters, false otherwise.
     */
    public function isKhmer(string $text): bool
    {
        return StringScript::isKhmer($text);
    }

    /**
     * Check if a given text contains Lao characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Lao characters, false otherwise.
     */
    public function isLao(string $text): bool
    {
        return StringScript::isLao($text);
    }

    /**
     * Check if a given text contains Limbu characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Limbu characters, false otherwise.
     */
    public function isLimbu(string $text): bool
    {
        return StringScript::isLimbu($text);
    }

    /**
     * Check if a given text contains Malayalam characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Malayalam characters, false otherwise.
     */
    public function isMalayalam(string $text): bool
    {
        return StringScript::isMalayalam($text);
    }

    /**
     * Check if a given text contains Mongolian characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Mongolian characters, false otherwise.
     */
    public function isMongolian(string $text): bool
    {
        return StringScript::isMongolian($text);
    }

    /**
     * Check if a given text contains Myanmar characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Myanmar characters, false otherwise.
     */
    public function isMyanmar(string $text): bool
    {
        return StringScript::isMyanmar($text);
    }

    /**
     * Check if a given text contains Ogham characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Ogham characters, false otherwise.
     */
    public function isOgham(string $text): bool
    {
        return StringScript::isOgham($text);
    }

    /**
     * Check if a given text contains Oriya characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Oriya characters, false otherwise.
     */
    public function isOriya(string $text): bool
    {
        return StringScript::isOriya($text);
    }

    /**
     * Check if a given text contains Runic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Runic characters, false otherwise.
     */
    public function isRunic(string $text): bool
    {
        return StringScript::isRunic($text);
    }

    /**
     * Check if a given text contains Sinhala characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Sinhala characters, false otherwise.
     */
    public function isSinhala(string $text): bool
    {
        return StringScript::isSinhala($text);
    }

    /**
     * Check if a given text contains Syriac characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Syriac characters, false otherwise.
     */
    public function isSyriac(string $text): bool
    {
        return StringScript::isSyriac($text);
    }

    /**
     * Check if a given text contains Tagalog characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tagalog characters, false otherwise.
     */
    public function isTagalog(string $text): bool
    {
        return StringScript::isTagalog($text);
    }

    /**
     * Check if a given text contains Tagbanwa characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tagbanwa characters, false otherwise.
     */
    public function isTagbanwa(string $text): bool
    {
        return StringScript::isTagbanwa($text);
    }

    /**
     * Check if a given text contains Tai Le characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tai Le characters, false otherwise.
     */
    public function isTaiLe(string $text): bool
    {
        return StringScript::isTaiLe($text);
    }

    /**
     * Check if a given text contains Tamil characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tamil characters, false otherwise.
     */
    public function isTamil(string $text): bool
    {
        return StringScript::isTamil($text);
    }

    /**
     * Check if a given text contains Telugu characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Telugu characters, false otherwise.
     */
    public function isTelugu(string $text): bool
    {
        return StringScript::isTelugu($text);
    }

    /**
     * Check if a given text contains Thaana characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Thaana characters, false otherwise.
     */
    public function isThaana(string $text): bool
    {
        return StringScript::isThaana($text);
    }

    /**
     * Check if a given text contains Thai characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Thai characters, false otherwise.
     */
    public function isThai(string $text): bool
    {
        return StringScript::isThai($text);
    }

    /**
     * Check if a given text contains Tibetan characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tibetan characters, false otherwise.
     */
    public function isTibetan(string $text): bool
    {
        return StringScript::isTibetan($text);
    }

    /**
     * Check if a given text contains Yi characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Yi characters, false otherwise.
     */
    public function isYi(string $text): bool
    {
        return StringScript::isYi($text);
    }

    /**
     * Check if a given text contains Chinese characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Chinese characters, false otherwise.
     */
    public function isChinese(string $text): bool
    {
        return StringScript::isChinese($text);
    }

    /**
     * Check if a given text contains Japanese characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Japanese characters, false otherwise.
     */
    public function isJapanese(string $text): bool
    {
        return StringScript::isJapanese($text);
    }
}
