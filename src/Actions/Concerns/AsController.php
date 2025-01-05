<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\Concerns;

use Magento\Framework\App\Area;
use Magento\Store\Api\Data\StoreInterface;
use Maginium\Customer\Models\Customer;
use Maginium\Foundation\Exceptions\BadRequestException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Pagination\Constants\Paginator as PaginatorConstants;
use Maginium\Framework\Response\Traits\ResponseBuilder;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Emulation;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Facades\StoreManager;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Maginium\User\Models\User;

/**
 * Trait AsController.
 *
 * Provides base functionality for controllers, including response handling and logging support.
 * This trait is intended to be used in controller classes to standardize response construction
 * and ensure proper logging configuration.
 *
 * Features:
 * - Incorporates the `ResponseBuilder` trait for managing responses.
 * - Automatically sets the logger's class name to the current class for consistent log tracing.
 *
 * Usage:
 * ```php
 * class ExampleController {
 *     use AsController;
 * }
 *
 * $controller = new ExampleController();
 * ```
 *
 * @method void assertValidPagination(int $page, int $size) Validate pagination parameters.
 * @method DataObject body() Get request body content into an DataObject.
 * @method string[] input() Get and decode JSON request body content into an array.
 * @method string apiVersion() Get the API version from the request URL.
 * @method DataObject headers() Retrieve all request headers as a DataObject.
 * @method mixed header(string $name) Get a specific request header.
 * @method DataObject params() Retrieve all request parameters as a DataObject.
 * @method mixed query(string $key, mixed $default = null) Get a specific request parameter.
 * @method string currentLanguage() Get the current language/locale.
 * @method mixed user() Get the authenticated user.
 * @method int|null userId() Get the user ID from the authenticated user.
 * @method string|null userGroup() Get the user group from the bearer token.
 * @method string|null userRole() Get the user role from the bearer token.
 * @method StoreInterface store() Get the current store.
 * @method int storeId() Get the current store ID.
 */
trait AsController
{
    // Trait for handling response construction and manipulation
    use ResponseBuilder;

    /**
     * Get the current store.
     *
     * @return StoreInterface The current store instance.
     */
    public function store(): StoreInterface
    {
        // Retrieve the current store or fallback to the default store view if not available
        return StoreManager::getStore() ?? StoreManager::getDefaultStoreView();
    }

    /**
     * Get a specific request parameter.
     *
     * @param string $key The parameter key.
     * @param mixed $default The default value if the parameter is not found.
     *
     * @return mixed The parameter value or the default value.
     */
    public function query(string $key, $default = null): mixed
    {
        return Request::query($key, $default);
    }

    /**
     * Retrieves the store ID from the store object.
     *
     * @return int The store ID.
     */
    public function getStoreId(): int
    {
        // Retrieve the store ID from the store object.
        return (int)$this->store()->getId();
    }

    /**
     * Retrieves the website ID associated with the current store.
     *
     * @return int The website ID.
     */
    public function getWebsiteId(): int
    {
        // Retrieve the website ID from the store object.
        return (int)$this->store()->getWebsiteId();
    }

    /**
     * Retrieve input values from the request.
     * If a specific key is provided, it returns the value for that key.
     * If no key is provided, it returns all input values.
     *
     * @param string|null $key The key of the input value. If null, returns all inputs.
     * @param mixed $default The default value to return if the key does not exist.
     *
     * @return mixed The value of the input key, or all inputs if key is null, or default value if not found.
     */
    public function input($key = null, $default = null): mixed
    {
        return Request::input($key, $default);
    }

    /**
     * Get and decode JSON request body content into an array.
     *
     * This method retrieves the raw content from the request body, checks for its validity,
     * and attempts to decode it from JSON into a PHP array. If the body is empty or the
     * JSON format is invalid, an exception is thrown with an appropriate error message.
     *
     * @throws InvalidArgumentException If the request body is empty or the JSON format is invalid.
     *
     * @return DataObject The decoded JSON data as a DataObject instance.
     */
    public function body(): DataObject
    {
        // Retrieve the raw request body content
        $content = Request::getContent();

        // Check if the request body is empty
        if (Validator::isEmpty($content)) {
            // If the content is empty, throw an exception with a message indicating the empty body
            throw InvalidArgumentException::make(__('Empty request body.'));
        }

        // Decode the JSON content into a PHP array or object
        $data = Json::decode($content);

        // Check if JSON decoding fails (null indicates failure)
        if ($data === null) {
            // If the decoding fails, throw an exception with a message indicating invalid JSON
            throw InvalidArgumentException::make(__('Invalid JSON format.'));
        }

        // Return the decoded data wrapped in a DataObject
        return DataObject::make($data);
    }

