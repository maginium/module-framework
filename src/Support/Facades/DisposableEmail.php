<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Beeyev\DisposableEmailFilter\DisposableEmailFilter as DisposableEmailFilterManager;
use Maginium\Framework\Support\Facade;

/**
 * @method static \Beeyev\DisposableEmailFilter\DisposableEmailDomains\DisposableEmailDomains disposableEmailDomains()
 * @method static \Beeyev\DisposableEmailFilter\CustomEmailDomainFilter\CustomEmailDomainFilterInterface blacklistedDomains()
 * @method static \Beeyev\DisposableEmailFilter\CustomEmailDomainFilter\CustomEmailDomainFilterInterface whitelistedDomains()
 * @method static bool isDisposableEmailAddress(string $emailAddress)
 * @method static bool isEmailAddressValid(string $emailAddress)
 *
 * @see DisposableEmailFilterManager
 */
class DisposableEmail extends Facade
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
        return DisposableEmailFilterManager::class;
    }
}
