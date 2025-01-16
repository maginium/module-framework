<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Mail\Helpers\Data as DataHelper;
use Maginium\Framework\Mail\Interfaces\Data\AddressInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Validator;

/**
 * Trait HasRecipient.
 *
 * This trait provides methods for managing the recipient information within an email envelope.
 * It allows for setting and retrieving the recipient's email address and name.
 * The recipient's information is typically used to populate the "To" field in an email.
 */
trait HasRecipient
{
    /**
     * The recipients receiving a copy of the message.
     *
     * @var AddressInterface[]|null
     */
    public $cc = [];

    /**
     * The recipients receiving a blind copy of the message.
     *
     * @var AddressInterface[]|null
     */
    public $bcc = [];

    /**
     * Set the recipient email address.
     *
     * This method sets the recipient's email address and optionally their name for the "to" field.
     * It creates an Address object using the provided email and name.
     *
     * @param string $email Recipient's email address.
     * @param string $name Recipient's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function to(string $email, string $name = ''): MailerInterface
    {
        // Create an Address object for the recipient.
        $toAddress = $this->createAddressObject($email, $name);

        // Set the recipient email address in the internal data store.
        return $this->setTo($toAddress);
    }

    /**
     * Retrieve the recipient details, including email and name.
     *
     * @return AddressInterface|null
     */
    public function getTo(): ?AddressInterface
    {
        return $this->getData(MailerInterface::TO);
    }

    /**
     * Set the recipient details, including email and name.
     *
     * @param AddressInterface $to Array of recipient address objects.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setTo(AddressInterface $to): MailerInterface
    {
        // Create an Address object for the recipient.
        $toAddress = $this->createAddressObject($to);

        // Set the recipient email address in the internal data store.
        $this->setData(MailerInterface::TO, $toAddress);

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Retrieve the sender details, including email and name.
     *
     * @return AddressInterface|null
     */
    public function getFrom(): ?AddressInterface
    {
        // Create a default from address using the method for getting the default value.
        $defaultFrom = $this->createAddressObject($this->getDefaultFrom());

        return $this->getData(MailerInterface::FROM) ?? $defaultFrom;
    }

    /**
     * Set the sender details, including email and name.
     *
     * @param AddressInterface $from Array of sender address objects.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setFrom(AddressInterface $from): MailerInterface
    {
        // Create an Address object for the sender.
        $fromAddress = $this->createAddressObject($from);

        // Set the sender email address in the internal data store.
        $this->setData(MailerInterface::FROM, $fromAddress);

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Set the sender email address.
     *
     * This method sets the sender's email address and optionally their name for the "from" field.
     * It creates an Address object using the provided email and name.
     *
     * @param string $email Sender's email address.
     * @param string $name Sender's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function from(string $email, string $name = ''): MailerInterface
    {
        // Create an Address object for the sender.
        $fromAddress = $this->createAddressObject($email, $name);

        // Set the sender email address in the internal data store.
        $this->setData(MailerInterface::FROM, $fromAddress);

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Set the reply-to email address.
     *
     * This method sets the reply-to email address, allowing the recipient's replies to be directed to a different address.
     *
     * @param string $email Reply-to email address.
     * @param string $name Optional reply-to name.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function replyTo(string $email, string $name = ''): MailerInterface
    {
        // Create an Address object for the reply-to address.
        $replyToAddress = $this->createAddressObject($email, $name);

        // Set the reply-to address in the internal data store.
        return $this->setReplyTo($replyToAddress);
    }

    /**
     * Set the reply-to email address.
     *
     * This method sets the reply-to email address, which directs the recipient's replies to a different address.
     * If no custom reply-to address is set, the default reply-to address is used.
     *
     * @return AddressInterface Returns the reply-to address instance or default reply-to if none set.
     */
    public function getReplyTo(): AddressInterface
    {
        // Create a default reply-to address using the method for getting the default value.
        $defaultReplyTo = $this->createAddressObject($this->getDefaultReplyTo());

        // Return the custom reply-to address if set, otherwise return the default reply-to address.
        return $this->getData(MailerInterface::REPLY_TO) ?? $defaultReplyTo;
    }

