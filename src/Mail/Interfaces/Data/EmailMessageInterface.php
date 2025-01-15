<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces\Data;

use Magento\Framework\Mail\EmailMessageInterface as BaseEmailMessageInterface;

/**
 * Interface EmailMessageInterface.
 *
 * Extends the Magento EmailMessageInterface to include additional functionality for setting headers.
 */
interface EmailMessageInterface extends BaseEmailMessageInterface
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
    public function addHeader(string $key, string $value): self;
}
