# Figlet Module

The Figlet Module is a PHP library for generating text-based ASCII art using FIGlet fonts. This module provides tools for managing, rendering, and
customizing text with a variety of FIGlet font styles.

Features • Render ASCII Art: Convert plain text into ASCII art using FIGlet fonts. • Support for Custom Fonts: Easily load and use custom .flf font
files. • Flexible Configuration: Customize the output, including alignment, padding, and character spacing. • Wide Font Compatibility: Includes
support for popular FIGlet fonts like Standard, Slant, and more. • Easy Integration: Use with any PHP application or framework.

Installation

To install the Figlet Module, use Composer:

composer require pixicommerce/figlet

Usage

Basic Example

use Maginium\Foundation\Figlet\Figlet;

// Initialize the Figlet renderer $figlet = new Figlet();

// Render a simple text echo $figlet->render("Hello, World!");

Using Custom Fonts

use Maginium\Foundation\Figlet\Figlet;

// Initialize the Figlet renderer with a custom font $figlet = new Figlet();
$figlet->setFont('/path/to/custom/font.flf');

// Render text with the custom font echo $figlet->render("Custom Font!");

Configuration Options

The Figlet Module provides configuration options to customize the output:

Option Description Default alignment Align the text (left, center, right) left characterSpacing Spacing between characters 1 padding Padding around
the text 0

Example:

$figlet->setAlignment('center');
$figlet->setCharacterSpacing(2); $figlet->setPadding(1);

Fonts

The Figlet Module supports .flf font files. Popular fonts include: • Standard • Slant • Big • Mini

To add custom fonts, place the .flf file in your project and specify its path using the setFont() method.

Testing

Run the following command to execute tests for the Figlet Module:

vendor/bin/phpunit --testsuite FigletTest

Contributing

Contributions are welcome! If you’d like to add features, fix bugs, or improve the documentation, please submit a pull request.

Steps to Contribute

1.  Fork the repository.
2.  Create a new branch for your changes.
3.  Submit a pull request describing the changes.

License

This project is licensed under the MIT License. See the LICENSE file for details.