    /**
     * Set the reply-to email address.
     *
     * @param AddressInterface $replyTo The reply-to address object.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setReplyTo(AddressInterface $replyTo): MailerInterface
    {
        // Create an Address object for the reply-to address.
        $replyToAddress = $this->createAddressObject($replyTo);

        // Set the reply-to address in the internal data store.
        $this->setData(MailerInterface::REPLY_TO, $replyToAddress);

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Add a recipient to the CC (carbon copy) list.
     *
     * This method adds a recipient to the CC list, allowing them to receive a copy of the email.
     * It creates an Address object using the provided email and name, then adds it to the CC data.
     *
     * @param string $email CC recipient's email address.
     * @param string $name CC recipient's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function cc(string $email, string $name = ''): MailerInterface
    {
        return $this->setCc($email, $name);
    }

    /**
     * Get the CC (carbon copy) recipients.
     *
     * This method retrieves the list of recipients in the CC field.
     *
     * @return AddressInterface[]|null Returns the CC address instance, default CC if none set, or null if no CC.
     */
    public function getCc(): ?array
    {
        // Get the current CC data
        $ccData = $this->getData(MailerInterface::CC);

        // If CC data is set and not empty, return it as an array
        if (! Validator::isEmpty($ccData)) {
            return $ccData;
        }

        // If no CC data, check if the default CC is set and create the address object
        $defaultCc = $this->getDefaultCc() ? $this->createAddressObject($this->getDefaultCc()) : null;

        // Return the default CC if available, otherwise return null
        return $defaultCc ? [$defaultCc] : null;
    }

    /**
     * Set the CC (carbon copy) recipients.
     *
     * Handles single or multiple email addresses and ensures proper validation
     * and normalization of each recipient.
     *
     * @param array|string $email Single email address or an array of recipients.
     * @param string $name Name of the recipient (optional for single email).
     *
     * @throws InvalidArgumentException If a recipient array is missing the required "email" key.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setCc(array|string $email, string $name = ''): MailerInterface
    {
        // Process multiple recipients if an array is provided
        if (Validator::isArray($email)) {
            foreach ($email as $recipient) {
                $normalizedRecipient = $this->normalizeRecipient($recipient);
                $this->addRecipient(
                    MailerInterface::CC,
                    $normalizedRecipient[AddressInterface::EMAIL],
                    $normalizedRecipient[AddressInterface::NAME] ?? '',
                );
            }
        } else {
            // Handle a single email address
            $this->addRecipient(MailerInterface::CC, $email, $name);
        }

        return $this;
    }

    /**
     * Add a recipient to the BCC (blind carbon copy) list.
     *
     * This method adds a recipient to the BCC list, allowing them to receive a copy of the email without other recipients knowing.
     * It creates an Address object using the provided email and name, then adds it to the BCC data.
     *
     * @param string $email BCC recipient's email address.
     * @param string $name BCC recipient's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function bcc(string $email, string $name = ''): MailerInterface
    {
        return $this->setBcc($email, $name);
    }

    /**
     * Get the BCC (blind carbon copy) recipients.
     *
     * This method retrieves the list of recipients in the BCC field.
     *
     * @return AddressInterface[]|null Returns the BCC address instance, default BCC if none set, or null if no BCC.
     */
    public function getBcc(): ?array
    {
        // Get the current BCC data
        $bccData = $this->getData(MailerInterface::BCC);

        // If BCC data is set and not empty, return it as an array
        if (! Validator::isEmpty($bccData)) {
            return $bccData;
        }

        // If no BCC data, check if the default BCC is set and create the address object
        $defaultBcc = $this->getDefaultBcc() ? $this->createAddressObject($this->getDefaultBcc()) : null;

        // Return the default BCC if available, otherwise return null
        return $defaultBcc ? [$defaultBcc] : null;
    }

    /**
     * Set the BCC (blind carbon copy) recipients.
     *
     * Handles single or multiple email addresses and ensures proper validation
     * and normalization of each recipient.
     *
     * @param array|string $email Single email address or an array of recipients.
     * @param string $name Name of the recipient (optional for single email).
     *
     * @throws InvalidArgumentException If a recipient array is missing the required "email" key.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setBcc(array|string $email, string $name = ''): MailerInterface
    {
        // Process multiple recipients if an array is provided
        if (Validator::isArray($email)) {
            foreach ($email as $recipient) {
                $normalizedRecipient = $this->normalizeRecipient($recipient);
                $this->addRecipient(
                    MailerInterface::BCC,
                    $normalizedRecipient[AddressInterface::EMAIL],
                    $normalizedRecipient[AddressInterface::NAME] ?? '',
                );
            }
        } else {
            // Handle a single email address
            $this->addRecipient(MailerInterface::BCC, $email, $name);
        }

        return $this;
    }

    /**
     * Determine if the message has the given address as a "cc" recipient.
     *
     * This method checks if the provided email address (and optionally the name)
     * exists in the "cc" recipients of the message.
     *
     * @param string $address The email address to check.
     * @param string|null $name The name associated with the email address (optional).
     *
     * @return bool Returns true if the address is found in the "cc" recipients, otherwise false.
     */
    public function hasCc(string $address, ?string $name = null): bool
    {
        // Check the "cc" recipients for the specified address and name.
        return $this->hasRecipient($this->getData(MailerInterface::CC), $address, $name);
    }

