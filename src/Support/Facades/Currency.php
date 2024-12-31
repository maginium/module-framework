<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Currency\Interfaces\CurrencyInterface;
use Maginium\Framework\Support\Facade;

/**
 * Class Currency.
 *
 * Facade for interacting with the Currency service.
 *
 *
 * @method static array getFormatOptions()
 *     Get format options.
 *     Returns:
 *     - array: The format options.
 * @method static string getCurrencySymbol(string $currency)
 *     Get the currency symbol.
 *     Parameters:
 *     - $currency: The currency code.
 *     Returns:
 *     - string: The currency symbol.
 * @method static array getCurrencyDefaultConfig(string $currencyCode)
 *     Get the default configuration for the currency.
 *     Parameters:
 *     - $currencyCode: The currency code.
 *     Returns:
 *     - array: The default configuration.
 * @method static array getAllowedCurrenciesByScope(array $scopeData)
 *     Get allowed currencies based on scope data.
 *     Parameters:
 *     - $scopeData: Scope data to determine allowed currencies.
 *     Returns:
 *     - array: Allowed currencies.
 * @method static array getSavedConfig(string $code, array $scope)
 *     Get the saved configuration based on the code and scope.
 *     Parameters:
 *     - $code: Configuration code.
 *     - $scope: Scope data.
 *     Returns:
 *     - array: The saved configuration.
 * @method static array getSavedDefaultConfig(string $code)
 *     Get the saved default configuration based on the code.
 *     Parameters:
 *     - $code: Configuration code.
 *     Returns:
 *     - array: The saved default configuration.
 * @method static array getSavedWebsiteConfig(string $code, int $websiteId)
 *     Get the saved website configuration based on the code and website ID.
 *     Parameters:
 *     - $code: Configuration code.
 *     - $websiteId: Website ID.
 *     Returns:
 *     - array: The saved website configuration.
 * @method static array getCurrencyConfig(string $code, ?int $storeId = null)
 *     Get the currency configuration based on the code and store ID.
 *     Parameters:
 *     - $code: Currency code.
 *     - $storeId: Store ID (optional).
 *     Returns:
 *     - array: The currency configuration.
 * @method static string getLocaleShowSymbol(string $code, string $showSymbol, string $symbol)
 *     Get the locale-specific display of the symbol.
 *     Parameters:
 *     - $code: Currency code.
 *     - $showSymbol: Whether to show the symbol.
 *     - $symbol: The currency symbol.
 *     Returns:
 *     - string: The locale-specific symbol display.
 * @method static string getDirectoryCurrency(string $result, int $decimal, array $original, array $config)
 *     Get the formatted directory currency.
 *     Parameters:
 *     - $result: The result string.
 *     - $decimal: Number of decimal places.
 *     - $original: Original currency data.
 *     - $config: Configuration settings.
 *     Returns:
 *     - string: The formatted directory currency.
 * @method static string processShowSymbol(string $symbol, string $content, string $options, array $negative, ?string $default = null)
 *     Process and format the symbol display.
 *     Parameters:
 *     - $symbol: The currency symbol.
 *     - $content: The content to be formatted.
 *     - $options: Formatting options.
 *     - $negative: Negative formatting options.
 *     - $default: Default symbol (optional).
 *     Returns:
 *     - string: The processed symbol display.
 * @method static string getFormattedPrice(float $price, $storeId = null)
 *     Get the formatted price based on currency and store ID.
 *     Parameters:
 *     - $price: The price to be formatted.
 *     - $storeId: The store ID (optional).
 *     Returns:
 *     - string: The formatted price.
 * @method static string formatPrice(float $price, $storeId = null)
 *     Format the price based on currency and store ID.
 *     Parameters:
 *     - $price: The price to be formatted.
 *     - $storeId: The store ID (optional).
 *     Returns:
 *     - string: The formatted price.
 * @method static string getCurrencyCode()
 *     Get the currency code for the current store.
 *     Returns:
 *     - string: The currency code.
 * @method static string getLocaleCode()
 *     Get the locale code.
 *     Returns:
 *     - string: The locale code.
 * @method static string formatCurrencyText($currency, $price, $storeId = null)
 *     Format currency text based on currency code, price, and store ID.
 *     Parameters:
 *     - $currency: The currency code.
 *     - $price: The price to be formatted.
 *     - $storeId: The store ID (optional).
 *     Returns:
 *     - string: The formatted currency text.
 * @method static mixed getFormatByCurrency($currencyCode, $storeId = null)
 *     Get the currency format based on currency code and store ID.
 *     Parameters:
 *     - $currencyCode: The currency code.
 *     - $storeId: The store ID (optional).
 *     Returns:
 *     - mixed: The currency format.
 * @method static string sanitize(string $data)
 *     Sanitize a string by removing HTML tags and special characters.
 *     Parameters:
 *     - $data: The string to be sanitized.
 *     Returns:
 *     - string: The sanitized string.
 *
 * @see CurrencyInterface
 */
class Currency extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return CurrencyInterface::class;
    }
}
