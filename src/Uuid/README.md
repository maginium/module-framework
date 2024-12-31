# UUID Module

The UUID Module provides a robust and flexible API for generating and managing Universally Unique Identifiers (UUIDs). It supports various UUID
versions (1, 3, 4, and 5) and includes utilities for validation, namespace-based generation, and lexicographically sortable UUIDs.

Features • Generate UUIDs for versions 1, 3, 4, and 5. • Supports namespace-based UUID generation. • Generate lexicographically sortable UUIDs. •
Validate UUID strings for correctness. • Custom exceptions for error handling.

Installation

To use the UUID Module, follow these steps:

1.  Install the package (if applicable):

composer require maginium/framework-uuid

2.  Configure it in your application or register the service provider (if required).

Usage

Importing the Facade

The Uuid facade offers a convenient way to interact with the UUID Manager:

use Maginium\Framework\Uuid\Facades\Uuid;

Available Methods

1. generate(int $version, ?string $namespace = null, ?string $name = null)

Generates a UUID for the specified version. Supports versions 1, 3, 4, and 5.

// Generate a version 4 UUID $uuidV4 = Uuid::generate(4);

// Generate a version 5 UUID using a namespace and name $uuidV5 = Uuid::generate(5, 'namespace-uuid', 'resource-name');

2. orderedUuid()

Generates a lexicographically sortable UUID, ideal for database indexing.

$orderedUuid = Uuid::orderedUuid();

3. uuid1()

Generates a version 1 UUID based on timestamp and node.

$uuidV1 = Uuid::uuid1();

4. uuid3(string $namespace, string $name)

Generates a version 3 UUID using a namespace and name (MD5 hashing).

$uuidV3 = Uuid::uuid3('namespace-uuid', 'resource-name');

5. uuid4()

Generates a random version 4 UUID.

$uuidV4 = Uuid::uuid4();

6. uuid5(string $namespace, string $name)

Generates a version 5 UUID using a namespace and name (SHA-1 hashing).

$uuidV5 = Uuid::uuid5('namespace-uuid', 'resource-name');

7. namespaceUuid(string $namespace, string $name)

Generates a namespace-based UUID, equivalent to uuid5.

$namespaceUuid = Uuid::namespaceUuid('namespace-uuid', 'resource-name');

8. isValid(string $uuid)

Validates whether a given string is a valid UUID.

$isValid = Uuid::isValid('550e8400-e29b-41d4-a716-446655440000');

Examples

Generating and Validating a UUID

use Maginium\Framework\Uuid\Facades\Uuid;

// Generate a UUID version 4 $uuid = Uuid::uuid4(); echo "Generated UUID: $uuid";

// Validate the UUID if (Uuid::isValid($uuid)) { echo "The UUID is valid."; } else { echo "The UUID is invalid."; }

Generating a Namespace-Based UUID

$namespaceUuid = Uuid::namespaceUuid('my-namespace-uuid', 'my-resource-name'); echo "Namespace-based UUID: $namespaceUuid";

Constants

The module defines constants for common use cases: • UUID Field Name:

const UUID = 'uuid';

You can reference this constant for database schema fields or application logic.

Exceptions

All methods throw the Maginium\Foundation\Exceptions\Exception in cases where UUID generation or validation fails. Ensure you wrap calls in
try-catch blocks for error handling.

try { $uuid = Uuid::generate(6); // Invalid version } catch (Exception $e) { echo "Error: " . $e->getMessage(); }

Contributing

1.  Fork the repository.
2.  Create a feature branch: git checkout -b feature-name.
3.  Commit your changes: git commit -m "Add feature description".
4.  Push to the branch: git push origin feature-name.
5.  Open a pull request for review.

License

This module is open-source software licensed under the MIT license.

Support

If you encounter any issues or have questions, feel free to open an issue in the repository or contact the support team.
