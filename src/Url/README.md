# URL Manager

The URL Manager module provides a convenient API for managing, generating, and manipulating URLs in your application. It simplifies operations like
building route URLs, managing query parameters, and interacting with backend/frontend URL configurations.

Features • Generate route URLs for frontend and backend. • Add and manage query parameters. • Escape URLs for safe usage. • Validate origins and
redirect URLs. • Works seamlessly with session-based URL parameters.

Installation

To use the URL Manager module in your project:

1.  Install via Composer (if applicable):

composer require pixicommerce/framework-url

2.  Add the service provider (if using a framework that supports service providers) or configure the module as per your application.

Usage

Importing the Facade

The Url facade provides a static interface to interact with the underlying URL Manager:

use Maginium\Framework\Url\Facades\Url;

Available Methods

1. getBaseUrl(array $params = [])

Retrieve the base URL of the application.

$baseUrl = Url::getBaseUrl(['secure' => true]);

2. getCurrentUrl()

Get the current request URL.

$currentUrl = Url::getCurrentUrl();

3. getRouteUrl($routePath = null, $routeParams = null)

Generate a URL for a specific route.

$routeUrl = Url::getRouteUrl('customer/account/login', ['_secure' => true]);

4. addQueryParams(array $data)

Add query parameters to the current URL.

$urlWithParams = Url::addQueryParams(['key' => 'value']);

5. setQueryParam($key, $data)

Set a specific query parameter.

$urlWithQuery = Url::setQueryParam('utm_source', 'newsletter');

6. getUrl($routePath = null, $routeParams = null)

Get a complete URL with route and parameters.

$completeUrl = Url::getUrl('checkout/cart', ['_secure' => true]);

7. escape($value)

Escape a URL value to ensure safety in HTML or JavaScript contexts.

$escapedUrl = Url::escape($unsafeUrl);

8. getRedirectUrl($url)

Generate a redirect-safe URL.

$redirectUrl = Url::getRedirectUrl('<https://example.com/safe-redirect>');

Example

Here’s a practical example of generating a backend URL and appending query parameters:

use Maginium\Framework\Url\Facades\Url;

// Generate a backend URL $backendUrl = Url::getRouteUrl('admin/dashboard');

// Add query parameters $finalUrl = Url::addQueryParams([ 'section' => 'reports', 'type' => 'sales' ]);

echo $finalUrl;

Constants

The module defines constants for commonly used URL keys: • PWA_URL - Represents the Progressive Web App URL. • WEB_URL - Represents the base URL for
the web.

These can be accessed using:

use Maginium\Framework\Url\Facades\Url;

echo Url::PWA_URL; // Outputs: 'pwa_url'

Contributing

1.  Fork the repository.
2.  Create a feature branch: git checkout -b feature-name.
3.  Commit changes: git commit -m "Add feature description".
4.  Push to the branch: git push origin feature-name.
5.  Open a pull request.

License

This module is open-source software licensed under the MIT license.

Support

If you encounter any issues, feel free to open an issue in the repository or contact the support team.
