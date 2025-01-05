<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Maginium\Foundation\Interfaces\DataObjectInterface;

/**
 * Class EloquentModel.
 *
 * Extends the base Laravel Eloquent Model class and provides additional functionality
 * for custom query builders, global scope management, and connection handling. This class
 * ensures queries are built with enhanced flexibility and alignment with the framework's architecture.
 *
 * @property string|null $createdAtKey Custom field name for created_at timestamp
 * @property string|null $updatedAtKey Custom field name for updated_at timestamp
 */
abstract class EloquentModel extends BaseModel implements DataObjectInterface
{
}