    /**
     * Get the API version from the request URL.
     *
     * @return string The API version.
     */
    public function apiVersion(): string
    {
        // Get the current request URL
        $requestUrl = Request::getRequestUri();

        // Parse the URL to get the path
        $parsedUrl = parse_url($requestUrl, PHP_URL_PATH);

        // Split the path into segments
        $pathSegments = Php::explode(SP, Str::trim($parsedUrl, SP));

        // Get the API version from the first index of the path segments
        $apiVersion = Str::lower($pathSegments[1]) ?? 'v1'; // Default to 'v1' if not found

        return $apiVersion;
    }

    /**
     * Retrieve all request headers as a DataObject.
     *
     * This method extracts all headers from the current HTTP request, formats them
     * into a DataObject instance, and returns it. This allows for cleaner access and
     * manipulation of request data within the application.
     *
     * @return DataObject The request headers wrapped in a DataObject.
     */
    public function headers(): DataObject
    {
        // Fetch all headers from the current request
        $headers = Request::headers();

        // Convert the array of headers into a DataObject instance for enhanced functionality
        return DataObject::make($headers);
    }

    /**
     * Get a specific request header.
     *
     * @param string $key The header key.
     *
     * @return mixed The header value.
     */
    public function header(string $key): mixed
    {
        return Request::header($key);
    }

    /**
     * Retrieve all request parameters as a DataObject.
     *
     * This method extracts all parameters from the current HTTP request, formats them
     * into a DataObject instance, and returns it. This allows for cleaner access and
     * manipulation of request data within the application.
     *
     * @return DataObject The request parameters wrapped in a DataObject.
     */
    public function params(): DataObject
    {
        // Fetch all parameters from the current request
        $params = Request::getParams();

        // Convert the array of parameters into a DataObject instance for enhanced functionality
        return DataObject::make($params);
    }

    /**
     * Get the current language/locale.
     *
     * @throws InvalidArgumentException If the request body is empty or the JSON format is invalid.
     *
     * @return string The language code.
     */
    public function language(): string
    {
        return Request::getLanguage() ?? 'en';
    }

    /**
     * Check if the user is logged in.
     *
     * This method checks if an authenticated user is available. It relies on the
     * `user()` method to retrieve the current authenticated user, and returns
     * a boolean indicating whether the user is logged in or not.
     *
     * @return bool True if the user is logged in, false otherwise.
     */
    public function isLoggedIn(): bool
    {
        // Check if the user is authenticated by calling the user() method
        return $this->userId() !== null;
    }

    /**
     * Get the user ID from the authenticated user.
     *
     * @return int|null The user ID, or null if not found.
     */
    public function userId(): ?int
    {
        // Retrieve the authenticated user
        $user = $this->user();

        // Return the user ID or null if no authenticated user
        return $user ? $user->getId() : null;
    }

    /**
     * Get the authenticated user.
     *
     * @return User|Customer|null The authenticated user object, or null if not found.
     */
    public function user(): mixed
    {
        // Retrieve the authenticated user from the request
        $user = Request::user();

        // Check if a user is authenticated and has an ID
        return $user && $user->getId() ? $user->getData() : null;
    }

    /**
     * Get the user group from the bearer token.
     *
     * @return string|null The user group, or null if not found.
     */
    public function userGroup(): ?string
    {
        // Retrieve the authenticated user
        $user = $this->user();

        // Return the user group or null if no authenticated user
        return $user ? $user->getGroup() : null;
    }

    /**
     * Get the user role from the bearer token.
     *
     * @return string|null The user role, or null if not found.
     */
    public function userRole(): ?string
    {
        // Retrieve the authenticated user
        $user = $this->user();

        // Return the user role or null if no authenticated user
        return $user ? $user->getRole() : null;
    }

    /**
     * Get the current store ID.
     *
     * @return int The current store ID.
     */
    public function storeId(): int
    {
        // Retrieve the current store or fallback to the default store view if not available
        $store = $this->getStore();

        // Return the store ID
        return (int)$store->getId();
    }

