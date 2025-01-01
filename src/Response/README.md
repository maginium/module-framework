# Response Module

The Response Module is a part of the Maginium framework, providing an enhanced HTTP response interface for managing HTTP requests and responses
effectively. This module extends functionality for headers, status codes, redirection, and body manipulation.

Features • HTTP Status Management: Set and retrieve HTTP response codes and reason phrases. • Headers Manipulation: Add, update, retrieve, and clear
HTTP headers. • Body Handling: Append, replace, or retrieve HTTP response body content. • Redirection: Seamlessly handle HTTP redirection with custom
URLs and status codes. • Response Building: Supports structured responses for RESTful APIs. • Integration: Simplified access via a facade for ease of
use.

Installation

1.  Add to Your Project: Ensure the Response module is installed via composer:

composer require maginium/framework-response

2.  Register the Module: Add the module to your service provider or configuration file, depending on your framework setup.

Usage

Using the Facade

The module provides a facade Maginium\Framework\Support\Facades\Response for easier interaction.

Example: Setting Headers and Body

use Maginium\Framework\Support\Facades\Response;

// Set headers Response::setHeader('Content-Type', 'application/json');

// Set HTTP status Response::setHttpResponseCode(200);

// Add body content Response::setBody(json_encode(['message' => 'Success']));

// Send the response Response::sendResponse();

Example: Redirection

use Maginium\Framework\Support\Facades\Response;

// Set a redirect URL Response::setRedirect('<https://example.com>', 301);

// Send the response Response::sendResponse();

Key Methods

Here’s a list of the main methods available in the Response facade:

Status Code and Headers • setHttpResponseCode(int $code) • getHttpResponseCode() • setHeader(string $name, string $value, bool $replace = false) •
getHeader(string $name) • clearHeader(string $name)

Response Body • setBody(string $value) • appendBody(string $value) • getBody()

Redirection • setRedirect(string $url, int $code = 302)

Sending Response • sendResponse()

Contributing

Contributions are welcome! To contribute:

1.  Fork the repository.
2.  Create a feature branch.
3.  Submit a pull request with clear details about the enhancement or fix.

License

This module is licensed under the MIT License. See the LICENSE file for more information.

Contact

For questions, issues, or feature requests, reach out to us at <support@maginium.com>.
