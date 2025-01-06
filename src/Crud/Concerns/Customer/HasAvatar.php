<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Concerns\Customer;

use Maginium\Framework\Support\Facades\Avatar;

/**
 * Trait HasAvatar.
 *
 * Provides methods for managing a customer's avatar.
 * Includes functionality for retrieving avatar URLs in multiple formats.
 *
 * @method array|null getAvatar() Retrieve the avatar URLs in multiple formats (e.g., 'png', 'svg', 'webp'), or null if avatar generation fails.
 */
trait HasAvatar
{
    /**
     * Retrieve the avatar URLs of the customer in multiple formats.
     *
     * Generates avatars using the customer's full name if no avatar is set.
     *
     * @return array|null Associative array with avatar formats as keys (e.g., 'png', 'svg', 'webp'),
     *                    or null if avatar generation fails or no full name is available.
     */
    public function getAvatar(): ?array
    {
        // Safely retrieve the customer's full name
        $name = $this->getFullName() ?? 'Guest User';

        // Generate an avatar using the Avatar facade
        $avatar = Avatar::create($name);

        // Return avatar URLs in multiple formats, or null if generation fails
        return $avatar ? [
            'png' => $avatar->toPng(),
            'svg' => $avatar->toSvg(),
            'webp' => $avatar->toWebp(),
        ] : null;
    }
}
