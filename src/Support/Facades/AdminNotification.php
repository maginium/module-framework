<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Illuminate\Contracts\Support\Arrayable;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facade;
use Maginium\Framework\Support\Str;

/**
 * Class AdminNotification.
 *
 * Facade for interacting with the Admin Notification management. This facade provides easy access to admin notification
 * related operations, such as adding notifications for various events.
 *
 * @method static void push(Arrayable|Collection|DataTransferObject|DataObject|array $dto, string $configNamespace) Add an admin notification based on the provided data.
 *
 * @see AdminNotificationHelper
 */
class AdminNotification extends Facade
{
    /**
     * Path to the admin notification title configuration.
     */
    public const CONFIG_NOTIFICATION_TITLE_PATH = 'admin_notification/%1/title';

    /**
     * Path to the admin notification message configuration.
     */
    public const CONFIG_NOTIFICATION_MESSAGE_PATH = 'admin_notification/%1/message';

    /**
     * Adds an admin notification for the contact message.
     *
     * @param mixed $dto Data transfer object or other types containing the contact details.
     * @param string $configNamespace The configuration namespace to retrieve the notification settings.
     */
    public static function push(
        Arrayable|Collection|DataTransferObject|DataObject|array $dto,
        string $configNamespace,
    ): void {
        // Retrieve notification title and message template from config constants
        $title = Config::getString(Str::format(self::CONFIG_NOTIFICATION_TITLE_PATH, $configNamespace));
        $messageTemplate = Config::getString(Str::format(self::CONFIG_NOTIFICATION_MESSAGE_PATH, $configNamespace));

        // Match placeholders like {name}, {comment}, etc. in the message template
        preg_match_all('/\{(\w+)\}/', $messageTemplate, $matches);

        // Prepare the placeholders to be replaced with actual values
        $placeholders = self::getPlaceholders($dto, $matches[1]);

        // Replace placeholders in the template message with actual values
        $message = Str::swap($placeholders, $messageTemplate);

        // Add the notification using the admin notification helper
        self::addAdminNotification($title, $message);
    }

    /**
     * Converts the data to an array and extracts placeholders from the DTO.
     *
     * @param mixed $dto The data transfer object or other types.
     * @param array $placeholdersList List of placeholders to extract.
     *
     * @return array The placeholders array.
     */
    private static function getPlaceholders(
        Arrayable|Collection|DataTransferObject|DataObject|array $dto,
        array $placeholdersList,
    ): array {
        // Convert the data to an array if it's an instance of Arrayable, Collection, DTO, or DataObject
        $data = self::convertToArray($dto);

        // Prepare the placeholders to be replaced with actual values
        $placeholders = [];

        foreach ($placeholdersList as $placeholder) {
            if (array_key_exists($placeholder, $data)) {
                $placeholders["{$placeholder}"] = $data[$placeholder];
            }
        }

        return $placeholders;
    }

    /**
     * Converts the given data to an array based on its type.
     *
     * @param mixed $dto The data transfer object or other types.
     *
     * @return array The data as an array.
     */
    private static function convertToArray(mixed $dto): array
    {
        if ($dto instanceof Arrayable || $dto instanceof Collection) {
            return $dto->toArray();
        }

        if ($dto instanceof DataTransferObject) {
            return $dto->toArray();
        }

        if ($dto instanceof DataObject) {
            return $dto->getData();
        }

        // If it's already an array, return as is
        return is_array($dto) ? $dto : [];
    }

    /**
     * Adds the notification using the admin notification helper.
     *
     * @param string $title The notification title.
     * @param string $message The notification message.
     */
    private static function addAdminNotification(string $title, string $message): void
    {
        // self::$adminNotificationHelper?->addAdminNotification(
        //     $title, // Notification title
        //     $message, // Message with replaced placeholders
        //     self::ADMIN_NOTIFICATION_SEVERITY, // Severity level of the notification
        //     null, // Optional additional parameters
        //     self::ADMIN_NOTIFICATION_TYPE // Notification type
        // );
    }

    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return '';
    }
}
