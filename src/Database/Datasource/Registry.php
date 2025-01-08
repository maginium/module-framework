<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Datasource;

use Maginium\Foundation\Abstracts\DataSource\DataSourceRegistry;

/**
 * Registry.
 *
 * This class manages multiple service datasources, allowing dynamic access to datasources for any entity model.
 * It is designed to be extended and used for different entity types.
 */
class Registry extends DataSourceRegistry
{
    /**
     * Constructor to initialize the service datasources.
     *
     * @param array $datasources Associative array of entities and their data sources.
     */
    public function __construct(array $datasources = [])
    {
        parent::__construct($datasources);
    }
}
