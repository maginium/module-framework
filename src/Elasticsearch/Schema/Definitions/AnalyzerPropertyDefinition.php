<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Schema\Definitions;

use Maginium\Framework\Support\Fluent;

/**
 * Class AnalyzerPropertyDefinition.
 *
 * This class represents the definition of an Elasticsearch analyzer property.
 * It extends the Fluent class to allow dynamic method chaining for defining various
 * settings related to an analyzer, such as type, tokenizer, filters, etc.
 * The methods provide a fluent interface for setting properties on the analyzer.
 *
 * @method $this type(string|array $value) Sets the type of the analyzer.
 * @method $this tokenizer(string|array $value) Specifies the tokenizer for the analyzer.
 * @method $this filter(array $value) Defines an array of filters for the analyzer.
 * @method $this charFilter(array $value) Specifies character filters for the analyzer.
 * @method $this pattern(string|array $value) Defines the pattern for the analyzer.
 * @method $this mappings(string|array $value) Sets the mappings for the analyzer.
 * @method $this stopwords(string|array $value) Specifies stopwords for the analyzer.
 * @method $this replacement(string|array $value) Defines replacement values for the analyzer.
 */
class AnalyzerPropertyDefinition extends Fluent
{
    // The class itself does not need additional properties or methods as it relies
    // on the dynamic methods provided by the Fluent class to handle method chaining.
}
