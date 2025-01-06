<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Abstracts;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Magento\Eav\Model\Entity\Collection\AbstractCollection as EavAbstractCollection;
use Magento\Eav\Model\Entity\Collection\AbstractCollectionFactory as EavAbstractCollectionFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollectionFactory;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\EloquentModel;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Dto\Traits\WithData;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;

/**
 * Class AbstractRepository.
 */
abstract class AbstractRepository
{
    // Adds DTO logic to the repository.
    use WithData;

    /**
     * The query union statements.
     *
     * @var array
     */
    public $unions;

    /**
     * Regex patterns to detect SQL injection.
     */
    protected $sqlInjectionRegEx = [
        '/(%27)|(\')|(--)|(%23)|(#)/',
        '/((%3D)|(=))[^\n]*((%27)|(\')|(--)|(%3B)|(;))/',
        '/w*((%27)|(\'))((%6F)|o|(%4F))((%72)|r|(%52))/',
        '/((%27)|(\'))union/',
    ];

    /**
     * @var AbstractCollectionFactory|EavAbstractCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ModelInterfaceFactory
     */
    protected $modelFactory;

    /**
     * Repository constructor.
     *
     * @param mixed $model The model model model.
     * @param mixed $collection The model collection model.
     */
    public function __construct(
        $model,
        $collection,
    ) {
        $this->modelFactory = $model;
        $this->collectionFactory = $collection;

        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Get a collection of models.
     *
     * @param mixed ...$arguments The arguments to be passed to the collection constructor.
     *
     * @return DatabaseCollection|AbstractCollection|EavAbstractCollection The collection of models
     */
    abstract public function collection(mixed ...$arguments): DatabaseCollection|AbstractCollection|EavAbstractCollection;

    /**
     * Create a new model.
     *
     * @param mixed ...$arguments The arguments to be passed to the model constructor.
     *
     * @return ModelInterface|EloquentModel The newly created model.
     */
    abstract public function factory(mixed ...$arguments): ModelInterface|EloquentModel;

    /**
     * Check for SQL injection in a field.
     *
     * @param string $field The field value to check
     *
     * @throws Exception If SQL injection is detected
     *
     * @return string The sanitized field value
     */
    public function checkSqlInjection(string $field): string
    {
        foreach ($this->sqlInjectionRegEx as $regex) {
            if (Php::pregMatch($regex, $field)) {
                // Throw the exception
                // Throw the exception
                throw Exception::make('SQL injection detected: ' . $field);
            }
        }

        return $field;
    }

    /**
     * Get the model name from a given class, lowercased.
     *
     * @param string $class The class name.
     *
     * @return string The lowercased base class name.
     */
    public function getEntityName(): string
    {
        // Use ReflectionClass to get the base class of the given class
        $baseClass = $this->factory()->getTableName();

        // Return the base class name in lowercase
        return Str::lower($baseClass);
    }
}
