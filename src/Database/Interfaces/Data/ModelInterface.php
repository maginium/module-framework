<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces\Data;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Maginium\Foundation\Interfaces\DataObjectInterface;
use Maginium\Framework\Database\Interfaces\IdentifiableInterface;

/**
 * Interface ModelInterface.
 *
 * This interface defines the contract for models.
 *
 * @method ModelInterface load(mixed $id, string|null $field = null) Load a model by its ID.
 * @method array toDataArray(array $keys = ['*']) Convert the model's data to an array format.
 * @method static ModelInterface make(array $attributes = []) Create a new model instance with the specified attributes.
 * @method self save() Save the current model instance to the database.
 * @method ModelInterface load($modelId, $field = null) Load a model instance by its ID or another field.
 * @method AbstractModel|null getModel() Retrieve the underlying Magento model instance.
 * @method void setModel(AbstractModel $model) Set the underlying Magento model instance.
 * @method string|null getEventObject() Retrieve the event object name associated with the model.
 * @method string|null getIdFieldName() Get the name of the ID field for the model.
 * @method string|null getKeyName() Get the primary key name of the model.
 * @method ModelInterface setKeyName(string $key) Set the primary key name of the model.
 * @method string getQualifiedKeyName() Get the fully qualified name of the primary key.
 * @method string|null getKeyType() Get the data type of the primary key.
 * @method ModelInterface setKeyType(string $type) Set the data type of the primary key.
 * @method string qualifyColumn(string $column) Qualify the given column with the table name.
 * @method string|array qualifyColumns(string|array $columns) Qualify multiple columns with the table name.
 * @method string getTableName() Get the table name associated with the model.
 * @method bool isDirty($keys = null) Determine if the model has unsaved changes.
 * @method bool isClean($keys = null) Determine if the model has no unsaved changes.
 * @method array toArray(array $keys = ['*']) Convert the model instance to an array.
 * @method array toDataArray(array $keys = ['*']) Convert the model instance to a data array.
 * @method ModelInterface fill(array $data) Fill the model with an array of attributes.
 * @method string|null getEventPrefix() Get the event prefix for the model.
 * @method void fireEvent(string $event, $data) Dispatch an event with the specified name and data.
 */
interface ModelInterface extends DataObjectInterface, IdentifiableInterface
{
    /**
     * Retrieve collection instance.
     *
     * @return AbstractCollection
     */
    public function getCollection();
}
