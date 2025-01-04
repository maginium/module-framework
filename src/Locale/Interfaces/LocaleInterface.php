<?php

declare(strict_types=1);

namespace Maginium\Framework\Locale\Interfaces;

/**
 * Interface LocaleInterface.
 *
 * The `LocaleManager` class provides utilities for managing locale-specific
 * functionalities within the application. It includes methods for checking
 * language directionality (e.g., right-to-left or left-to-right) and managing
 * language codes.
 */
interface LocaleInterface
{
    /**
     * Check if the given locale is RTL.
     *
     * @param string $locale The locale code (e.g., 'en_US', 'ar_SA').
     *
     * @return bool Returns true if the locale is RTL, false otherwise.
     */
    public function isRtl(string $locale): bool;

    /**
     * Check if a given text contains Arabic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Arabic characters, false otherwise.
     */
    public function isArabic(string $text): bool;

    /**
     * Check if a given text contains Latin characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Latin characters, false otherwise.
     */
    public function isLatin(string $text): bool;

    /**
     * Check if a given text contains Armenian characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Armenian characters, false otherwise.
     */
    public function isArmenian(string $text): bool;

    /**
     * Check if a given text contains Bengali characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Bengali characters, false otherwise.
     */
    public function isBengali(string $text): bool;

    /**
     * Check if a given text contains Bopomofo characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Bopomofo characters, false otherwise.
     */
    public function isBopomofo(string $text): bool;

    /**
     * Check if a given text contains Braille characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Braille characters, false otherwise.
     */
    public function isBraille(string $text): bool;

    /**
     * Check if a given text contains Buhid characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Buhid characters, false otherwise.
     */
    public function isBuhid(string $text): bool;

    /**
     * Check if a given text contains Canadian Aboriginal characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Canadian Aboriginal characters, false otherwise.
     */
    public function isCanadianAboriginal(string $text): bool;

    /**
     * Check if a given text contains Cherokee characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Cherokee characters, false otherwise.
     */
    public function isCherokee(string $text): bool;

    /**
     * Check if a given text contains Cyrillic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Cyrillic characters, false otherwise.
     */
    public function isCyrillic(string $text): bool;

    /**
     * Check if a given text contains Devanagari characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Devanagari characters, false otherwise.
     */
    public function isDevanagari(string $text): bool;

    /**
     * Check if a given text contains Ethiopic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Ethiopic characters, false otherwise.
     */
    public function isEthiopic(string $text): bool;

    /**
     * Check if a given text contains Georgian characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Georgian characters, false otherwise.
     */
    public function isGeorgian(string $text): bool;

    /**
     * Check if a given text contains Greek characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Greek characters, false otherwise.
     */
    public function isGreek(string $text): bool;

    /**
     * Check if a given text contains Gujarati characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Gujarati characters, false otherwise.
     */
    public function isGujarati(string $text): bool;

    /**
     * Check if a given text contains Gurmukhi characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Gurmukhi characters, false otherwise.
     */
    public function isGurmukhi(string $text): bool;

    /**
     * Check if a given text contains Han characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Han characters, false otherwise.
     */
    public function isHan(string $text): bool;

    /**
     * Check if a given text contains Hangul characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hangul characters, false otherwise.
     */
    public function isHangul(string $text): bool;

    /**
     * Check if a given text contains Hanunoo characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hanunoo characters, false otherwise.
     */
    public function isHanunoo(string $text): bool;

    /**
     * Check if a given text contains Hebrew characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hebrew characters, false otherwise.
     */
    public function isHebrew(string $text): bool;

    /**
     * Check if a given text contains Hiragana characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Hiragana characters, false otherwise.
     */
    public function isHiragana(string $text): bool;

    /**
     * Check if a given text contains Inherited characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Inherited characters, false otherwise.
     */
    public function isInherited(string $text): bool;

    /**
     * Check if a given text contains Kannada characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Kannada characters, false otherwise.
     */
    public function isKannada(string $text): bool;

    /**
     * Check if a given text contains Katakana characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Katakana characters, false otherwise.
     */
    public function isKatakana(string $text): bool;

    /**
     * Check if a given text contains Khmer characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Khmer characters, false otherwise.
     */
    public function isKhmer(string $text): bool;

    /**
     * Check if a given text contains Lao characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Lao characters, false otherwise.
     */
    public function isLao(string $text): bool;

    /**
     * Check if a given text contains Limbu characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Limbu characters, false otherwise.
     */
    public function isLimbu(string $text): bool;

    /**
     * Check if a given text contains Malayalam characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Malayalam characters, false otherwise.
     */
    public function isMalayalam(string $text): bool;

    /**
     * Check if a given text contains Mongolian characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Mongolian characters, false otherwise.
     */
    public function isMongolian(string $text): bool;

    /**
     * Check if a given text contains Myanmar characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Myanmar characters, false otherwise.
     */
    public function isMyanmar(string $text): bool;

    /**
     * Check if a given text contains Ogham characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Ogham characters, false otherwise.
     */
    public function isOgham(string $text): bool;

    /**
     * Check if a given text contains Oriya characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Oriya characters, false otherwise.
     */
    public function isOriya(string $text): bool;

    /**
     * Check if a given text contains Runic characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Runic characters, false otherwise.
     */
    public function isRunic(string $text): bool;

    /**
     * Check if a given text contains Sinhala characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Sinhala characters, false otherwise.
     */
    public function isSinhala(string $text): bool;

    /**
     * Check if a given text contains Syriac characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Syriac characters, false otherwise.
     */
    public function isSyriac(string $text): bool;

    /**
     * Check if a given text contains Tagalog characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tagalog characters, false otherwise.
     */
    public function isTagalog(string $text): bool;

    /**
     * Check if a given text contains Tagbanwa characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tagbanwa characters, false otherwise.
     */
    public function isTagbanwa(string $text): bool;

    /**
     * Check if a given text contains Tai Le characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tai Le characters, false otherwise.
     */
    public function isTaiLe(string $text): bool;

    /**
     * Check if a given text contains Tamil characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tamil characters, false otherwise.
     */
    public function isTamil(string $text): bool;

    /**
     * Check if a given text contains Telugu characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Telugu characters, false otherwise.
     */
    public function isTelugu(string $text): bool;

    /**
     * Check if a given text contains Thaana characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Thaana characters, false otherwise.
     */
    public function isThaana(string $text): bool;

    /**
     * Check if a given text contains Thai characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Thai characters, false otherwise.
     */
    public function isThai(string $text): bool;

    /**
     * Check if a given text contains Tibetan characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Tibetan characters, false otherwise.
     */
    public function isTibetan(string $text): bool;

    /**
     * Check if a given text contains Yi characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Yi characters, false otherwise.
     */
    public function isYi(string $text): bool;

    /**
     * Check if a given text contains Chinese characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Chinese characters, false otherwise.
     */
    public function isChinese(string $text): bool;

    /**
     * Check if a given text contains Japanese characters.
     *
     * @param string $text The text to be checked.
     *
     * @return bool True if the text contains Japanese characters, false otherwise.
     */
    public function isJapanese(string $text): bool;
}
