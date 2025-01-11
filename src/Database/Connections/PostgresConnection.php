<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Connections;

use Illuminate\Database\PostgresConnection as PostgresConnectionBase;

/**
 * PostgresConnection implements connection extension.
 */
class PostgresConnection extends PostgresConnectionBase
{
    use ExtendsConnection;
}
