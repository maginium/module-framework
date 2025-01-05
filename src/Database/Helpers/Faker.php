<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Helpers;

use Faker\Factory as BaseFaker;
use Faker\Generator;
use Maginium\Framework\Support\Facades\Json;

/**
 * Class Faker.
 *
 * This class provides static methods for generating fake data using the Faker library.
 * It encapsulates the functionality to produce various types of random data
 * that can be useful for testing and seeding databases.
 */
class Faker
{
    /**
     * Instance of the Faker generator.
     *
     * @var Generator
     */
    private static Generator $faker;

    /**
     * Generates a random first name.
     *
     * @return string A random first name.
     */
    public static function firstName(): string
    {
        return static::$faker->firstName;
    }

    /**
     * Generates a random last name.
     *
     * @return string A random last name.
     */
    public static function lastName(): string
    {
        return static::$faker->lastName;
    }

    /**
     * Generates a random nickname.
     *
     * @return string A random nickname.
     */
    public static function nickname(): string
    {
        return static::$faker->userName;
    }

    /**
     * Generate a random company name.
     *
     * @return string A random company name.
     */
    public static function company(): string
    {
        return static::$faker->company;
    }

    /**
     * Generates a random email address.
     *
     * @return string A random email address.
     */
    public static function email(): string
    {
        return static::$faker->unique()->safeEmail;
    }

    /**
     * Generates a random address.
     *
     * @return string A random address.
     */
    public static function address(): string
    {
        return static::$faker->address;
    }

    /**
     * Generates a random phone number.
     *
     * @return string A random phone number.
     */
    public static function phone(): string
    {
        return static::$faker->phoneNumber;
    }

    /**
     * Generates a random UUID.
     *
     * @return string A random UUID.
     */
    public static function uuid(): string
    {
        return static::$faker->uuid;
    }

    /**
     * Generates a random domain name.
     *
     * @return string A random domain name.
     */
    public static function domainName(): string
    {
        return static::$faker->domainName;
    }

    /**
     * Generates a random sentence.
     *
     * @return string A random sentence.
     */
    public static function sentence(): string
    {
        return static::$faker->sentence;
    }

    /**
     * Generates a random word.
     *
     * @return string A random word.
     */
    public static function word(): string
    {
        return static::$faker->word;
    }

    /**
     * Generates a random JSON string with a specified number of key-value pairs.
     *
     * @param int $minKeys Minimum number of key-value pairs to generate.
     * @param int $maxKeys Maximum number of key-value pairs to generate.
     *
     * @return string A JSON-encoded string containing random key-value pairs.
     */
    public static function randomJson(int $minKeys = 10, int $maxKeys = 20): string
    {
        $metadata = [];

        // Randomly select the number of keys
        $numberOfKeys = rand($minKeys, $maxKeys);

        for ($j = 0; $j < $numberOfKeys; $j++) {
            // Generate a random key
            $key = static::word();

            // Generate a random value
            $value = static::sentence();

            // Store the key-value pair
            $metadata[$key] = $value;
        }

        // Return the JSON representation of the metadata
        return Json::encode($metadata);
    }

    /**
     * Generates a random date between specified start and end dates.
     *
     * @param string $start The start date.
     * @param string $end The end date.
     *
     * @return string A random date in 'Y-m-d' format.
     */
    public static function dateBetween(string $start, string $end): string
    {
        return static::$faker->date($format = 'Y-m-d', $max = 'now');
    }

    /**
     * Generates a random text string of a specified length.
     *
     * @param int $length The length of the text string to generate.
     *
     * @return string A random text string.
     */
    public static function text(int $length = 100): string
    {
        return static::$faker->text($length);
    }

    /**
     * Generates a random number between specified minimum and maximum values.
     *
     * @param int $min The minimum value for the random number.
     * @param int $max The maximum value for the random number.
     *
     * @return int A random number between min and max.
     */
    public static function randomNumber(int $min = 0, int $max = 100): int
    {
        return static::$faker->numberBetween($min, $max);
    }

    /**
     * Generates a random boolean value (true or false).
     *
     * @return bool A random boolean value.
     */
    public static function boolean(): bool
    {
        return static::$faker->boolean;
    }

    /**
     * Generate a random digit that is not null.
     *
     * This method initializes the Faker instance if it hasn't been initialized yet
     * and returns a randomly generated digit between 1 and 9 (inclusive).
     *
     * @return int A randomly generated digit from 1 to 9.
     */
    public static function randomDigitNotNull(): int
    {
        return static::$faker->randomDigitNotNull();
    }

    /**
     * Generates a random image URL.
     *
     * @return string A random image URL.
     */
    public static function imageUrl(): string
    {
        // Generate a random image URL (using a random image provider if needed)

        // Size 640x480, category business
        return static::$faker->imageUrl(640, 480, 'business', true);
    }

    /**
     * Generates a random date-time string in ISO 8601 format.
     *
     * @return string A random date-time string in ISO 8601 format.
     */
    public static function dateTime(): string
    {
        // Use preferred format here
        return static::$faker->dateTime()->format('Y-m-d\TH:i:sP');
    }

    /**
     * Initializes the Faker instance if it hasn't been initialized yet.
     *
     * This method ensures that the Faker instance is created only once
     * and can be reused across multiple method calls to improve performance.
     */
    private static function initializeFaker(): void
    {
        if (! isset(static::$faker)) {
            static::$faker = BaseFaker::create();
        }
    }

    /**
     * Dynamically handles method calls to the Redis client.
     *
     * Delegates method calls to the Redis client if the method is not defined in the manager.
     *
     * @param  string $method The name of the method being called.
     * @param  array $parameters The parameters passed to the method.
     *
     * @return mixed The result of the method call on the Redis client.
     */
    public function __call(string $method, array $parameters)
    {
        // Initialize the Faker instance if it hasn't been initialized yet
        static::initializeFaker();

        // Call the method on the Redis client instance
        return static::$faker->{$method}(...$parameters);
    }

    public static function __callStatic(string $method, array $parameters)
    {
        // Initialize the Faker instance if it hasn't been initialized yet
        static::initializeFaker();

        // Call the method on the Redis client instance
        return static::$faker->{$method}(...$parameters);
    }
}
