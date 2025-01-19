<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Magento\Framework\Data\Collection;
use Magento\Store\Model\Store;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;

/**
 * Trait for custom entities with localization support.
 *
 * This trait provides methods for handling localizable attributes and fetching
 * data specific to a locale. Models that use this trait can manage localized
 * attributes and retrieve localized data based on the current locale.
 *
 * @template TKey of array-key
 * @template TValue
 */
trait HasLocalization
{
    /**
     * @var array List of localizable attributes for the model.
     *
     * This static property holds an array of attributes that can be localized.
     * Models using this trait can define their own localizable attributes.
     */
    protected static array $localizableAttributes = [];

    /**
     * @var ModelInterface|null Cached localized model for the current store.
     *
     * This property holds the localized data for a specific store. It is cached
     * to avoid repeated database calls for the same store's localized data.
     */
    protected ?ModelInterface $localizationModel = null;

    /**
     * Retrieve the list of localizable attributes for the model.
     *
     * @return array The localizable attributes of the model.
     *
     * This method returns the list of attributes that can be localized. If no
     * attributes are defined, it returns an empty array.
     */
    public static function getLocalizableAttributes(): array
    {
        return static::$localizableAttributes ?: [];
    }

    /**
     * Retrieve and cache localized data for a specific store and attribute.
     *
     * @param string $attribute The name of the attribute to retrieve localized data for.
     * @param int $storeId The ID of the store to retrieve data from.
     *
     * @return ModelInterface|null The localized model data for the specified store, or null if not found.
     *
     * This method checks if the localized data for the given attribute and store ID is already
     * cached. If not, it queries the database and caches the result.
     * Throws an exception if the attribute is not localizable.
     */
    public function getTranslation(string $attribute, int $storeId = Store::DEFAULT_STORE_ID): ?ModelInterface
    {
        // Ensure the attribute is defined as localizable.
        if (! in_array($attribute, static::getLocalizableAttributes(), true)) {
            throw InvalidArgumentException::make("The attribute '{$attribute}' is not localizable.");
        }

        // Query the database to fetch and cache the localized data for the specified store and attribute.
        $collection = $this->fetchData($storeId);

        // Retrieve the first item in the collection, if available.
        $localizedData = $collection->getFirstItem()->getData($attribute);

        // Cache the localized model data for future access.
        $this->localizationModel = $localizedData ?: null;

        return $this->localizationModel;
    }

    /**
     * Set localized data for a specific store and attribute.
     *
     * @param string $attribute The name of the attribute to set localized data for.
     * @param mixed $value The value to set for the localized attribute.
     * @param int $storeId The ID of the store (default is the main store).
     *
     * @return $this The current model instance, allowing method chaining.
     *
     * This method sets localized data for a specified store and attribute. If the
     * attribute is not in the list of localizable attributes, an exception is thrown.
     */
    public function setTranslation(string $attribute, $value, int $storeId = Store::DEFAULT_STORE_ID): self
    {
        // Retrieve or initialize localized model data for the specified store.
        $localizationModel = $this->getLocalizedData($storeId);

        // Ensure the attribute is defined as localizable.
        if (! in_array($attribute, static::getLocalizableAttributes(), true)) {
            throw InvalidArgumentException::make("The attribute '{$attribute}' is not localizable.");
        }

        // Set store ID and attribute value in the localized model.
        if ($localizationModel) {
            // Assign the store ID.
            $localizationModel->setData(Store::STORE_ID, $storeId);

            // Assign the localized value.
            $localizationModel->setData($attribute, $value);
        }

        // Return the current model instance for method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Fetch localized data collection for a specific store and attribute.
     *
     * @param int $storeId The ID of the store to retrieve localized data from.
     *
     * @return Collection The collection of localized data for the specified store and attribute.
     */
    protected function fetchData(int $storeId): Collection
    {
        // Ensure that the localization model is valid before fetching data.
        if (! $this->localizationModel instanceof ModelInterface) {
            throw InvalidArgumentException::make('Localization model is not properly initialized.');
        }

        // Get the model collection
        $collection = $this->localizationModel->getCollection();

        // Filter by both storeId and attribute.
        $collection->addFieldToFilter(Store::STORE_ID, $storeId);
        $collection->addFieldToFilter($this->getKeyName(), $this->getId());

        // Limit the collection to the first result.
        $collection->setCurPage(1);

        // Return the model collection
        return $collection;
    }

    /**
     * Magic getter to access localized attributes directly from the main model.
     *
     * @param string $name The name of the attribute to retrieve.
     *
     * @return mixed|null The localized value for the attribute, or null if not found.
     *
     * This magic method is used to access the localized data directly from the
     * main model. If the requested attribute is localized, it fetches the data
     * from the localized model.
     */
    public function __get(string $name)
    {
        // Check if the attribute is localizable and if localized data is available.
        if (in_array($name, static::getLocalizableAttributes(), true) && $this->localizationModel) {
            // If so, return the localized value for the attribute.
            return $this->localizationModel->getData($name);
        }

        // If not localized, return the standard model data (non-localized).
        return $this->_getData($name);
    }

    /**
     * Magic setter to set localized attributes directly from the main model.
     *
     * @param string $name The name of the attribute to set.
     * @param mixed $value The value to set for the attribute.
     *
     * @return void
     *
     * This magic method is used to set localized data directly from the main model.
     * If the attribute is localized, it sets the value on the localized model.
     * Otherwise, it sets the value on the standard model.
     */
    public function __set(string $name, $value): void
    {
        // Check if the attribute is localizable and if localized data is available.
        if (in_array($name, static::getLocalizableAttributes(), true) && $this->localizationModel) {
            // If so, set the value on the localized model.
            $this->localizationModel->setData($name, $value);
        } else {
            // If not localized, set the value on the standard model data.
            $this->setData($name, $value);
        }
    }
}
