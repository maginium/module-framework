<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Query;

use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;

/**
 * Class Grammar.
 *
 * Custom query grammar for handling Elasticsearch queries. Extends the base Grammar class from Laravel's
 * database query builder, allowing for potential interception or custom modifications of SQL-like queries
 * that are sent to Elasticsearch. This class is designed to be extended or modified as needed to integrate
 * Elasticsearch query syntax.
 */
class Grammar extends BaseGrammar
{
    // In case we need to intercept something at some point
}