    /**
     * Starts the store emulation environment for frontend-related operations.
     *
     * This method emulates the store's frontend area to perform checks based
     * on the store's environment (such as customer verification).
     *
     * @param int|null $storeId The store ID to emulate.
     *
     * @return void
     */
    public function startEmulation(?int $storeId = null): void
    {
        // Default the `$field` to the primary key of the model if not explicitly provided.
        $storeId ??= $this->getStoreId();

        // Start the emulation of the specified store environment for frontend area
        Emulation::startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
    }

    /**
     * Ends the store emulation environment.
     *
     * This method stops the store emulation environment to revert to the default.
     *
     * @return void
     */
    public function stopEmulation(): void
    {
        // End the store emulation and revert back to the default environment
        Emulation::stopEnvironmentEmulation();
    }

    /**
     * Filter the columns from an array of models and return the filtered result.
     *
     * This method will call `getColumns()` to retrieve the specified columns, then filter
     * the array of models to include only those columns and return the result as a collection.
     *
     * @param array $models Array of models (associative arrays or objects).
     *
     * @return Collection Filtered models containing only the specified columns.
     */
    public function applyColumnFilter(array|Collection $models): Collection
    {
        // Get the specified columns from the request query
        $columns = $this->getColumns();

        $models = $models instanceof Collection ? $models : Collection::make($models);

        // If no columns are specified, return an empty collection
        if (Validator::isEmpty($columns)) {
            return $models;
        }

        // Convert models to a collection and filter columns using `only`
        $filteredModels = $models
            ->map(function($model) use ($columns) {
                // Ensure each model is an array and return only the specified columns
                return collect($model)->only($columns);
            });

        return $filteredModels;
    }

    /**
     * Retrieve a specific column from the request query parameter.
     *
     * This method filters the retrieved columns to include only the specified column.
     *
     * @param string $column The column name to retrieve.
     *
     * @return array An array containing the specified column, if it exists.
     */
    public function getColumn(string $column): array
    {
        // Fetch all columns and filter by the specified column
        return collect($this->getColumns())
            ->only($column)
            ->toArray();
    }

    /**
     * Retrieve all columns specified in the request query parameter.
     *
     * This method retrieves the 'columns' parameter from the request, splits it by
     * commas or pipes (`|`), and returns an array of column names. Defaults to ['*'].
     *
     * @return array An array of column names.
     */
    public function getColumns(): array
    {
        // Fetch the 'columns' query parameter, defaulting to an empty string
        $columns = Request::query('columns', '');

        // If columns are provided, split by comma or pipe
        return $columns ? preg_split('/[,|]+/', $columns) : ['*'];
    }

    /**
     * Validate pagination parameters.
     *
     * Ensures that the given page number and page size are within the permissible limits.
     * Throws specific exceptions if validation fails, with localized error messages.
     *
     * @param int $page The page number to validate.
     * @param int $size The page size to validate.
     *
     * @throws BadRequestException If the page number or size is invalid.
     * @throws LocalizedException If an unexpected error occurs during validation.
     *
     * @return void
     */
    public function assertValidPagination(int $page, int $size): void
    {
        try {
            // Validate the page number to ensure it meets the minimum allowed value.
            if ($page < PaginatorConstants::DEFAULT_PAGE) {
                // Throw an exception for an invalid page number.
                throw BadRequestException::make(__(
                    "Invalid page number '%1'. Page number must be at least %2.",
                    $page, // The invalid page number provided by the user.
                    PaginatorConstants::DEFAULT_PAGE, // The minimum allowed page number.
                ));
            }

            // Validate the page size to ensure it does not exceed the maximum limit.
            if ($size > PaginatorConstants::MAX_PER_PAGE) {
                // Throw an exception for exceeding the maximum page size.
                throw BadRequestException::make(__(
                    "Invalid page size '%1'. Page size cannot exceed the maximum allowed limit of %2.",
                    $size, // The invalid page size provided by the user.
                    PaginatorConstants::MAX_PER_PAGE, // The maximum allowed page size.
                ));
            }
        } catch (Exception $e) {
            // Handle any unexpected exceptions during validation.
            // Wrap the original exception in a LocalizedException to provide user-friendly error messages.
            throw LocalizedException::make(__(
                __('An error occurred during validation: %1', $e->getMessage()),
            ));
        }
    }
}
