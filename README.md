# Maginium Framework Module for Magento 2

The **Maginium Framework** is a utility and integration module for Magento 2, designed to extend the functionality of your Magento-based e-commerce application. This package provides essential tools and services to integrate various frameworks and packages seamlessly into your Magento 2 store.

---

## Installation

To install the Maginium Framework module, use Composer:

```bash
composer require maginium/module-framework
```

Requirements
• PHP: >= 8.2
• Magento 2: Compatible with all Magento 2.x versions

Dependencies:

The Maginium Framework module requires the following third-party libraries:

- beeyev/disposable-email-filter-php: ^1.3
- composer/composer: ^2.0.0
- doctrine/dbal: ^2.13.3|^3.1.4
- fakerphp/faker: ^1.23
- guzzlehttp/guzzle: ^7.9
- illuminate/console: ^10.48
- illuminate/database: ^10.48
- illuminate/pagination: ^10.48
- illuminate/process: ^10.48
- intervention/image: ^3.9
- jenssegers/agent: ^2.6
- kreait/firebase-php: ^7.13
- ksubileau/color-thief-php: \*
- laravel/serializable-closure: ^1.3
- lasserafn/php-string-script-language: ^0.4.0
- league/flysystem: ^2.5
- league/flysystem-aws-s3-v3: ^2.5
- magento/framework:\*
- maginium/module-foundation: \*
- phpnexus/cwh: ^2.0
- phpoffice/phpspreadsheet: ^3.3
- predis/predis: ^2.2
- pusher/pusher-php-server: ^7.2
- run-as-root/magento2-message-queue-retry: ^3.0
- spatie/fork: ^1.2
- symfony/filesystem: ^6.4
- symfony/uid: ^7.1
- vlucas/phpdotenv: ^5.6

Development Requirements (Dev)

The following packages are required for development and testing purposes:

- meyfa/phpunit-assert-gd: ^2.0.0|^3.0.0
- mockery/mockery: ^1.5
- phpunit/phpunit: ^10.3
- tightenco/duster: ^3.0

Features

- Enhanced Integration: Seamlessly integrates with other Maginium modules and Magento 2 core functionalities.
- Extensive Utilities: Provides various utilities to help extend and enhance Magento 2 features.
- Third-Party Framework Support: Supports many popular PHP frameworks and packages such as Laravel, Symfony, and more.
- Optimized for Performance: Designed for speed and scalability, ensuring minimal overhead in production environments.
- Secure and Reliable: Built with security and stability in mind, using modern PHP practices and testing.

Documentation

For detailed documentation, visit the official project page:
Maginium Framework Documentation

Issue Tracking

If you encounter any issues or bugs, please open an issue on our GitHub repository:
GitHub Issues

Contributing

We welcome contributions to the Maginium Framework module. If you’d like to contribute, please follow these steps:

1.  Fork the repository.
2.  Create a new branch for your feature or fix.
3.  Write tests for any new functionality.
4.  Submit a pull request with a clear description of your changes.

Maintainers

- Pixielity - Lead Maintainer

License

This package is open-source and available under the MIT License.

Contact

For support or inquiries, please contact us at:
Email: <pixielity@gmail.com>

Security

For security issues, please refer to our security page or contact us directly at:
<pixielity@gmail.com>

Compatibility

- Magento 2: Compatible with Magento 2.3.x versions.
- PHP: Requires PHP 8.2 or higher.

Installation Troubleshooting

If you encounter issues during installation, try the following:

1.  Clear Composer Cache:

composer clear-cache

2.  Ensure Proper Permissions:
    Make sure your system has the correct permissions to install and modify Magento files.
3.  Composer Auth:
    If you’re encountering authentication issues with private repositories, make sure your Composer configuration includes the correct credentials.

Example Usage

Once installed, you can begin using the framework in your Magento 2 application. For example, integrating third-party APIs or using built-in utilities.

Support

If you need help, please open an issue on the GitHub repository or contact our support team.
