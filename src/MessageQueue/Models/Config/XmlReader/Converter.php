<?php

declare(strict_types=1);

namespace Maginium\Framework\MessageQueue\Models\Config\XmlReader;

use DOMDocument;
use DOMNode;
use Magento\Framework\Communication\Config\ConfigParser;
use Magento\Framework\Communication\Config\Reader\XmlReader\Converter as BaseConverter;
use Magento\Framework\Communication\Config\Reader\XmlReader\Validator;
use Magento\Framework\Communication\Config\ReflectionGenerator;
use Magento\Framework\Communication\ConfigInterface as Config;
use Magento\Framework\Stdlib\BooleanUtils;

/**
 * Converts Communication config from \DOMDocument to an array.
 * This class processes XML configuration files, extracting details about topics, schemas, and handlers.
 */
class Converter extends BaseConverter
{
    /**
     * @var ReflectionGenerator Responsible for generating reflection-based configurations.
     */
    private ReflectionGenerator $reflectionGenerator;

    /**
     * @var BooleanUtils Utility class for handling boolean values.
     */
    private BooleanUtils $booleanUtils;

    /**
     * @var Validator Validates XML schemas and configurations.
     */
    private Validator $xmlValidator;

    /**
     * Initialize dependencies for the Converter class.
     *
     * @param Validator $xmlValidator Validates the XML nodes and their structure.
     * @param BooleanUtils $booleanUtils Handles conversion of boolean-like values.
     * @param ReflectionGenerator $reflectionGenerator Generates topic configurations using reflection.
     */
    public function __construct(
        Validator $xmlValidator,
        BooleanUtils $booleanUtils,
        ReflectionGenerator $reflectionGenerator,
    ) {
        // Call parent constructor to initialize dependencies in the base class.
        parent::__construct($reflectionGenerator, $booleanUtils, $xmlValidator);

        // Assign dependencies to class properties.
        $this->booleanUtils = $booleanUtils;
        $this->xmlValidator = $xmlValidator;
        $this->reflectionGenerator = $reflectionGenerator;
    }

    /**
     * Extract topics configuration from the provided DOMDocument.
     *
     * @param DOMDocument $config The XML configuration document.
     *
     * @return array An array containing parsed topics configuration.
     */
    protected function extractTopics($config): array
    {
        // Initialize an empty array to store topics.
        $topics = [];

        // Iterate over each <topic> node in the XML document.
        /** @var DOMNode $topicNode */
        foreach ($config->getElementsByTagName('topic') as $topicNode) {
            // Extract the topic name.
            $topicName = $this->getTopicName($topicNode);

            // Retrieve service method, if any.
            $serviceMethod = $this->getServiceMethodBySchema($topicNode);

            // Generate topic data using the topic node, name, and service method.
            $topicData = $this->generateTopicData($topicNode, $topicName, $serviceMethod);

            // Add the topic data to the result if it was successfully generated.
            if ($topicData !== null) {
                $topics[$topicName] = $topicData;
            }
        }

        // Return the array of parsed topics.
        return $topics;
    }

    /**
     * Get the name of the topic from the DOMNode.
     *
     * @param DOMNode $topicNode The XML node representing the topic.
     *
     * @return string The name of the topic extracted from the node's attributes.
     */
    private function getTopicName(DOMNode $topicNode): string
    {
        return $topicNode->attributes->getNamedItem('name')->nodeValue;
    }

    /**
     * Generate the topic data based on the service method and schemas.
     *
     * @param DOMNode $topicNode The topic node from the XML document.
     * @param string $topicName The extracted name of the topic.
     * @param array|null $serviceMethod The service method data if applicable.
     *
     * @return array|null The generated topic data or null if generation is not possible.
     */
    private function generateTopicData(DOMNode $topicNode, string $topicName, ?array $serviceMethod): ?array
    {
        // Extract request and response schemas from the topic node.
        $requestSchema = $this->extractTopicRequestSchema($topicNode);
        $responseSchema = $this->extractTopicResponseSchema($topicNode);

        // Extract any response handlers and determine if the topic is synchronous.
        $handlers = $this->extractTopicResponseHandlers($topicNode);
        $isSynchronous = $this->extractTopicIsSynchronous($topicNode);

        // Validate the schemas and topic configuration.
        $this->validateSchemas(
            $topicName,
            $serviceMethod,
            $requestSchema,
            $responseSchema,
            $handlers,
        );

        // Generate topic data based on the service method, if available.
        if ($serviceMethod) {
            return $this->generateServiceMethodTopicData($topicName, $serviceMethod, $handlers, $isSynchronous);
        }

        // Fallback to manual topic data generation if no service method is provided.
        return $this->generateManualTopicData($topicName, $requestSchema, $responseSchema, $handlers, $isSynchronous);
    }

