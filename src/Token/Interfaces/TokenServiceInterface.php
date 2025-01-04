<?php

declare(strict_types=1);

namespace Maginium\Framework\Token\Interfaces;

use Maginium\Foundation\Exceptions\AuthenticationException;
use Maginium\Foundation\Exceptions\LocalizedException;

/**
 * Interface TokenServiceInterface.
 *
 * Defines the contract for token management services. Provides methods
 * for creating, retrieving, and revoking access tokens.
 */
interface TokenServiceInterface
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
    public function create(int $userId): string;

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
    public function revoke(int $userId): bool;
}
