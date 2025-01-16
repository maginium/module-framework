<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Maginium\Framework\Mail\Interfaces\MailerInterface;

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
        return $this->getData(MailerInterface::STORE_ID);
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
        $this->setData(MailerInterface::TEMPLATE_ID, $templateId);

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
}
