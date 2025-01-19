<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Magento\Framework\Mail\EmailMessage as BaseEmailMessage;
use Maginium\Framework\Mail\Interfaces\Data\EmailMessageInterface;

/**
 * Class EmailMessage.
 *
 * This class extends the Magento Framework EmailMessage to add custom headers.
 * It is used to build and manipulate email messages, allowing custom headers to be added to the message.
 */
class EmailMessage extends BaseEmailMessage implements EmailMessageInterface
{
    /**
     * Add a single header to the headers collection.
     *
     * This method allows you to add a custom header to the email message.
     * It will add a new header to the existing ones without replacing them.
     *
     * The header is added to the collection, which will be sent as part of the email.
     *
     * @param string $key The name of the header, for example, "Subject" or "Content-Type".
     * @param string $value The value of the header, for example, the actual subject text or content type.
     *
     * @return self Returns the instance of the EmailMessage to allow method chaining.
     */
    public function addHeader(string $key, string $value): self
    {
        // Get the headers collection from the zendMessage and add a new header with the provided key and value.
        // The addHeaderLine method adds the new header without replacing the existing ones.
        $this->zendMessage->getHeaders()->addHeaderLine($key, $value);

        // Return the current object instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }
}
