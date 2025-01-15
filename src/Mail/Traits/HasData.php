<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterface;
use Maginium\Framework\Mail\Interfaces\Data\TemplateDataInterface;
use Maginium\Framework\Support\DataObject;

/**
 * Trait HasData.
 *
 * This trait provides methods to manage email templateData within the envelope context.
 * It allows for adding, retrieving, and setting additional templateData for the email.
 * Email templateData can be used for various purposes, such as tracking, custom metadata,
 * or other protocol-related information (e.g., 'Content-Type', 'Subject').
 */
trait HasData
{
    /**
     * Set template data variables for the email.
     *
     * This method allows you to pass dynamic variables to the email template.
     * These variables can be replaced in the template during rendering.
     *
     * @param TemplateDataInterface[] $data Key-value pairs for template variables.
     *
     * @return EnvelopeInterface Returns the current instance for method chaining.
     */
    public function withData(array $data): EnvelopeInterface
    {
        // Return the current instance to allow method chaining.
        return $this->setTemplateData($data);
    }

    /**
     * Retrieve the additional templateData for the email.
     *
     * @return TemplateDataInterface[]|null Returns an array of key-value pairs representing the templateData.
     */
    public function getTemplateData(): ?array
    {
        // Call denormalizeTemplateData to return key-value pairs
        return $this->getData(EnvelopeInterface::TEMPLATE_DATA);
    }

    /**
     * Set the additional templateData for the email.
     *
     * @param TemplateDataInterface[]|null $templateData An array of templateData key-value pairs.
     *
     * @return EnvelopeInterface Returns the current instance for method chaining.
     */
    public function setTemplateData(?array $templateData): EnvelopeInterface
    {
        // Normalize the templateData.
        $templateData = $this->normalizeTemplateData($templateData);

        $this->setData(EnvelopeInterface::TEMPLATE_DATA, $templateData);

        return $this;
    }

    /**
     * Normalize the given templateData into TemplateDataInterface instances.
     *
     * Converts the provided data into an array of TemplateDataInterface objects. Handles
     * Arrayable and DataObject types by converting them into arrays, and ensures that
     * both key-value pair arrays and existing TemplateDataInterface objects are properly processed.
     *
     * @param array|Arrayable|DataObject|null $templateData The templateData as key-value pairs or an object.
     *
     * @return TemplateDataInterface[] An array of TemplateDataInterface instances.
     */
    private function normalizeTemplateData(array|Arrayable|DataObject|null $templateData): array
    {
        // If the templateData is null or empty, return an empty array
        if (! $templateData) {
            return [];
        }

        // Normalize templateData to an array if it's a DataObject or Arrayable
        if ($templateData instanceof DataObject) {
            $templateData = $templateData->getData();
        } elseif ($templateData instanceof Arrayable) {
            $templateData = $templateData->toArray();
        } elseif (! is_iterable($templateData)) {
            throw InvalidArgumentException::make('TemplateData must be an array, Arrayable, or DataObject.');
        }

        // Normalize each entry, converting it into TemplateDataInterface if necessary
        $normalized = [];

        foreach ($templateData as $key => $value) {
            // If the value is already a TemplateDataInterface, keep it as is
            $normalized[] = $value instanceof TemplateDataInterface
                ? $value
                : $this->createTemplateDataObject([
                    TemplateDataInterface::KEY => $key,
                    TemplateDataInterface::VALUE => $value,
                ]);
        }

        return $normalized;
    }

    /**
     * Create a TemplateDataInterface instance from an array or string input.
     *
     * Converts the input into a TemplateDataInterface instance.
     *
     * @param array $templateData The templateData as key-value pairs.
     *
     * @return TemplateDataInterface The created TemplateDataInterface instance.
     */
    private function createTemplateDataObject(array $templateData): TemplateDataInterface
    {
        // Create a new TemplateDataInterface instance using the factory.
        $templateDataObject = $this->templateDataFactory->create();

        // Set the key and value in the TemplateDataInterface object.
        $templateDataObject->setKey($templateData[TemplateDataInterface::KEY] ?? '');
        $templateDataObject->setValue($templateData[TemplateDataInterface::VALUE] ?? '');

        return $templateDataObject;
    }
}
