<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\User\Api\Data\UserInterface;
use Maginium\Foundation\Abstracts\Middleware\AbstractMiddleware;
use Maginium\Foundation\Enums\UserType;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\Facades\Log;

/**
 * Middleware to handle user session management. It determines if the current user is an admin, a customer, or anonymous.
 * This middleware sets the user in the request and logs relevant user information for debugging and auditing purposes.
 */
class User extends AbstractMiddleware
{
    /**
     * @var AdminSession The admin session to retrieve admin user data.
     */
    private $adminSession;

    /**
     * @var CustomerSession The customer session to retrieve customer data.
     */
    private $customerSession;

    /**
     * User constructor.
     *
     * @param AdminSession $adminSession The admin session service.
     * @param CustomerSession $customerSession The customer session service.
     */
    public function __construct(AdminSession $adminSession, CustomerSession $customerSession)
    {
        $this->adminSession = $adminSession;
        $this->customerSession = $customerSession;

        // Set the logger class name for context purposes.
        // This helps identify which class is performing the logging in the logs.
        Log::setClassName(static::class);
    }

    /**
     * Perform the pre-dispatch logic. This method is executed before handling the request.
     * It determines if the user is an admin, customer, or anonymous based on the session.
     * It also logs the relevant user information in the system log for tracking and debugging.
     *
     * @param RequestInterface $request The incoming HTTP request that needs to be processed.
     */
    protected function before($request): void
    {
        // Initialize the user context as 'anonymous', in case no user session is found.
        $userContext = ['identity' => UserType::GUEST];

        // Check if there is an admin user in the session
        /** @var UserInterface $customer */
        $adminUser = $this->adminSession->getUser();

        /** @var CustomerInterface $customer */
        $customer = $this->customerSession->getCustomer();

        if ($adminUser !== null) {
            // If an admin user is found, set the admin user to the request
            // This makes the admin user accessible throughout the request lifecycle
            $request->setUser($adminUser);

            // Set the user context to 'admin' and include the admin user ID for logging
            $userContext = [
                'id' => $adminUser->getId(), // Admin user ID for tracing purposes
                'identity' => UserType::ADMIN, // Identity type (admin)
            ];
        }
        // If there is no admin user, check if a customer is logged in
        elseif ($customer !== null && $customer->getId() !== null) {
            // If a customer is found, set the customer to the request
            // This makes the customer accessible throughout the request lifecycle
            $request->setUser($customer);

            // Set the user context to 'customer' and include the customer ID for logging
            $userContext = [
                'id' => $customer->getId(), // Customer ID for tracing purposes
                'identity' => UserType::CUSTOMER, // Identity type (customer)
            ];
        } else {
            // If no admin or customer is found, set the user as null (anonymous)
            // This could happen if no user is logged in or the session is invalid
            $request->setUser(null);
        }

        // Log the user information context (admin, customer, or anonymous)
        // This allows us to track who is making the request for auditing and debugging purposes
        Log::withContext(['user-info' => $userContext]);
    }
}
