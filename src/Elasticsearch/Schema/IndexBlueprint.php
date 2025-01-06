<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Schema;

use Maginium\Framework\Elasticsearch\Connection;
use Maginium\Framework\Elasticsearch\Schema\Definitions\FieldDefinition;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Fluent;

/**
 * Class IndexBlueprint.
 *
 * This class defines the structure for an Elasticsearch index blueprint.
 * It provides methods for creating fields, setting settings, and building
 * the index creation and modification operations.
 */
class IndexBlueprint
{
    /**
     * The Connection object for interacting with Elasticsearch.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The name of the index.
     *
     * @var string
     */
    protected string $index = '';

    /**
     * The new index name, used for reindexing purposes.
     *
     * @var string|null
     */
    protected ?string $newIndex;

    /**
     * An array of parameters related to the index creation or modification.
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * Constructor for the IndexBlueprint class.
     *
     * @param string $index The index name.
     * @param string|null $newIndex The new index name, optional.
     */
    public function __construct($index, $newIndex = null)
    {
        $this->index = $index;
        $this->newIndex = $newIndex;
    }

    /**
     * Adds a 'text' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function text($field): FieldDefinition
    {
        return $this->addField('text', $field);
    }

    /**
     * Adds an 'array' field type to the index, internally treated as 'text'.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function array($field): FieldDefinition
    {
        return $this->addField('text', $field);
    }

    /**
     * Adds a 'boolean' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function boolean($field): FieldDefinition
    {
        return $this->addField('boolean', $field);
    }

    /**
     * Adds a 'keyword' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function keyword($field): FieldDefinition
    {
        return $this->addField('keyword', $field);
    }

    /**
     * Adds a 'long' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function long($field): FieldDefinition
    {
        return $this->addField('long', $field);
    }

    /**
     * Adds an 'integer' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function integer($field): FieldDefinition
    {
        return $this->addField('integer', $field);
    }

    /**
     * Adds a 'short' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function short($field): FieldDefinition
    {
        return $this->addField('short', $field);
    }

    /**
     * Adds a 'byte' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function byte($field): FieldDefinition
    {
        return $this->addField('byte', $field);
    }

    /**
     * Adds a 'double' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function double($field): FieldDefinition
    {
        return $this->addField('double', $field);
    }

    /**
     * Adds a 'float' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function float($field): FieldDefinition
    {
        return $this->addField('float', $field);
    }

    /**
     * Adds a 'half_float' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function halfFloat($field): FieldDefinition
    {
        return $this->addField('half_float', $field);
    }

    /**
     * Adds a 'scaled_float' field type to the index with an optional scaling factor.
     *
     * @param string $field The field name.
     * @param int $scalingFactor The scaling factor for the float.
     *
     * @return FieldDefinition
     */
    public function scaledFloat($field, $scalingFactor = 100): FieldDefinition
    {
        return $this->addField('scaled_float', $field, [
            'scaling_factor' => $scalingFactor,
        ]);
    }

    /**
     * Adds an 'unsigned_long' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function unsignedLong($field): FieldDefinition
    {
        return $this->addField('unsigned_long', $field);
    }

    /**
     * Adds a 'date' field type to the index with an optional format.
     *
     * @param string $field The field name.
     * @param string|null $format The date format.
     *
     * @return FieldDefinition
     */
    public function date($field, $format = null): FieldDefinition
    {
        if ($format) {
            return $this->addField('date', $field, ['format' => $format]);
        }

        return $this->addField('date', $field);
    }

    /**
     * Adds a 'geo_point' field type to the index for geographic data.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function geo($field): FieldDefinition
    {
        return $this->addField('geo_point', $field);
    }

    /**
     * Adds a 'nested' field type to the index, useful for nested documents.
     *
     * @param string $field The field name.
     * @param array $params Optional parameters for the nested field.
     *
     * @return FieldDefinition
     */
    public function nested($field, $params = []): FieldDefinition
    {
        return $this->addField('nested', $field, $params);
    }

    /**
     * Adds an 'alias' field type to the index for aliasing other fields.
     *
     * @param string $field The alias field name.
     * @param string $path The path to the field being aliased.
     *
     * @return FieldDefinition
     */
    public function alias($field, $path): FieldDefinition
    {
        return $this->addField('alias', $field, ['path' => $path]);
    }

    /**
     * Adds an 'ip' field type to the index.
     *
     * @param string $field The field name.
     *
     * @return FieldDefinition
     */
    public function ip($field): FieldDefinition
    {
        return $this->addField('ip', $field);
    }

    /**
     * Maps a custom property with a specified field type.
     *
     * @param string $field The field name.
     * @param string $type The field type.
     *
     * @return FieldDefinition
     */
    public function mapProperty($field, $type): FieldDefinition
    {
        return $this->addField($type, $field);
    }

    /**
     * Adds a setting to the index parameters.
     *
     * @param string $key The setting key.
     * @param mixed $value The setting value.
     */
    public function settings($key, $value): void
    {
        $this->parameters['settings'][$key] = $value;
    }

    /**
     * Maps a key-value pair to the 'map' section of the index parameters.
     *
     * @param string $key The key.
     * @param mixed $value The value.
     */
    public function map($key, $value): void
    {
        $this->parameters['map'][$key] = $value;
    }

    /**
     * Adds a custom field type to the index with optional parameters.
     *
     * @param string $type The field type.
     * @param string $field The field name.
     * @param array $parameters Optional additional parameters.
     *
     * @return FieldDefinition
     */
    public function field($type, $field, array $parameters = [])
    {
        return $this->addField($type, $field, $parameters);
    }

    /**
     * Builds and creates the index with the specified parameters.
     *
     * @param Connection $connection The Elasticsearch connection instance.
     */
    public function buildIndexCreate(Connection $connection): void
    {
        $connection->setIndex($this->index);

        if ($this->parameters) {
            $this->_formatParams();
            $connection->indexCreate($this->parameters);
        }
    }

    /**
     * Modifies an existing index with the provided parameters.
     *
     * @param Connection $connection The Elasticsearch connection instance.
     */
    public function buildIndexModify(Connection $connection): void
    {
        $connection->setIndex($this->index);

        if ($this->parameters) {
            $this->_formatParams();
            $connection->indexModify($this->parameters);
        }
    }

    /**
     * Helper function to add a field definition to the index parameters.
     *
     * @param string $type The field type.
     * @param string $field The field name.
     * @param array  $parameters Optional parameters for the field.
     *
     * @return FieldDefinition
     */
    protected function addField(string $type, string $field, array $parameters = []): FieldDefinition
    {
        // Use Container::make to create the FieldDefinition instance
        $fieldDefinition = Container::make(FieldDefinition::class, [
            'attributes' => array_merge(compact('type', 'field'), $parameters),
        ]);

        return $this->addFieldDefinition($fieldDefinition);
    }

    /**
     * Adds a field definition to the parameters array.
     *
     * @param FieldDefinition $definition The field definition.
     *
     * @return FieldDefinition
     */
    protected function addFieldDefinition($definition)
    {
        $this->parameters['properties'][] = $definition;

        return $definition;
    }

    /**
     * Formats the parameters to ensure compatibility with Elasticsearch.
     */
    private function _formatParams(): void
    {
        if ($this->parameters) {
            if (! empty($this->parameters['properties'])) {
                $properties = [];

                foreach ($this->parameters['properties'] as $property) {
                    if ($property instanceof Fluent) {
                        $properties[] = $property->toArray();
                    } else {
                        $properties[] = $property;
                    }
                }
                $this->parameters['properties'] = $properties;
            }
        }
    }
}
