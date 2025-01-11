# Serializer Module Documentation

Overview

The Serializer module in Maginium provides robust and flexible solutions for serializing and unserializing data. It includes three key serializers
that handle different serialization requirements, ensuring data integrity and simplifying interactions with closures, JSON, and generic PHP
serialization.

Serializers Overview

1. SerializableClosure • Purpose: Handles the serialization and unserialization of closures using Laravel’s SerializableClosure library. • Key
   Features: • Serializes closures into a storable and transferable format. • Unserializes closures back into executable forms. • Utilizes
   SerializableClosureFactory for creating closure instances. • Methods: • make(Closure $closure): SerializableClosure • serialize(Closure $closure):
   string • unserialize(string $string): Closure • Usage Example:

$closure = function () { return 'Hello, Serializer!'; };

$serializer = new SerializableClosure(new SerializableClosureFactory());
$serialized = $serializer->serialize($closure);
$unserialized = $serializer->unserialize($serialized); echo $unserialized(); // Outputs: Hello, Serializer!

2. Json Serializer • Purpose: Provides JSON encoding and decoding functionalities, extending Magento’s base JSON serializer. • Key Features: •
   Serializes PHP data types into JSON strings. • Decodes JSON strings back into their original PHP data formats. • Validates JSON strings for
   correctness. • Methods: • encode(mixed $data): string|false • decode(string $string): mixed • isValid(string $json): bool • Usage Example:

$jsonSerializer = new Json();

$data = ['name' => 'Maginium', 'module' => 'Serializer'];
$jsonString = $jsonSerializer->encode($data);

if ($jsonSerializer->isValid($jsonString)) { $decoded = $jsonSerializer->decode($jsonString); print_r($decoded); }

3. Generic Serializer • Purpose: Handles serialization and unserialization of general PHP data types, extending Magento’s base serializer. • Key
   Features: • Converts PHP data into a serialized string format. • Restores serialized strings back to their original PHP data types. • Provides
   enhanced exception handling. • Methods: • serialize(mixed $data): string|false • unserialize(string $string): mixed • Usage Example:

$genericSerializer = new Serializer();

$data = ['key' => 'value', 'list' => [1, 2, 3]];
$serialized = $genericSerializer->serialize($data);
$unserialized = $genericSerializer->unserialize($serialized);

print_r($unserialized);

Benefits

1.  Flexibility: Supports multiple formats and use cases, from closures to JSON and general data types.
2.  Ease of Use: Provides simple and intuitive APIs for serialization tasks.
3.  Error Handling: Ensures robust error detection with meaningful exception messages.
4.  Compatibility: Leverages well-known libraries like Laravel’s SerializableClosure and Magento’s base serializers.

Installation

1.  Add the module to your project using composer:

composer require maginium/serializer

2.  Register the module in your application bootstrap file if necessary.

Configuration

No additional configuration is required. Simply instantiate the serializers with their required dependencies.

Future Enhancements • Add support for XML serialization. • Provide additional format-specific validation options.

For more information or to report issues, please visit our GitHub Repository.
