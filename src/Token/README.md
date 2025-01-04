# Token Manager Module

Overview

The Token Manager Module provides a comprehensive solution for managing access tokens across different user roles, including customers, admins, and
API keys. It integrates with Magento’s native services to handle token creation, revocation, and management in a secure and scalable manner.

This module is designed for use in the Maginium framework and provides services for managing the lifecycle of authentication tokens for various
user types, ensuring secure access to APIs and resources.

Features • Customer Token Management: • Create and revoke customer access tokens. • Integrates with Magento’s custom user context and token issuer for
seamless customer authentication. • Admin Token Management: • Handle admin token generation and revocation for administrative access to the backend. •
API Key Management: • Generate, retrieve, and manage API keys. • Securely create access tokens associated with API keys.

Components

TokenManager

The main service that encapsulates the management of tokens for all user types: • Customer Tokens • Admin Tokens • API Keys

ApiKeyService

Responsible for managing API keys and generating access tokens for them. It provides the functionality to: • Create new API keys. • Generate and
retrieve access tokens associated with API keys. • Handle OAuth-based access token generation for API keys.

CustomerTokenService

This service is dedicated to managing customer access tokens. It includes functionality to: • Create access tokens for a given customer ID. • Revoke
tokens if needed.

AdminTokenService (Not fully implemented)

Handles the generation and revocation of admin access tokens.

Installation

To install and use the Token Manager module:

1.  Ensure your Magento environment is set up with the necessary dependencies.
2.  Install the module via Composer or manually place it in the app/code directory of your Magento installation.
3.  Run the Magento upgrade and deployment commands:

php bin/magento setup:upgrade php bin/magento setup:di:compile php bin/magento cache:flush

Usage

TokenManager Class

The TokenManager class is the central service for accessing token management functionality. It provides methods to interact with customer, admin, and
API key services.

use Maginium\Framework\Token\Services\TokenManager;

$tokenManager = new TokenManager($apiKeyService, $adminTokenService, $customerTokenService);

// Generate a customer token $customerToken = $tokenManager->customer()->create($customerId);

// Generate an API key $apiKey = $tokenManager->apiKey()->create($apiKeyName, $email, $permissions);

CustomerTokenService

The CustomerTokenService allows you to generate and manage tokens for customer access.

use Maginium\Framework\Token\Services\CustomerTokenService;

// Create a customer token $tokenService = new CustomerTokenService();
$customerToken = $tokenService->create($userId);

ApiKeyService

The ApiKeyService is used to create API keys and associate them with access tokens.

use Maginium\Framework\Token\Services\ApiKeyService;

// Create an API key and generate access token $apiKeyService = new ApiKeyService($oauthService, $baseApiKeyService);
$apiKey =
$apiKeyService->create($apiKeyName, $email, $permissions);

Configuration

You can configure default values such as the API key name and developer email by editing the Config class in the framework.

Error Handling

The module uses custom exceptions to handle errors and provide more meaningful feedback: • AuthenticationException: Thrown when token creation fails
due to authentication issues. • LocalizedException: Thrown for general exceptions, such as invalid configuration or API key generation errors.

You can catch these exceptions and handle them gracefully in your application.

try { $customerToken = $tokenManager->customer()->create($userId); } catch (AuthenticationException $e) { // Handle authentication error } catch
(LocalizedException $e) { // Handle other exceptions }

Security Considerations • Tokens and API keys should be securely stored and transmitted. • Always use HTTPS to ensure tokens are sent over a secure
channel. • Regularly rotate API keys and tokens to minimize the risk of exposure.

License

This module is licensed under the Maginium license. For more information, please refer to the license file included in the repository.

Contributing

If you wish to contribute to the development of this module, feel free to fork the repository and submit pull requests. Please ensure that your code
follows the PSR-2 coding standards and includes appropriate test coverage.
