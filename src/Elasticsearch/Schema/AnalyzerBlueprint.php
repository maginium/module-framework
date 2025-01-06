<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Schema;

use Maginium\Framework\Elasticsearch\Connection;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Fluent;

/**
 * Class AnalyzerBlueprint.
 *
 * This class provides methods for defining and managing Elasticsearch analyzer settings for a given index.
 * It is used to configure various properties such as analyzers, tokenizers, char filters, and filters,
 * and then build the index analyzer settings that will be applied to an Elasticsearch index.
 * The class allows you to define the analyzer properties dynamically and then build and apply the settings
 * using the provided connection object.
 */
class AnalyzerBlueprint
{
    /**
     * The Connection object for this blueprint.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The index name for which the analyzer settings are being defined.
     *
     * @var string
     */
    protected string $index = '';

    /**
     * The parameters for the analyzer settings.
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * AnalyzerBlueprint constructor.
     *
     * @param string $index The name of the Elasticsearch index.
     */
    public function __construct(string $index)
    {
        $this->index = $index;
    }

    /**
     * Define the analyzer for the index.
     *
     * @param string $name The name of the analyzer.
     *
     * @return Definitions\AnalyzerPropertyDefinition The analyzer property definition.
     */
    public function analyzer(string $name): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('analyzer', $name);
    }

    /**
     * Define the tokenizer for the index.
     *
     * @param string $type The type of the tokenizer.
     *
     * @return Definitions\AnalyzerPropertyDefinition The tokenizer property definition.
     */
    public function tokenizer(string $type): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('tokenizer', $type);
    }

    /**
     * Define the character filter for the index analyzer.
     *
     * @param string $type The type of the character filter.
     *
     * @return Definitions\AnalyzerPropertyDefinition The char filter property definition.
     */
    public function charFilter(string $type): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('char_filter', $type);
    }

    /**
     * Define the filter for the index analyzer.
     *
     * @param string $type The type of the filter.
     *
     * @return Definitions\AnalyzerPropertyDefinition The filter property definition.
     */
    public function filter(string $type): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('filter', $type);
    }

    /**
     * Build and apply the index analyzer settings.
     *
     * This method sets the index on the provided connection and then applies the parameters
     * defined for the analyzer settings.
     *
     * @param Connection $connection The connection object to apply the analyzer settings.
     *
     * @return bool Returns false, as no explicit return value is necessary.
     */
    public function buildIndexAnalyzerSettings(Connection $connection): bool
    {
        $connection->setIndex($this->index);

        if ($this->parameters) {
            $this->_formatParams();
            $connection->indexAnalyzerSettings($this->parameters);
        }

        return false;
    }

    /**
     * Add a property definition to the analyzer settings.
     *
     * This helper method is used to dynamically add a property to the analyzer settings.
     *
     * @param string $config The configuration key (e.g., 'analyzer', 'tokenizer').
     * @param string $name The name of the property (e.g., the name of the analyzer).
     * @param array $parameters Additional parameters for the property.
     *
     * @return Definitions\AnalyzerPropertyDefinition The added property definition.
     */
    protected function addProperty(string $config, string $name, array $parameters = []): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addPropertyDefinition(Container::make(
            Definitions\AnalyzerPropertyDefinition::class,
            ['attributes' => array_merge(compact('config', 'name'), $parameters)],
        ));
    }

    /**
     * Add the property definition to the parameters.
     *
     * This method stores the property definition into the `$parameters['analysis']` array.
     *
     * @param Definitions\AnalyzerPropertyDefinition $definition The property definition to add.
     *
     * @return Definitions\AnalyzerPropertyDefinition The added property definition.
     */
    protected function addPropertyDefinition(Definitions\AnalyzerPropertyDefinition $definition): Definitions\AnalyzerPropertyDefinition
    {
        $this->parameters['analysis'][] = $definition;

        return $definition;
    }

    /**
     * Format the parameters for the analyzer settings.
     *
     * This helper method processes the parameters array to ensure the analysis properties
     * are properly formatted before applying them to the connection.
     */
    private function _formatParams(): void
    {
        if ($this->parameters && ! empty($this->parameters['analysis'])) {
            $properties = [];

            foreach ($this->parameters['analysis'] as $property) {
                $properties[] = $property instanceof Fluent ? $property->toArray() : $property;
            }

            $this->parameters['analysis'] = $properties;
        }
    }
}