    /**
     * Validate schemas for the topic.
     *
     * @param string $topicName The name of the topic being validated.
     * @param array|null $serviceMethod The service method metadata, if applicable.
     * @param string|null $requestSchema The request schema definition.
     * @param string|null $responseSchema The response schema definition.
     * @param array|null $handlers The handlers associated with the topic.
     *
     * @return void
     */
    private function validateSchemas(
        string $topicName,
        ?array $serviceMethod,
        ?string $requestSchema,
        ?string $responseSchema,
        ?array $handlers,
    ): void {
        // Extract request and response metadata from the service method, if provided.
        $requestResponseSchema = $serviceMethod
            ? $this->reflectionGenerator->extractMethodMetadata(
                $serviceMethod[ConfigParser::TYPE_NAME],
                $serviceMethod[ConfigParser::METHOD_NAME],
            )
            : null;

        // Validate response-request schema relationships.
        $this->xmlValidator->validateResponseRequest(
            $requestResponseSchema,
            $requestSchema,
            $topicName,
            $responseSchema,
            $handlers,
        );

        // Validate topic declarations against the schema.
        $this->xmlValidator->validateDeclarationOfTopic(
            $requestResponseSchema,
            $topicName,
            $requestSchema,
            $responseSchema,
        );
    }

    /**
     * Generate topic data for a service method-based configuration.
     *
     * @param string $topicName The name of the topic.
     * @param array $serviceMethod The service method metadata.
     * @param array|null $handlers The handlers for the topic.
     * @param bool $isSynchronous Whether the topic is synchronous.
     *
     * @return array The topic configuration array.
     */
    private function generateServiceMethodTopicData(
        string $topicName,
        array $serviceMethod,
        ?array $handlers,
        bool $isSynchronous,
    ): array {
        return $this->reflectionGenerator->generateTopicConfigForServiceMethod(
            $topicName,
            $serviceMethod[ConfigParser::TYPE_NAME],
            $serviceMethod[ConfigParser::METHOD_NAME],
            $handlers,
            $isSynchronous,
        );
    }

    /**
     * Generate topic data manually if no service method is defined.
     *
     * @param string $topicName The name of the topic.
     * @param string|null $requestSchema The request schema, if provided.
     * @param string|null $responseSchema The response schema, if provided.
     * @param array|null $handlers The handlers for the topic.
     * @param bool $isSynchronous Whether the topic is synchronous.
     *
     * @return array|null The topic configuration array or null if insufficient data.
     */
    private function generateManualTopicData(
        string $topicName,
        ?string $requestSchema,
        ?string $responseSchema,
        ?array $handlers,
        bool $isSynchronous,
    ): ?array {
        // Define a topic configuration if both request and response schemas are present.
        if ($requestSchema && $responseSchema) {
            return [
                Config::TOPIC_NAME => $topicName,
                Config::TOPIC_IS_SYNCHRONOUS => $isSynchronous,
                Config::TOPIC_REQUEST => $requestSchema,
                Config::TOPIC_REQUEST_TYPE => Config::TOPIC_REQUEST_TYPE_CLASS,
                Config::TOPIC_RESPONSE => $responseSchema,
                Config::TOPIC_HANDLERS => $handlers,
            ];
        }

        // Define a partial topic configuration if only the request schema is present.
        if ($requestSchema) {
            return [
                Config::TOPIC_NAME => $topicName,
                Config::TOPIC_IS_SYNCHRONOUS => false,
                Config::TOPIC_REQUEST => $requestSchema,
                Config::TOPIC_REQUEST_TYPE => Config::TOPIC_REQUEST_TYPE_CLASS,
                Config::TOPIC_RESPONSE => null,
                Config::TOPIC_HANDLERS => $handlers,
            ];
        }

        // Return null if no valid configuration can be created.
        return null;
    }

    /**
     * Extract whether the topic is synchronous.
     *
     * @param DOMNode $topicNode The XML node representing the topic.
     *
     * @return bool True if the topic is synchronous, false otherwise.
     */
    private function extractTopicIsSynchronous(DOMNode $topicNode): bool
    {
        // Get the "is_synchronous" attribute from the topic node.
        $attributeName = Config::TOPIC_IS_SYNCHRONOUS;
        $topicAttributes = $topicNode->attributes;

        // Convert the attribute value to boolean, defaulting to true if not present.
        return $topicAttributes->getNamedItem($attributeName)
            ? $this->booleanUtils->toBoolean($topicAttributes->getNamedItem($attributeName)->nodeValue)
            : true;
    }
}
