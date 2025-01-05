<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum for search engines.
 *
 * This enum defines constants for different search engines supported by the application.
 *
 * @method static self ALGOLIA() Represents the Algolia search engine.
 * @method static self MEILI_SEARCH() Represents the MeiliSearch search engine.
 * @method static self ELASTIC_SEARCH() Represents the Elasticsearch search engine.
 */
class SearcherEngines extends Enum
{
    /**
     * Represents the Algolia search engine.
     */
    #[Label('Algolia')]
    #[Description('Algolia is a hosted search engine service for building search functionality.')]
    public const ALGOLIA = 'algolia';

    /**
     * Represents the MeiliSearch search engine.
     */
    #[Label('MeiliSearch')]
    #[Description('MeiliSearch is an open-source search engine that provides fast, relevant search results.')]
    public const MEILI_SEARCH = 'meilisearch';

    /**
     * Represents the Elasticsearch search engine.
     */
    #[Label('Elasticsearch')]
    #[Description('Elasticsearch is a distributed search and analytics engine built on Apache Lucene.')]
    public const ELASTIC_SEARCH = 'elasticsearch';
}