    /**
     * Determine if the message has the given address as a "bcc" recipient.
     *
     * This method checks if the provided email address (and optionally the name)
     * exists in the "bcc" recipients of the message.
     *
     * @param string $address The email address to check.
     * @param string|null $name The name associated with the email address (optional).
     *
     * @return bool Returns true if the address is found in the "bcc" recipients, otherwise false.
     */
    public function hasBcc(string $address, ?string $name = null): bool
    {
        // Check the "bcc" recipients for the specified address and name.
        return $this->hasRecipient($this->getData(MailerInterface::BCC), $address, $name);
    }

    /**
     * Normalize a recipient into an array with "email" and "name" keys.
     *
     * @param array|Arrayable|DataObject|string $recipient The recipient data.
     *
     * @throws InvalidArgumentException If the recipient data is invalid.
     *
     * @return array An associative array with "email" and optional "name" keys.
     */
    private function normalizeRecipient(array|Arrayable|DataObject|string $recipient): array
    {
        // Handle DataObject and Arrayable instances
        if ($recipient instanceof DataObject) {
            $recipient = $recipient->getData();
        } elseif ($recipient instanceof Arrayable) {
            $recipient = $recipient->toArray();
        }

        // Ensure the recipient is a valid array with an "email" key
        if (! Validator::isArray($recipient) || ! isset($recipient[AddressInterface::EMAIL])) {
            throw InvalidArgumentException::make('Each recipient must be an array with at least an "email" key.');
        }

        return $recipient;
    }

    /**
     * Get the default CC (Carbon Copy) addresses from Magento configuration.
     *
     * If no CC addresses are configured, an empty array will be returned.
     *
     * @return AddressInterface[]|null The default CC addresses or null if no configuration exists.
     */
    private function getDefaultCC(): ?array
    {
        // Retrieve the configured CC emails from the Magento system using the specific config path
        return Config::getArray(DataHelper::CONFIG_PATH_DEFAULT_CC_EMAILS) ?? [];
    }

    /**
     * Get the default BCC (Blind Carbon Copy) addresses from Magento configuration.
     *
     * If no BCC addresses are configured, an empty array will be returned.
     *
     * @return AddressInterface[]|null The default BCC addresses or null if no configuration exists.
     */
    private function getDefaultBcc(): ?array
    {
        // Retrieve the configured BCC emails from the Magento system using the specific config path
        return Config::getArray(DataHelper::CONFIG_PATH_DEFAULT_BCC_EMAILS) ?? [];
    }

    /**
     * Get the default Reply-To address from Magento configuration.
     *
     * If no configuration is found, an array with null values for email and name will be returned.
     *
     * @return array|null The default Reply-To address as an associative array with AddressInterface::EMAIL and AddressInterface::NAME keys.
     */
    private function getDefaultReplyTo(): ?array
    {
        // Retrieve the configured name and email for the Reply-To address from the Magento system
        $name = Config::getString(DataHelper::CONFIG_PATH_DEFAULT_REPLY_TO_NAME);
        $email = Config::getString(DataHelper::CONFIG_PATH_DEFAULT_REPLY_TO_EMAIL);

        // Return the Reply-To address as an associative array containing AddressInterface::EMAIL and AddressInterface::NAME
        return [AddressInterface::EMAIL => $email, AddressInterface::NAME => $name];
    }

