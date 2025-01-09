<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Concerns\Authorization;

use Magento\Authorization\Model\UserContextInterface;
use Maginium\Customer\Facades\Customer;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Crud\HttpController;
use Maginium\Framework\Support\Facades\Token;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Validator;

/**
 * Trait HasAuthorization
 * Handles authorization for customer users through header and session validation mechanisms.
 *
 * Provides utility methods for managing and verifying tokens.
 *
 * @mixin HttpController
 */
trait HasAuthorization
{
    /**
     * Retrieve the Authorization token from the request headers.
     *
     * @return string|null Returns the token if present, otherwise null.
     */
    private function getAuthorizationToken(): ?string
    {
        // Retrieve the Authorization header
        $authHeader = $this->header('Authorization');

        // Validate and parse the header if it starts with "Bearer"
        if (Validator::isString($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $parts = explode(' ', $authHeader);

            // Return the token part if the header format is correct
            return Php::count($parts) === 2 ? $parts[1] : null;
        }

        return null;
    }

    /**
     * Check if the request contains a valid Authorization header.
     *
     * @return bool True if the token is valid, false otherwise.
     */
    private function checkAuthorizationHeader(): bool
    {
        // Retrieve the token from the Authorization header
        $authToken = $this->getAuthorizationToken();

        // Validate the token
        return $authToken && $this->isValidAuthToken($authToken);
    }

    /**
     * Validate the provided authorization token.
     *
     * @param string $token The token to validate.
     *
     * @return bool True if the token is valid, false otherwise.
     */
    private function isValidAuthToken(string $token): bool
    {
        try {
            // Load the token using the Token facade
            $tokenModel = Token::loadByToken($token);

            // Verify that the token exists and is active
            return $tokenModel && $tokenModel->getId();
        } catch (Exception $e) {
            // Handle exceptions (e.g., log the error or rethrow if necessary)
            return false;
        }
    }

    /**
     * Get the current user associated with the authorization token.
     *
     * Determines the user type (Customer or Admin) and retrieves the user model
     * based on the `user_type_id` from the token.
     *
     * @return mixed|null Returns the user model if the token is valid and the user exists, otherwise null.
     */
    private function getCurrentUser()
    {
        // Retrieve the token from the Authorization header
        $authToken = $this->getAuthorizationToken();

        if ($authToken) {
            try {
                // Load the token and fetch its details
                $tokenModel = Token::loadByToken($authToken);

                if ($tokenModel) {
                    // Extract user information from the token
                    $userId = $tokenModel->getUserId();
                    $userTypeId = $tokenModel->getUserTypeId();

                    // Use match to determine the appropriate user class
                    return match ($userTypeId) {
                        UserContextInterface::USER_TYPE_CUSTOMER => Customer::loadById($userId),
                        UserContextInterface::USER_TYPE_ADMIN => User::loadById($userId),
                        default => throw Exception::make('Invalid user type in token.'),
                    };
                }
            } catch (Exception $e) {
                // Handle exceptions (e.g., log or suppress the error)
                // Log::error('Error fetching user: ' . $e->getMessage());
                return;
            }
        }

        // Return null if the token is not valid or the user cannot be determined
    }

    /**
     * Verify the user's session against the provided token.
     *
     * @return bool True if the session is valid, false otherwise.
     */
    private function validateUserSession(): bool
    {
        // Check the token and ensure a valid user session is active
        $user = $this->getCurrentUser();

        return $user && $user->isActive();
    }

    /**
     * Invalidate the authorization token to log the user out.
     *
     * @return bool True if the token was successfully invalidated, false otherwise.
     */
    private function invalidateToken(): bool
    {
        $authToken = $this->getAuthorizationToken();

        if ($authToken) {
            try {
                // Attempt to delete or deactivate the token
                $tokenModel = Token::loadByToken($authToken);

                if ($tokenModel) {
                    $tokenModel->invalidate();

                    return true;
                }
            } catch (Exception $e) {
                // Handle exceptions (e.g., log or suppress the error)
                return false;
            }
        }

        return false;
    }
}
