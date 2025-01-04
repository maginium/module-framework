<?php

declare(strict_types=1);

namespace Maginium\Framework\Url\Interfaces;

use Magento\Framework\UrlInterface as BaseUrlInterface;

/**
 * Interface for defining custom URL constants and behaviors.
 *
 * This interface extends the base Magento `UrlInterface` to introduce additional constants
 * specific to the application's requirements, such as PWA and Web URL keys.
 *
 * Implementations of this interface can leverage these constants to standardize
 * the retrieval and management of URLs for various application contexts.
 */
interface UrlInterface extends BaseUrlInterface
{
    /**
     * Key representing the Progressive Web App (PWA) URL.
     *
     * @var string
     */
    public const PWA_URL = 'pwa_url';

    /**
     * Key representing the base Web URL.
     *
     * @var string
     */
    public const WEB_URL = 'base_url';

    /**
     * Generate the backend URL for a given route.
     *
     * Constructs the full backend URL using the provided route and appends
     * the configured backend front name for proper routing.
     *
     * @param string|null $route The route to generate the URL for. Defaults to an empty string.
     *
     * @return string The constructed backend URL.
     */
    public function getBackendUrl(?string $route = ''): string;
}
