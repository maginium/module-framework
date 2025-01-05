<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Illuminate\Contracts\Translation\HasLocalePreference as BaseHasLocalePreference;

/**
 * Interface HasLocalePreference.
 *
 * This interface defines a contract for models that have a preferred locale setting.
 * It is used to get the preferred locale of an model, which can be useful in applications
 * that support multiple languages or regions.
 */
interface HasLocalePreference extends BaseHasLocalePreference
{
    /**
     * Get the preferred locale of the model.
     *
     * This method should be implemented by any class that needs to provide information
     * about the preferred locale setting of the model. The locale is typically a string
     * such as 'en_US', 'fr_FR', etc. It can return null if no preference is set.
     *
     * @return string|null The preferred locale of the model, or null if not set.
     */
    public function preferredLocale(): ?string;
}
