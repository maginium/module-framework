<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Exceptions\InvalidCasterClassException;
use Maginium\Framework\Dto\Interfaces\CasterInterface;

/**
 * Custom attribute used to specify a caster class for transforming data in DTOs.
 *
 * The `CastWith` attribute allows for associating a specific caster class to a
 * data transfer object (DTO) field. The caster class is responsible for transforming
 * the field's data into the desired format. The caster class must implement the
 * `CasterInterface` and can accept additional arguments to assist in the transformation.
 *
 * @example
 * #[CastWith(Caster::class, 'argument1', 'argument2')]
 * public string $fullName;
 *
 * @see CasterInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class CastWith
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * List of arguments passed to the caster class constructor.
     *
     * This array holds any additional arguments that are provided when initializing
     * the caster class. These arguments will be forwarded to the constructor of the
     * caster class, enabling customization of the data transformation process.
     *
     * @var array<mixed>
     */
    public array $args;

    /**
     * Constructor to initialize the `CastWith` attribute.
     *
     * This constructor ensures that the specified caster class implements the
     * `CasterInterface`. If it doesn't, an exception is thrown. Additionally, it
     * stores the arguments that will be used to instantiate the caster class.
     *
     * @param string $casterClass The fully-qualified name of the caster class.
     * @param mixed  ...$args The arguments to be passed to the caster class constructor.
     *
     * @throws InvalidCasterClassException If the provided class does not implement `CasterInterface`.
     */
    public function __construct(
        public string $casterClass,
        mixed ...$args,
    ) {
        // Validate that the provided class implements the CasterInterface
        if (! is_subclass_of($this->casterClass, CasterInterface::class)) {
            // Throw an exception if the class does not implement CasterInterface
            InvalidCasterClassException::make($this->casterClass);
        }

        // Store the arguments to be passed to the caster class
        $this->args = $args;
    }
}
