<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Resize\ResizeManager;
use Maginium\Framework\Support\Facade;

/**
 * Resizer.
 *
 * @see Resizer
 *
 * @method static void save(string $savePath)
 * @method static \Maginium\Framework\Resize\Resizer reset()
 * @method static \Maginium\Framework\Resize\Resizer setOptions(array $options)
 * @method static \Maginium\Framework\Resize\Resizer resize(int $newWidth, int $newHeight, array $options = [])
 * @method static \Maginium\Framework\Resize\Resizer sharpen(int $sharpness)
 * @method static \Maginium\Framework\Resize\Resizer crop(int $cropStartX, int $cropStartY, int $newWidth, int $newHeight, int $srcWidth = null, int $srcHeight = null)
 * @method static \Resizer open(string $filename)
 */
class Resizer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return ResizeManager::class;
    }
}
