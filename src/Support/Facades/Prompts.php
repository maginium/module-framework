<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Laravel\Prompts\Progress;
use Maginium\Framework\Prompts\Interfaces\PromptsInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Prompts service.
 *
 * This class acts as a simplified interface to access the PromptsInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static int|string search(string $label, Closure $options, string $placeholder = '', int $scroll = 5, $validate = null, string $hint = '', $required = true, ?Closure $transform = null)
 * @method static string text(string $label, string $placeholder = '', string $default = '', bool|string $required = false, mixed $validate = null, string $hint = '', ?Closure $transform = null)
 * @method static string textarea(string $label, string $placeholder = '', string $default = '', bool|string $required = false, mixed $validate = null, string $hint = '', int $rows = 5, ?Closure $transform = null)
 * @method static string password(string $label, string $placeholder = '', bool|string $required = false, mixed $validate = null, string $hint = '', ?Closure $transform = null)
 * @method static int|string select(string $label, array|Collection $options, int|string|null $default = null, int $scroll = 5, mixed $validate = null, string $hint = '', bool|string $required = true, ?Closure $transform = null)
 * @method static array<int|string> multiselect(string $label, array|Collection $options, array|Collection $default = [], int $scroll = 5, bool|string $required = false, mixed $validate = null, string $hint = 'Use the space bar to select options.', ?Closure $transform = null)
 * @method static bool confirm(string $label, bool $default = true, string $yes = 'Yes', string $no = 'No', bool|string $required = false, mixed $validate = null, string $hint = '', ?Closure $transform = null)
 * @method static void alert(string $message)
 * @method static void info(string $message)
 * @method static void warning(string $message)
 * @method static void error(string $message)
 * @method static void success(string $message)
 * @method static void intro(string $message)
 * @method static void outro(string $message)
 * @method static void table(array $headers = [], ?array $rows = null)
 * @method static Progress progress(string $label, $steps, ?Closure $callback = null, string $hint = '')
 * @method static FormBuilder form()
 * @method static mixed spinner(Closure $callback, string $message = '') Show the spinner while executing the provided callback.
 *
 * @see PromptsInterface
 */
class Prompts extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return PromptsInterface::class;
    }
}
