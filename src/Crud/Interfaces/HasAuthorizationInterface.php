<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\Exception;

/**
 * Interface HasAuthorizationInterface
 * Defines authorization method signatures for user roles.
 */
interface HasAuthorizationInterface
{
    /**
     * Check if the request has valid authorization.
     *
     * @return bool
     */
    public function checkAuthorization(): bool;

    /**
     * Validate authorization based on the request.
     *
     * @throws Exception If the request is unauthorized.
     *
     * @return void
     */
    public function validateAuthorization(): void;
}