    /**
     * Get the default From address from Magento configuration.
     *
     * If no configuration is found, an array with null values for email and name will be returned.
     *
     * @return array|null The default From address as an associative array with AddressInterface::EMAIL and AddressInterface::NAME keys.
     */
    private function getDefaultFrom(): ?array
    {
        // Retrieve the configured name and email for the From address from the Magento system
        $name = Config::getString(DataHelper::XML_PATH_EMAIL_SENDER_NAME);
        $email = Config::getString(DataHelper::XML_PATH_EMAIL_SENDER_EMAIL);

        // Return the From address as an associative array containing AddressInterface::EMAIL and AddressInterface::NAME
        return [AddressInterface::EMAIL => $email, AddressInterface::NAME => $name];
    }

    /**
     * Determine if the message has the given recipient.
     *
     * Checks if the specified email address (and optionally the name) exists in
     * the provided list of recipients.
     *
     * @param array<int, AddressInterface> $recipients The list of recipients.
     * @param string $address The email address to check.
     * @param string|null $name The name associated with the email address (optional).
     *
     * @return bool Returns true if the recipient is found, otherwise false.
     */
    private function hasRecipient(array $recipients, string $address, ?string $name = null): bool
    {
        // Use a collection to check if the recipient exists in the list.
        return collect($recipients)->contains(function(AddressInterface $recipient) use ($address, $name) {
            // If no name is provided, match only by email.
            if ($name === null) {
                return $recipient->getEmail() === $address;
            }

            // Match both email and name.
            return $recipient->getEmail() === $address && $recipient->getName() === $name;
        });
    }

    /**
     * Add a recipient to the specified list (CC or BCC) after checking for duplicates.
     *
     * This method checks if the email already exists in the list before adding it.
     *
     * @param string $list The list to add the recipient to (CC or BCC).
     * @param string $email The recipient's email address.
     * @param string $name The recipient's name (optional).
     *
     * @return void
     */
    private function addRecipient(string $list, string $email, string $name = ''): void
    {
        // Normalize the new address
        $newAddress = $this->normalizeAddresses($email, $name);

        // Get the current list (CC or BCC)
        $currentList = $this->{$list};

        // Check if the email already exists in the list using hasRecipient
        if ($this->hasRecipient($currentList, $email, $name)) {
            // If the email is already in the list, return without adding it again
            return;
        }

        // Add the new address to the list
        $this->{$list} = Arr::merge($currentList, $newAddress);

        // Set the updated data for the list (CC or BCC)
        $this->addData([$list => $newAddress]);
    }

    /**
     * Normalize the given addresses into AddressInterface instances.
     *
     * Converts the provided address input (string, array, or AddressInterface) into
     * an array of AddressInterface instances.
     *
     * @param AddressInterface|array<int, AddressInterface|string>|string|null $address The address input.
     * @param string|null $name The name associated with the address (optional).
     *
     * @return AddressInterface[] An array of AddressInterface instances.
     */
    private function normalizeAddresses(AddressInterface|array|string|null $address, ?string $name = null): array
    {
        // Return an empty array if the address is null.
        if ($address === null) {
            return [];
        }

        // If the address is already an AddressInterface, return it as a single-element array.
        if ($address instanceof AddressInterface) {
            return [$address];
        }

        // If the address is an array, map each element to an AddressInterface instance.
        if (Validator::isArray($address)) {
            return Arr::map($address, fn($email): AddressInterface => $this->createAddressObject($email, name: $name));
        }

        // If the address is a string, create an AddressInterface instance and return it in an array.
        return [$this->createAddressObject($address, $name)];
    }

    /**
     * Create an AddressInterface instance from various input types.
     *
     * Converts a string, array, or AddressInterface into an AddressInterface instance.
     *
     * @param AddressInterface|array<string, string>|string|null $address The address input.
     * @param string|null $name The name associated with the address (optional).
     *
     * @return AddressInterface The created AddressInterface instance.
     */
    private function createAddressObject(AddressInterface|array|string|null $address, ?string $name = null): AddressInterface
    {
        // If the address is already an AddressInterface, return it.
        if ($address instanceof AddressInterface) {
            return $address;
        }

        // Create a new AddressInterface instance using the factory.
        $addressObject = $this->addressFactory->create();

        // If the address is an array, set its email and name.
        if (Validator::isArray($address)) {
            $addressObject->setEmail($address[AddressInterface::EMAIL] ?? '');
            $addressObject->setName($address[AddressInterface::NAME] ?? ($name ?? ''));
        } else {
            // If the address is a string, set it as the email and use the provided name.
            $addressObject->setEmail((string)$address);
            $addressObject->setName($name ?? '');
        }

        return $addressObject;
    }
}
