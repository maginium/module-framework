<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Illuminate\Database\Schema\Blueprint;

/**
 * Interface IndexableDataPatchInterface.
 *
 * Provides a contract for classes that define custom indexes
 * for database schema tables.
 */
interface IndexableDataPatchInterface
{
    /**
     * Define indexes for the database schema table.
     *
     * @param Blueprint $table The Blueprint instance used to define indexes on the table.
     *
     * @return void
     */
    public function indexes(Blueprint $table): void;
}
