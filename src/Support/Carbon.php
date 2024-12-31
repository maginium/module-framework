<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable as BaseCarbonImmutable;
use Illuminate\Support\Traits\Conditionable;
use Maginium\Framework\Support\Traits\Dumpable;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

/**
 * Class Carbon.
 *
 * This class extends the base Carbon class and integrates additional functionality such as
 * conditionable traits and dumpable debugging tools. It also adds custom methods like `createFromId`
 * to create Carbon instances from unique IDs such as UUIDs or ULIDs.
 *
 * The class supports time manipulation with `setTestNow` for both mutable and immutable Carbon instances.
 */
class Carbon extends BaseCarbon
{
    // Allows the use of conditional methods to chain logic more easily.
    use Conditionable;
    // Enables dump and die (dd) functionality for debugging purposes.
    use Dumpable;

    /**
     * Set the test "now" timestamp for both mutable and immutable Carbon instances.
     *
     * This method overrides the test "now" globally for both the mutable (BaseCarbon) and
     * immutable (BaseCarbonImmutable) instances. Useful for testing or scenarios where
     * the current time needs to be faked.
     *
     * @param mixed $testNow The test "now" timestamp. Can be null to reset.
     *
     * @return void
     */
    public static function setTestNow(mixed $testNow = null): void
    {
        // Set the test now for mutable Carbon
        BaseCarbon::setTestNow($testNow);

        // Set the test now for immutable Carbon
        BaseCarbonImmutable::setTestNow($testNow);
    }

    /**
     * Create a Carbon instance from a given UUID or ULID.
     *
     * This method allows for creating a Carbon instance by parsing the creation timestamp from
     * an ordered UUID (RFC 4122) or ULID (Universally Unique Lexicographically Sortable Identifier).
     *
     * If a string is provided, it determines whether the string is a ULID or UUID, and converts
     * it into the appropriate object before retrieving the associated timestamp.
     *
     * @param Uuid|Ulid|string $id The identifier to create the Carbon instance from. It can be a UUID, ULID, or string.
     *
     * @return static Returns the Carbon instance with the corresponding date and time.
     */
    public static function createFromId(Uuid|Ulid|string $id): static
    {
        // If the ID is a string, convert it into either a ULID or UUID
        if (Validator::isString($id)) {
            $id = Ulid::isValid($id) ? Ulid::fromString($id) : Uuid::fromString($id);
        }

        // Create a Carbon instance from the DateTime obtained from the ULID/UUID
        return static::createFromInterface($id->getDateTime());
    }
}
