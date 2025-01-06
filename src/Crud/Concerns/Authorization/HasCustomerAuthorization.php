<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Concerns\Authorization;

use Maginium\Customer\Facades\CustomerSession;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Crud\HttpController;

/**
 * Trait HasCustomerAuthorization
 * Handles authorization for customer users, with a header check followed by session validation.
 *
 * @mixin HttpController
 */
trait HasCustomerAuthorization
{
    use HasAuthorization;

    /**
     * Check if the current request has valid customer authorization.
     *
     * @return bool
     */
    public function checkAuthorization(): bool
    {
        return $this->checkAuthorizationHeader() || CustomerSession::isLoggedIn();
    }

    /**
     * Validate customer authorization.
     *
     * @throws LocalizedException if the user is unauthorized.
     *
     * @return void
     */
    public function validateAuthorization(): void
    {
        if (! $this->checkAuthorization()) {
            throw LocalizedException::make(__('Unauthorized access for customer.'));
        }
    }
}
