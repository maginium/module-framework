<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Maginium\Foundation\Enums\Directions;
use Maginium\Foundation\Enums\Locales;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\StoreManager;

/**
 * Trait HasContent.
 *
 * This trait provides methods for handling content-specific attributes within an envelope context.
 * It allows for managing store IDs associated with email content. Store IDs are typically used
 * for associating emails with a specific store configuration or environment.
 */
trait HasContent
{
    /**
     * Set the store ID.
     *
     * This method sets the store ID for the current email configuration.
     *
     * @param int $storeId The store ID.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function store(int $storeId): MailerInterface
    {
        // Set the store ID in the internal data store.
        return $this->setStoreId($storeId);
    }

    /**
     * Retrieve the store ID for the email.
     *
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        return $this->getData(MailerInterface::STORE_ID) ?? (int)StoreManager::getStore()->getId();
    }

    /**
     * Set the store ID for the email.
     *
     * @param int|null $storeId The ID of the store.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setStoreId(?int $storeId): MailerInterface
    {
        $this->setData(MailerInterface::STORE_ID, $storeId);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the subject of the email.
     *
     * This method sets the subject line for the email, specifying the main topic of the message.
     *
     * @param string $subject Subject line of the email.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function subject(string $subject): MailerInterface
    {
        // Set the subject of the email in the internal data store.
        return $this->setSubject($subject);
    }

    /**
     * Retrieve the subject of the email.
     *
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->getData(MailerInterface::SUBJECT);
    }

    /**
     * Set the subject of the email.
     *
     * @param string|null $subject The email subject.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setSubject(?string $subject): MailerInterface
    {
        $this->setData(MailerInterface::SUBJECT, $subject);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the template ID for the email.
     *
     * This method specifies the template to be used for the email, providing a reference for the layout and content.
     *
     * @param string $templateId Template identifier.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function template(string $templateId): MailerInterface
    {
        // Return the current instance to allow method chaining.
        return $this->setTemplateId($templateId);
    }

    /**
     * Retrieve the template ID for the email.
     *
     * @return string|null
     */
    public function getTemplateId(): ?string
    {
        return $this->getData(MailerInterface::TEMPLATE_ID);
    }

    /**
     * Set the template ID for the email.
     *
     * @param string|null $templateId The template ID.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setTemplateId(?string $templateId): MailerInterface
    {
        // Prepare the template ID based on locale or store-specific configurations.
        $templateId = $this->prepareEmailTemplate($templateId, $this->getStoreId());

        // Store the prepared template ID in the data container.
        $this->setData(MailerInterface::TEMPLATE_ID, $templateId);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Determine if the message has the given subject.
     *
     * Compares the current message's subject with the provided subject string.
     *
     * @param string $subject The subject to check.
     *
     * @return bool Returns true if the subject matches, otherwise false.
     */
    public function hasSubject(string $subject): bool
    {
        // Check if the message's subject matches the given subject.
        return $this->getData(MailerInterface::SUBJECT) === $subject;
    }

    /**
     * Prepare the email content for sending.
     *
     * This method prepares the email template, considering special conditions such as Right-To-Left (RTL) direction
     * for specific locales.
     *
     * @param string $templateId The email template identifier.
     * @param int $storeId The store ID for which the email is being sent.
     *
     * @throws InvalidArgumentException If email content is empty.
     *
     * @return string
     */
    private function prepareEmailTemplate(string $templateId, int $storeId): string
    {
        // Append RTL direction to template if needed based on locale or configuration
        if ($this->shouldAppendRtlDirection($templateId, $storeId)) {
            $templateId .= Directions::RTL; // Modify template ID for RTL locales
        }

        return $templateId;
    }

    /**
     * Check if RTL direction should be appended to the template ID.
     *
     * This method determines whether the template should have RTL (Right-to-Left) direction based on
     * the store locale or specific configuration settings.
     *
     * @param string $templateId The template ID.
     * @param int $storeId The store ID.
     *
     * @return bool Whether RTL direction should be appended.
     */
    private function shouldAppendRtlDirection(string &$templateId, int $storeId): bool
    {
        // Get the locale of the store
        $storeLocale = $this->getStoreLocale($storeId);

        // Set the scope of the configuration to the store ID
        Config::setScopeId($storeId);

        // Check if RTL should be appended based on configuration or store locale
        return Config::getBool(MailerInterface::XML_PATH_MAILER_IS_RTL)
            || (isset($storeLocale) && Locales::isRtl($storeLocale)); // Returns true if RTL is required
    }

    /**
     * Get the store locale based on the provided or default store ID.
     *
     * This method determines the locale of a store based on the provided store ID. If no store ID is provided,
     * it will use the default store's ID to fetch the locale.
     *
     * @param int|null $storeId The store ID. If null, the default store ID will be used.
     *
     * @return string|null The locale of the store. Returns null if no store is found.
     */
    private function getStoreLocale(?int $storeId): ?string
    {
        // If no store ID is provided, use the default store's ID and return its locale
        if (! $storeId) {
            /** @var StoreInterface $defaultStore */
            $defaultStore = StoreManager::getStore();
            $storeId = $defaultStore->getId();

            return $defaultStore->getLocale();
        }

        // If a specific store ID is provided, return the locale of that store
        /** @var StoreInterface $specifiedStore */
        $specifiedStore = StoreManager::getStore($storeId);

        return $specifiedStore->getLocale();
    }
}
