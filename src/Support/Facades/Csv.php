<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\File\Csv as CsvManager;
use Maginium\Framework\Support\Facade;

/**
 * Csv facade class to interact with the CsvManager.
 *
 * This facade provides a simplified API for interacting with the underlying CsvManager class.
 * It includes methods for setting CSV file properties, reading and writing data, and appending data.
 *
 * @method static void refreshEventDispatcher()
 * @method static void setLineLength($length)
 * @method static void setDelimiter($delimiter)
 * @method static void setEnclosure($enclosure)
 * @method static array getData($file)
 * @method static array getDataPairs($file, $keyIndex = 0, $valueIndex = 1)
 * @method static void saveData($file, $data)
 * @method static void appendData($file, $data, $mode = 'w')
 *
 * @see CsvManager
 */
class Csv extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade, which in this case is the CsvManager.
     */
    protected static function getAccessor(): string
    {
        return CsvManager::class;
    }
}
