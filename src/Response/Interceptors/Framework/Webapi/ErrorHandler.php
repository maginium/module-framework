<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Interceptors\Framework\Webapi;

use Magento\Framework\App\State;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Response as BaseResponse;
use Magento\Framework\Webapi\Rest\Response\RendererFactory;
use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Response\Interfaces\Data\ResponseInterface;
use Maginium\Framework\Response\Interfaces\Data\ResponseInterfaceFactory;
use Override;

/**
 * Class ErrorHandler.
 *
 * Handles API response errors by managing exceptions and sending standardized error responses.
 * This class extends the Magento base response class to provide enhanced exception handling.
 */
class ErrorHandler extends BaseResponse
{
    /**
     * @var ResponseInterfaceFactory Factory to create response builder instances.
     */
    protected ResponseInterfaceFactory $responseFactory;

    /**
     * Constructor.
     *
     * @param State $appState Application state for Magento.
     * @param ErrorProcessor $errorProcessor Error processor for rendering exceptions.
     * @param RendererFactory $rendererFactory Factory for response renderers.
     * @param ResponseInterfaceFactory $responseFactory Factory to build response instances.
     */
    public function __construct(
        State $appState,
        ErrorProcessor $errorProcessor,
        RendererFactory $rendererFactory,
        ResponseInterfaceFactory $responseFactory,
    ) {
        parent::__construct($rendererFactory, $errorProcessor, $appState);

        $this->responseFactory = $responseFactory;
    }

    /**
     * Sends the API response, handling exceptions if they occur.
     *
     * @return void
     */
    #[Override]
    public function sendResponse(): void
    {
        try {
            // Check if there are exceptions in the response to render error messages.
            if ($this->isException()) {
                $this->_renderMessages();
            }

            // Proceed with the standard response sending process.
            $this->send();
        } catch (Exception $e) {
            // Determine the HTTP code based on the exception type.
            $httpCode = $this->determineHttpCode($e);

            // Render the exception using the error processor.
            $this->_errorProcessor->renderException($e, $httpCode);

            // Process the error response for client delivery.
            $this->processErrorResponse($e, $httpCode);
        }
    }

    /**
     * Renders exception messages into the response body.
     *
     * @return $this
     */
    // #[Override]
    // protected function _renderMessages(): static
    // {
    //     $statusCode = HttpStatusCode::INTERNAL_SERVER_ERROR;

    //     /** @var Exception $exception */
    //     foreach ($this->getException() as $exception) {
    //         // Mask the exception to avoid exposing sensitive details.
    //         $maskedException = $this->_errorProcessor->maskException($exception);

    //         // Determine the HTTP status code from the masked exception.
    //         $statusCode = $this->determineHttpCode($maskedException);

    //         // Determine the stack trace for the exception.
    //         $traceString = $exception instanceof WebapiException
    //         ? $exception->getStackTrace()
    //         : $exception->getTraceAsString();

    //         // Build a standardized error response.
    //         $responseBuilder = $this->buildResponse(
    //             $maskedException,
    //             $traceString,
    //             $statusCode,
    //         );
    //     }

    //     // Finalize the response with the last processed status code and error body.
    //     $this->finalizeResponse($statusCode, $responseBuilder->toJson());

    //     return $this;
    // }

    /**
     * Determines the appropriate HTTP status code for a given exception.
     *
     * @param Exception $exception The exception to evaluate.
     *
     * @return int The HTTP status code.
     */
    private function determineHttpCode(Exception $exception): int
    {
        // Check if the exception is a WebapiException
        if ($exception instanceof WebapiException) {
            // Attempt to retrieve the HTTP code from the exception
            $httpCode = $exception->getHttpCode();

            // Return 406 if the HTTP code matches "Not Acceptable"
            if ($httpCode === WebapiException::HTTP_NOT_ACCEPTABLE) {
                return WebapiException::HTTP_NOT_ACCEPTABLE;
            }

            // Return the exception code if valid, or fallback to internal error
            return $exception->getCode() ?: WebapiException::HTTP_INTERNAL_ERROR;
        }

        // Fallback to internal server error for non-WebapiException cases
        return WebapiException::HTTP_INTERNAL_ERROR;
    }

    /**
     * Processes the error response and prepares it for client delivery.
     *
     * @param Exception $exception The exception to process.
     * @param int $httpCode The HTTP status code for the response.
     *
     * @return void
     */
    private function processErrorResponse(Exception $exception, int $httpCode): void
    {
        // Determine the stack trace for the exception.
        $traceString = $exception instanceof WebapiException
            ? $exception->getStackTrace()
            : $exception->getTraceAsString();

        // Build the error response object.
        $responseBuilder = $this->buildResponse($exception, $traceString, $httpCode);

        // Finalize the response with the HTTP code and JSON body.
        $this->finalizeResponse($httpCode, $responseBuilder->toJson());
    }

    /**
     * Builds a standardized response object for the given exception.
     *
     * @param WebapiException $exception The exception to include in the response.
     * @param string $traceString The stack trace of the exception.
     * @param int $statusCode The HTTP status code for the response.
     *
     * @return ResponseInterface The response object.
     */
    private function buildResponse(
        WebapiException $exception,
        ?string $traceString,
        int $statusCode,
    ): ResponseInterface {
        /** @var ResponseInterface $responseBuilder */
        $responseBuilder = $this->responseFactory->create();

        // Populate the response builder with exception details.
        $responseBuilder->setException($exception)
            ->setCause($traceString)
            ->setStatusCode($statusCode)
            ->setMessage($exception->getMessage())
            ->setErrors($exception->getErrors());

        return $responseBuilder;
    }

    /**
     * Finalizes the response by setting the status code, MIME type, and response body.
     *
     * @param int $statusCode The HTTP status code to set.
     * @param string $body The response body content.
     *
     * @return void
     */
    private function finalizeResponse(int $statusCode, string $body): void
    {
        // Set the HTTP status code for the response.
        $this->setHttpResponseCode($statusCode);

        // Set the MIME type for the response (e.g., JSON).
        $this->setMimeType($this->_renderer->getMimeType());

        // Set the response body content.
        $this->setBody($body);
    }
}
