# ContainerManager

ContainerManager is a utility class within the Maginium framework, designed for efficient dependency resolution and object management. It
leverages Magento’s Object Manager to handle dependency injection, singleton retrieval, and module resolution.

Features • Dependency Management: Provides an interface for resolving dependencies and managing object instances. • Module Awareness: Verifies the
presence and activation status of Magento modules. • Dynamic Object Creation: Supports runtime creation of class instances with constructor arguments.
• Interface Compliance: Implements ContainerInterface for standardized container operations. • Singleton Support: Retrieves and manages singleton
instances of classes.

Installation

1.  Ensure that your project includes Magento Framework as a dependency.
2.  Add the ContainerManager class to your project’s Maginium\Framework\Container namespace.
3.  Include necessary dependencies like InvalidArgumentException and Reflection.

Usage

Basic Initialization

use Maginium\Framework\Container\ContainerManager;

$container = new ContainerManager();

Methods Overview

get(string $className): mixed

Retrieve a singleton instance of a specified class.

$instance = $container->get(\Magento\Catalog\Model\Product::class);

has(string $className): bool

Check if a class can be resolved.

$exists = $container->has(\Magento\Catalog\Model\Product::class);

resolve(string $className, array $arguments = []): ?object

Resolve and return an instance of a specified class.

$product = $container->resolve(\Magento\Catalog\Model\Product::class, ['id' => 123]);

make(string $className, ...$arguments): mixed

Create a new instance of a specified class.

$product = $container->make(\Magento\Catalog\Model\Product::class, ['id' => 123]);

isEnabled(string $moduleName): bool

Check if a Magento module is installed and enabled.

$isEnabled = $container->isEnabled('Magento_Catalog');

getBindings(): array

Retrieve all class bindings in the container.

$bindings = $container->getBindings();

extractModuleName(string $className): string

Extract the module name from the full class name.

$moduleName = $container->extractModuleName(\Magento\Catalog\Model\Product::class);

Exception Handling

InvalidArgumentException

This exception is thrown in the following cases: • When required parameters like $className or $moduleName are null or empty. • When attempting to
resolve non-existing classes or modules.

Example:

try { $container->get(''); } catch (\Maginium\Foundation\Exceptions\InvalidArgumentException $e) { echo $e->getMessage(); }

Class Diagram

ContainerManager ├── get(string
$className): mixed
 ├── has(string $className): bool
 ├── resolve(string $className, array $arguments = []): ?object
 ├── make(string $className, ...$arguments):
mixed ├── isEnabled(string $moduleName): bool ├── getBindings(): array └── extractModuleName(string $className): string

Dependencies • Magento Framework • ObjectManager • ModuleManager • ConfigInterface • ObjectManagerInterface • Maginium Components •
Maginium\Foundation\Exceptions\InvalidArgumentException • Maginium\Framework\Support\Php • Maginium\Framework\Support\Reflection

License

This module is licensed under the MIT License. See the LICENSE file for details.

Contributing

We welcome contributions to improve this module. Please submit a pull request or report issues in the repository.
