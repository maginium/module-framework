<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Token\Services;

use Exception;
use Magento\Integration\Api\UserTokenIssuerInterface;
use Magento\Integration\Api\UserTokenRevokerInterface;
use Magento\Integration\Model\CustomUserContextFactory;
use Magento\Integration\Model\UserToken\UserTokenParametersFactory;
use Pixicommerce\Foundation\Exceptions\AuthenticationException;
use Pixicommerce\Foundation\Exceptions\LocalizedException;
use Pixicommerce\Framework\Actions\Concerns\AsAction;
use Pixicommerce\Framework\Support\Facades\Log;

/**
 * Class AbstractTokenService.
 *
 * Provides a base structure for token management services, including creation,
 * retrieval, and revocation of user access tokens. Subclasses must implement
 * specific behavior for creating and revoking tokens.
 */
abstract class AbstractTokenService
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * Token issuer for generating tokens.
     *
     * @var UserTokenIssuerInterface
     */
    protected UserTokenIssuerInterface $tokenIssuer;

    /**
     * Factory for creating user token parameters.
     *
     * @var UserTokenParametersFactory
     */
    protected UserTokenParametersFactory $tokenParametersFactory;

    /**
     * Factory for creating custom user contexts.
     *
     * @var CustomUserContextFactory
     */
    protected CustomUserContextFactory $customUserContextFactory;

    /**
     * @var UserTokenRevokerInterface
     */
    protected UserTokenRevokerInterface $tokenRevoker;

    /**
     * Constructor for the AbstractTokenService.
     *
     * Initializes dependencies required for token issuance, revocation, and parameter generation.
     *
     * @param UserTokenIssuerInterface $tokenIssuer Responsible for issuing tokens.
     * @param UserTokenRevokerInterface $tokenRevoker Responsible for revoking tokens.
     * @param UserTokenParametersFactory $tokenParametersFactory Factory for generating token parameters.
     * @param CustomUserContextFactory $customUserContextFactory Factory for creating user contexts.
     */
    public function __construct(
        UserTokenIssuerInterface $tokenIssuer,
        UserTokenRevokerInterface $tokenRevoker,
        UserTokenParametersFactory $tokenParametersFactory,
        CustomUserContextFactory $customUserContextFactory,
    ) {
        $this->tokenIssuer = $tokenIssuer;
        $this->tokenRevoker = $tokenRevoker;
        $this->tokenParametersFactory = $tokenParametersFactory;
        $this->customUserContextFactory = $customUserContextFactory;

        // Set the class name for debugging purposes in the logging mechanism.
        Log::setClassName(static::class);
    }

    /**
     * Create an access token for a user.
     *
     * This method generates a new access token for a user using
     * Magento's custom user context and token issuer. It handles
     * exceptions gracefully and wraps them into a custom
     * AuthenticationException when errors occur.
     *
     * @param int $userId The user id whose token is to be created.
     *
     * @throws AuthenticationException If token generation fails.
     *
     * @return string The generated access token.
     */
    abstract public function create(int $userId): string;

    /**
     * Revoke an access token for a customer.
     *
     * This method attempts to revoke the access token associated
     * with a specific customer. If revocation fails, it throws a
     * LocalizedException with a descriptive error message.
     * Successful revocation results in a return value of true.
     *
     * @param int $userId The customer's user ID whose token is to be revoked.
     *
     * @throws LocalizedException If token revocation fails, an exception is thrown.
     *
     * @return bool True if the token was successfully revoked, false otherwise.
     */
    abstract public function revoke(int $userId): bool;

    /**
     * Revoke an access token for a user.
     *
     * @param int $userId The user ID for which the token needs to be revoked.
     *
     * @return bool True if the token was successfully revoked, false otherwise.
     */
    public function revokeAccessToken(int $userId): bool
    {
        try {
            // Attempt to revoke the token
            return $this->revoke($userId);
        } catch (LocalizedException $e) {
            // Log localized exceptions
            Log::error(sprintf(
                'Error revoking token for User ID %d: %s',
                $userId,
                $e->getMessage(),
            ));
        } catch (Exception $e) {
            // Log unexpected errors
            Log::error(sprintf(
                'Unexpected error revoking token for User ID %d: %s',
                $userId,
                $e->getMessage(),
            ));
        }

        // Return false indicating the token could not be revoked
        return false;
    }
}
