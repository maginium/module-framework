<?php

declare(strict_types=1);

namespace Maginium\Framework\Firestore\Helpers;

use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Support\Facades\Config;

/**
 * Helper class for managing FCM configurations.
 *
 * This class provides methods to retrieve specific configurations for FCM.
 */
class Data
{
    /**
     * FCM project ID configuration key.
     */
    public const FIREBASE_PROJECT_ID = 'project_id';

    /**
     * FCM private key ID configuration key.
     */
    public const FIREBASE_PRIVATE_KEY_ID = 'private_key_id';

    /**
     * FCM private key configuration key.
     */
    public const FIREBASE_PRIVATE_KEY = 'private_key';

    /**
     * FCM client notification configuration key.
     */
    public const FIREBASE_CLIENT_EMAIL = 'client_email';

    /**
     * FCM client ID configuration key.
     */
    public const FIREBASE_CLIENT_ID = 'client_id';

    /**
     * FCM auth URI configuration key.
     */
    public const FIREBASE_AUTH_URI = 'auth_uri';

    /**
     * FCM token URI configuration key.
     */
    public const FIREBASE_TOKEN_URI = 'token_uri';

    /**
     * FCM auth provider x509 cert URL configuration key.
     */
    public const FIREBASE_AUTH_PROVIDER_X509_CERT_URL = 'auth_provider_x509_cert_url';

    /**
     * FCM client x509 cert URL configuration key.
     */
    public const FIREBASE_CLIENT_X509_CERT_URL = 'client_x509_cert_url';

    /**
     * FCM universe domain configuration key.
     */
    public const FIREBASE_UNIVERSE_DOMAIN = 'universe_domain';

    /**
     * Get the FCM project ID configuration.
     *
     * @return string|null The FCM project ID configuration.
     */
    public static function getProjectId(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_PROJECT_ID');
    }

    /**
     * Get the FCM private key ID configuration.
     *
     * @return string|null The FCM private key ID configuration.
     */
    public static function getPrivateKeyId(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_PRIVATE_KEY_ID');
    }

    /**
     * Get the FCM private key configuration.
     *
     * @return string|null The FCM private key configuration.
     */
    public static function getPrivateKey(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_PRIVATE_KEY');
    }

    /**
     * Get the FCM client notification configuration.
     *
     * @return string|null The FCM client notification configuration.
     */
    public static function getClientEmail(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_CLIENT_EMAIL');
    }

    /**
     * Get the FCM client ID configuration.
     *
     * @return string|null The FCM client ID configuration.
     */
    public static function getClientId(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_CLIENT_ID');
    }

    /**
     * Get the FCM auth URI configuration.
     *
     * @return string|null The FCM auth URI configuration.
     */
    public static function getAuthUri(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_AUTH_URI');
    }

    /**
     * Get the FCM token URI configuration.
     *
     * @return string|null The FCM token URI configuration.
     */
    public static function getTokenUri(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_TOKEN_URI');
    }

    /**
     * Get the FCM auth provider x509 cert URL configuration.
     *
     * @return string|null The FCM auth provider x509 cert URL configuration.
     */
    public static function getAuthProviderX509CertUrl(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_AUTH_PROVIDER_X509_CERT_URL');
    }

    /**
     * Get the FCM client x509 cert URL configuration.
     *
     * @return string|null The FCM client x509 cert URL configuration.
     */
    public static function getClientX509CertUrl(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_CLIENT_X509_CERT_URL');
    }

    /**
     * Get the FCM universe domain configuration.
     *
     * @return string|null The FCM universe domain configuration.
     */
    public static function getUniverseDomain(): ?string
    {
        return Config::driver(ConfigDrivers::ENV)->getString('FIREBASE_UNIVERSE_DOMAIN');
    }

    /**
     * Get all FCM configurations.
     *
     * @return array Associative array of all FCM configurations.
     */
    public static function getConfig(): array
    {
        return [
            'type' => 'service_account',
            self::FIREBASE_PROJECT_ID => self::getProjectId(),
            self::FIREBASE_PRIVATE_KEY_ID => self::getPrivateKeyId(),
            self::FIREBASE_PRIVATE_KEY => self::getPrivateKey(),
            self::FIREBASE_CLIENT_EMAIL => self::getClientEmail(),
            self::FIREBASE_CLIENT_ID => self::getClientId(),
            self::FIREBASE_AUTH_URI => self::getAuthUri(),
            self::FIREBASE_TOKEN_URI => self::getTokenUri(),
            self::FIREBASE_AUTH_PROVIDER_X509_CERT_URL => self::getAuthProviderX509CertUrl(),
            self::FIREBASE_CLIENT_X509_CERT_URL => self::getClientX509CertUrl(),
            self::FIREBASE_UNIVERSE_DOMAIN => self::getUniverseDomain(),
        ];
    }
}
