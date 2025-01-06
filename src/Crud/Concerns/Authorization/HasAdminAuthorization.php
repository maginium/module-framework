<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Concerns\Authorization;

use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Crud\HttpController;
use Maginium\Framework\Support\Facades\AdminSession;

/**
 * Trait HasAdminAuthorization
 * Handles authorization for admin users, with a header check followed by session validation.
 *
 * @mixin HttpController
 */
trait HasAdminAuthorization
{
    use HasAuthorization;

    /**
     * Check if the current request has valid admin authorization.
     *
     * @return bool
     */
    public function checkAuthorization(): bool
    {
        return $this->checkAuthorizationHeader() || AdminSession::isLoggedIn();
    }

    /**
     * Validate admin authorization.
     *
     * @throws LocalizedException if the user is unauthorized.
     *
     * @return void
     */
    public function validateAuthorization(): void
    {
        if (! $this->checkAuthorization()) {
            throw LocalizedException::make(__('Unauthorized access for admin.'));
        }
    }
}
