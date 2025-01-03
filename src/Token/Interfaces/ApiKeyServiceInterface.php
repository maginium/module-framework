<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Token\Interfaces;

use Magento\Integration\Model\Integration as ApiKey;
use Pixicommerce\Foundation\Exceptions\LocalizedException;

/**
 * Interface ApiKeyServiceInterface.
 *
 * Defines the contract for API key-related services. This interface provides constants
 * for configuration paths and methods for managing API keys and their related operations.
 */
interface ApiKeyServiceInterface
{
    /**
     * Key for accessing token.
     */
    public const TOKEN = 'token';

    /**
     * Key for accessing backend policy id.
     */
    public const BACKEND_POLICY = 'Magento_Backend::all';

    /**
     * Key for accessing specific resources in API configurations.
     */
    public const RESOURCES = 'resources';

    /**
     * Key for accessing all available resources in API configurations.
     */
    public const ALL_RESOURCES = 'all_resources';

    /**
     * XML path for the integration name configuration.
     *
     * This value is used to define the name of the API integration.
     */
    public const XML_PATH_CONFIG_INTEGRATION_NAME = 'integration/general/name';

    /**
     * XML path for the developer's email configuration.
     *
     * This value is used to specify the email address of the developer responsible for the API.
     */
    public const XML_PATH_CONFIG_DEVELOPER_EMAIL = 'dev/developer/email';

    /**
     * Retrieves an existing API key by its name.
     *
     * @param string|null $name Name of the API key. Uses default configuration if not provided.
     *
     * @throws LocalizedException Throws when the API key is not found or misconfigured.
     *
     * @return ApiKey Returns the API key instance.
     */
    public function get(?string $name = null): ApiKey;

    /**
     * Creates a new API key and generates an access token for it.
     *
     * @param string|null $name Optional name for the API key. Uses default configuration if not provided.
     * @param string|null $email Optional email for the API key. Uses default configuration if not provided.
     * @param array $permissions List of permissions for the API key. Defaults to all permissions.
     *
     * @throws LocalizedException Throws when API key creation or token generation fails.
     *
     * @return string|null Returns the generated access token, or null if creation fails.
     */
    public function create(?string $name = null, ?string $email = null, array $permissions = ['*']): ?string;
}
