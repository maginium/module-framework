<?php

declare(strict_types=1);

namespace Maginium\Framework\Token\Services;

use Exception;
use Magento\Integration\Api\Exception\UserTokenException;
use Magento\Integration\Model\CustomUserContext;
use Maginium\Foundation\Exceptions\AuthenticationException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Token\Interfaces\AdminTokenServiceInterface;

/**
 * Service for managing admin access tokens.
 *
 * Handles the creation and revocation of admin tokens using Magento's
 * token services and custom business logic.
 */
class AdminTokenService extends AbstractTokenService implements AdminTokenServiceInterface
{
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
    public function create(int $userId): string
    {
        try {
            // Create a user context for the admin.
            // The context includes details such as the admin ID and user type.
            $context = $this->customUserContextFactory->create([
                'userId' => $userId, // Cast the admin ID to an integer for safety.
                'userType' => CustomUserContext::USER_TYPE_ADMIN, // Define the user type as "admin".
            ]);

            // Generate token parameters required for token creation.
            $params = $this->tokenParametersFactory->create();

            // Issue and return a new token using the created context and parameters.
            return $this->tokenIssuer->create($context, $params);
        } catch (Exception $exception) {
            // If an exception occurs, wrap it into an AuthenticationException
            // to provide a more meaningful error context.
            throw AuthenticationException::make(__('Failed to authenticate admin.'), $exception);
        }
    }

    /**
     * Revoke an access token for a user.
     *
     * This method attempts to revoke the access token associated
     * with a specific user. If revocation fails, it throws a
     * LocalizedException with a descriptive error message.
     * Successful revocation results in a return value of true.
     *
     * @param int $userId The user's user ID whose token is to be revoked.
     *
     * @throws LocalizedException If token revocation fails, an exception is thrown.
     *
     * @return bool True if the token was successfully revoked, false otherwise.
     */
    public function revoke(int $userId): bool
    {
        try {
            // Create a user context for the admin.
            // The context includes details such as the admin ID and user type.
            $context = $this->customUserContextFactory->create([
                'userId' => $userId, // Cast the admin ID to an integer for safety.
                'userType' => CustomUserContext::USER_TYPE_CUSTOMER, // Define the user type as "admin".
            ]);

            // Attempt to revoke the access token using the revoker service.
            $this->tokenRevoker->revokeFor($context);
        } catch (UserTokenException $exception) {
            // If revocation fails, throw a LocalizedException with a specific message.
            throw LocalizedException::make(__('Failed to revoke admin user\'s access tokens'), $exception);
        }

        // Return true indicating that the revocation was successful.
        return true;
    }
}
