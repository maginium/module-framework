<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Token\Services;

use Pixicommerce\Framework\Token\Interfaces\AdminTokenServiceInterface;
use Pixicommerce\Framework\Token\Interfaces\ApiKeyServiceInterface;
use Pixicommerce\Framework\Token\Interfaces\CustomerTokenServiceInterface;

/**
 * Service for managing user access tokens.
 *
 * This service handles the creation and revocation of customer, admin, and API key tokens.
 * It uses Magento's custom token services and encapsulates the token management logic.
 * The service ensures that token generation and revocation are handled securely and
 * exceptions are thrown when necessary.
 */
class TokenManager
{
    /**
     * The service responsible for managing API keys.
     *
     * @var ApiKeyServiceInterface
     */
    private ApiKeyServiceInterface $apiKeyService;

    /**
     * The service responsible for managing admin tokens.
     *
     * @var AdminTokenServiceInterface
     */
    private AdminTokenServiceInterface $adminTokenService;

    /**
     * The service responsible for managing customer tokens.
     *
     * @var CustomerTokenServiceInterface
     */
    private CustomerTokenServiceInterface $customerTokenService;

    /**
     * TokenManager constructor.
     *
     * Initializes the TokenManager with the necessary token services for customer,
     * admin, and API key management.
     *
     * @param ApiKeyServiceInterface $apiKeyService The API key service.
     * @param AdminTokenServiceInterface $adminTokenService The admin token service.
     * @param CustomerTokenServiceInterface $customerTokenService The customer token service.
     */
    public function __construct(
        ApiKeyServiceInterface $apiKeyService,
        AdminTokenServiceInterface $adminTokenService,
        CustomerTokenServiceInterface $customerTokenService,
    ) {
        $this->apiKeyService = $apiKeyService;
        $this->adminTokenService = $adminTokenService;
        $this->customerTokenService = $customerTokenService;
    }

    /**
     * Get the service for managing customer tokens.
     *
     * This method returns the service responsible for handling customer access tokens.
     * It can be used to generate or revoke customer tokens based on business logic.
     *
     * @return CustomerTokenServiceInterface The customer token service.
     */
    public function customer(): CustomerTokenServiceInterface
    {
        return $this->customerTokenService;
    }

    /**
     * Get the service for managing admin tokens.
     *
     * This method returns the service responsible for handling admin access tokens.
     * It can be used to generate or revoke admin tokens.
     *
     * @return AdminTokenServiceInterface The admin token service.
     */
    public function admin(): AdminTokenServiceInterface
    {
        return $this->adminTokenService;
    }

    /**
     * Get the service for managing API keys.
     *
     * This method returns the service responsible for handling API keys.
     * It can be used to generate or revoke API keys for different services.
     *
     * @return ApiKeyServiceInterface The API key service.
     */
    public function apiKey(): ApiKeyServiceInterface
    {
        return $this->apiKeyService;
    }
}
