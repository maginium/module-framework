<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

/**
 * Custom Blueprint class that extends the base Blueprint class.
 *
 * This class can be used to define additional custom methods for table creation
 * or schema manipulation in the migration system. For now, it acts as a direct
 * extension of Laravel's Blueprint, but can be extended with custom functionality
 * specific to the application's needs.
 */
class Blueprint extends BaseBlueprint
{
}
