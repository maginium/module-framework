<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination\Middlewares;

use Maginium\Foundation\Abstracts\Middleware\AbstractMiddleware;
use Maginium\Framework\Pagination\PaginationState;
use Maginium\Framework\Request\Interfaces\RequestInterface;

/**
 * Middleware class for converting the keys of the request data to snake_case.
 *
 * This middleware listens to the incoming HTTP requests and checks if the request content
 * is a valid JSON string. If so, it converts the keys of the decoded data to snake_case
 * using a provided case converter service and sets the converted data back as the request's content.
 */
class SetPaginationState extends AbstractMiddleware
{
    protected PaginationState $paginationState;

    /**
     * Constructor.
     */
    public function __construct(PaginationState $paginationState)
    {
        $this->paginationState = $paginationState;
    }

    /**
     * Perform optional pre-dispatch logic.
     *
     * @param  RequestInterface  $request  The incoming HTTP request.
     */
    protected function before($request): void
    {
        // Call PaginationState::resolveUsing to resolve pagination on each request
        $this->paginationState->resolveUsing($request);
    }
}
