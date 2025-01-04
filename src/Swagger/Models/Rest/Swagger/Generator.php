<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Swagger\Models\Rest\Swagger;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\Framework\Webapi\Authorization;
use Magento\Framework\Webapi\CustomAttribute\ServiceTypeListInterface;
use Magento\Webapi\Model\Cache\Type\Webapi;
use Magento\Webapi\Model\Config\Converter;
use Magento\Webapi\Model\Rest\Swagger\Generator as MagentoGenerator;
use Magento\Webapi\Model\Rest\SwaggerFactory;
use Magento\Webapi\Model\ServiceMetadata;
use Pixicommerce\Framework\Support\Arr;
use Pixicommerce\Framework\Support\Facades\Config;
use Pixicommerce\Framework\Support\Php;
use Pixicommerce\Framework\Support\Str;
use Pixicommerce\Framework\Support\Validator;
use Pixicommerce\Framework\Swagger\Helpers\Data as SwaggerHelper;

/**
 * REST Swagger schema generator.
 */
class Generator extends MagentoGenerator
{
    /**
     * Configuration path for store name.
     */
    private const CONFIG_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * Wrapper node for XML requests.
     */
    private const XML_SCHEMA_PARAMWRAPPER = 'request';

    /**
     * @var SwaggerHelper
     */
    protected $swaggerHelper;

    /**
     * Generator constructor.
     *
     * @param Webapi $cache
     * @param SwaggerHelper $swaggerHelper
     * @param Authorization $authorization
     * @param TypeProcessor $typeProcessor
     * @param SwaggerFactory $swaggerFactory
     * @param ServiceMetadata $serviceMetadata
     * @param ProductMetadataInterface $productMetadata
     * @param ServiceTypeListInterface $serviceTypeList
     */
    public function __construct(
        Webapi $cache,
        TypeProcessor $typeProcessor,
        SwaggerHelper $swaggerHelper,
        Authorization $authorization,
        SwaggerFactory $swaggerFactory,
        ServiceMetadata $serviceMetadata,
        ProductMetadataInterface $productMetadata,
        ServiceTypeListInterface $serviceTypeList,
    ) {
        parent::__construct(
            $cache,
            $typeProcessor,
            $serviceTypeList,
            $serviceMetadata,
            $authorization,
            $swaggerFactory,
            $productMetadata,
        );

        $this->swaggerHelper = $swaggerHelper;
        $this->swaggerFactory = $swaggerFactory;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Get Swagger API description.
     *
     * @return string
     */
    public function getDescription()
    {
        $storeName = Config::getString(self::CONFIG_PATH_STORE_NAME);

        $siteUrl = $storeName = Config::getString(
            self::CONFIG_PATH_STORE_NAME,
        );

        return '
    # ' .
            $storeName .
            ' API Documentation

    🚀 Welcome to the ' .
            $storeName .
            ' API! The ' .
            $storeName .
            ' API empowers you to manage ' .
            $storeName .
            ' effortlessly with its robust endpoints, all fortified by Bearer token authentication.

    🔐 **Authentication**
    Ensure secure access to the API by including a valid Bearer token in the Authorization header of your requests.

    🛣️ **API Endpoints**
    Navigate the ' .
            $storeName .
            ' universe with these key endpoints:
    - **GET /common-service:** Retrieve a curated list of ' .
            $storeName .
            ".
    - **GET /common-service/{id}:** Uncover intricate details of a specific API.
    - **ENTITY /common-service:** Forge a new API.
    - **PUT /common-service/{id}:** Sculpt an existing API to perfection.
    - **DELETE /common-service/{id}:** Eliminate an API gracefully.

    👤 **User Credentials**
    By default, an admin user account is configured:
    - **Username:** admin
    - **Password:** admin

    🛡️ **Security Best Practices**
    Ensure a fortress around your deployment:
    - Update admin credentials for heightened security.
    - Implement encryption and follow industry security best practices.

    🤝 **Support and Contact**
    Need assistance or have questions? Reach out to our support team at [support@common-service.example.com](mailto:support@common-service.example.com).

    🔗 **Useful Links:**
    - [JSON Documentation]({$siteUrl}/json)
    - [YAML Documentation]({$siteUrl}/yaml)
    - [The source API definition](https://github.com/swagger-api/swagger-petstore/blob/master/src/main/resources/openapi.yaml)

    🌟 **Note:**
    Dive into the " .
            $storeName .
            ' API with confidence and craftsmanship!
        ';
    }

    /**
     * Get the 'Info' section data.
     *
     * @return array
     */
    protected function getGeneralInfo()
    {
        $storeName = Config::getString(self::CONFIG_PATH_STORE_NAME);
        $versionParts = Php::explode('.', $this->productMetadata->getVersion());

        if (! isset($versionParts[0]) || ! isset($versionParts[1])) {
            return []; // Major and minor version are not set - return empty response
        }

        $majorMinorVersion = $versionParts[0] . '.' . $versionParts[1];

        return [
            'version' => $majorMinorVersion,
            'license' => $this->getLicenseInfo(),
            'contact' => $this->getContactInfo(),
            'description' => $this->getDescription(),
            'title' => $storeName .
                ' ' .
                '(' .
                $this->productMetadata->getName() .
                ' ' .
                $this->productMetadata->getEdition() .
                ')',
        ];
    }

    /**
     * Generate path info based on method data.
     *
     * @param string $methodName
     * @param array $httpMethodData
     * @param string $tagName
     * @param string $uri
     *
     * @return array
     */
    protected function generatePathInfo(
        string $methodName,
        array $httpMethodData,
        string $tagName,
        string $uri,
    ): array {
        $methodData = $httpMethodData[Converter::KEY_METHOD];
        $uri = ucwords(
            Str::replace(['/{', '}/', '{', '}'], DIRECTORY_SEPARATOR, $uri),
            DIRECTORY_SEPARATOR,
        );

        $operationId =
            ucfirst($methodName) .
            Str::replace([DIRECTORY_SEPARATOR, '-'], '', $uri);

        $pathInfo = [
            'tags' => [$tagName],
            'description' => $methodData['documentation'],
            'operationId' => $operationId,
            'consumes' => $this->getConsumableDatatypes(),
            'produces' => $this->getProducibleDatatypes(),
        ];

        $parameters = $this->generateMethodParameters(
            $httpMethodData,
            $operationId,
        );

        if ($parameters) {
            $pathInfo['parameters'] = $parameters;
        }

        $pathInfo['responses'] = $this->generateMethodResponses($methodData);

        return $pathInfo;
    }

    /**
     * Generate Tag Info for given service.
     *
     * @param string $serviceName
     * @param array $serviceData
     *
     * @return array
     */
    protected function generateTagInfo($serviceName, $serviceData)
    {
        $tagInfo = [];
        $tagInfo['name'] = $serviceName;

        if (! Validator::isEmpty($serviceData) && Validator::isArray($serviceData)) {
            $tagInfo['description'] = $serviceData[Converter::KEY_DESCRIPTION];
        }

        return $tagInfo;
    }

    /**
     * Generate definition for given type.
     *
     * @param string $typeName
     *
     * @return array
     */
    protected function generateDefinition($typeName)
    {
        $properties = [];
        $requiredProperties = [];
        $typeData = $this->typeProcessor->getTypeData($typeName);

        if (isset($typeData['parameters'])) {
            foreach ($typeData['parameters'] as $parameterName => $parameterData) {
                $properties[$parameterName] = $this->getObjectSchema(
                    $parameterData['type'],
                    $parameterData['documentation'],
                );

                if ($parameterData['required']) {
                    $requiredProperties[] = $parameterName;
                }
            }
        }

        $definition = ['type' => 'object'];

        if (isset($typeData['documentation'])) {
            $definition['description'] = $typeData['documentation'];
        }

        if (! Validator::isEmpty($properties)) {
            $definition['properties'] = $properties;
        }

        if (! Validator::isEmpty($requiredProperties)) {
            $definition['required'] = $requiredProperties;
        }

        return $definition;
    }

    /**
     * Get license information.
     *
     * @return array
     */
    protected function getLicenseInfo()
    {
        // Retrieve license information from configuration using the helper function
        $licenseName = $this->swaggerHelper->getLicenseName();
        $licenseUrl = $this->swaggerHelper->getLicenseUrl();

        // Provide default values if not set
        $url = $licenseUrl ?: Config::getString('SWAGGER_LICENSE_URL');
        $name = $licenseName ?: Config::getString('SWAGGER_LICENSE_TYPE');

        // Check if both name and URL are set before returning the result
        if ($name && $url) {
            return ['name' => $name, 'url' => $url];
        }

        // Return an empty array if either name or URL is not set
        return [];
    }

    /**
     * Get admin contact information.
     *
     * @return array
     */
    protected function getContactInfo()
    {
        // Use the helper function to get contact information from configuration
        $contactUrl = $this->swaggerHelper->getContactUrl();
        $contactName = $this->swaggerHelper->getContactName();
        $contactEmail = $this->swaggerHelper->getContactEmail();

        // Use the retrieved values or provide default values if not set
        // Use the retrieved values or provide default values if not set
        $url = $contactUrl ?: Config::getString('TECHNICAL_CONTACT_URL');
        $name = $contactName ?: Config::getString('TECHNICAL_CONTACT_NAME');
        $email = $contactEmail ?: Config::getString('TECHNICAL_CONTACT_EMAIL');

        return [
            'name' => $name,
            'email' => $email,
            'url' => $url,
        ];
    }

    /**
     * Creates an array for the given query parameter.
     *
     * @param string $name
     * @param string $type
     * @param string $description
     * @param bool|null $required
     *
     * @return array
     */
    private function createQueryParam(
        $name,
        $type,
        $description,
        $required = null,
    ) {
        $param = [
            'name' => $name,
            'in' => 'query',
        ];

        $param = Arr::merge(
            $param,
            $this->getObjectSchema($type, $description),
        );

        if (isset($required)) {
            $param['required'] = $required;
        }

        return $param;
    }

    /**
     * Generate parameters based on method data.
     *
     * @param array $httpMethodData
     * @param string $operationId
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function generateMethodParameters($httpMethodData, $operationId)
    {
        $bodySchema = [];
        $parameters = [];

        $phpMethodData = $httpMethodData[Converter::KEY_METHOD];

        // Return nothing if necessary fields are not set
        if (
            ! isset($phpMethodData['interface']['in']['parameters']) ||
            ! isset($httpMethodData['uri']) ||
            ! isset($httpMethodData['httpOperation'])
        ) {
            return [];
        }

        foreach ($phpMethodData['interface']['in']['parameters'] as $parameterName => $parameterInfo) {
            // Omit forced parameters
            if (
                isset($httpMethodData['parameters'][$parameterName]['force']) &&
                $httpMethodData['parameters'][$parameterName]['force']
            ) {
                continue;
            }

            if (! isset($parameterInfo['type'])) {
                return [];
            }
            $description = $parameterInfo['documentation'] ?? null;

            // Get location of parameter
            if (
                Str::contains(
                    $httpMethodData['uri'],
                    (string)('{' . $parameterName . '}'),
                )
            ) {
                $parameters[] = $this->generateMethodPathParameter(
                    $parameterName,
                    $parameterInfo,
                    $description,
                );
            } elseif (
                mb_strtoupper($httpMethodData['httpOperation']) === 'GET'
            ) {
                $parameters = $this->generateMethodQueryParameters(
                    $parameterName,
                    $parameterInfo,
                    $description,
                    $parameters,
                );
            } else {
                $bodySchema = $this->generateBodySchema(
                    $parameterName,
                    $parameterInfo,
                    $description,
                    $bodySchema,
                );
            }
        }
    }

    /**
     * Generate method path parameter.
     *
     * @param string $parameterName
     * @param array $parameterInfo
     * @param string $description
     *
     * @return array
     */
    private function generateMethodPathParameter(
        $parameterName,
        $parameterInfo,
        $description,
    ) {
        $param = [
            'name' => $parameterName,
            'in' => 'path',
            'type' => $this->getSimpleType($parameterInfo['type']),
            'required' => true,
        ];

        if ($description) {
            $param['description'] = $description;

            return $param;
        }

        return $param;
    }

    /**
     * Generate method query parameters.
     *
     * @param string $parameterName
     * @param array $parameterInfo
     * @param string $description
     * @param array $parameters
     *
     * @return array
     */
    private function generateMethodQueryParameters(
        $parameterName,
        $parameterInfo,
        $description,
        $parameters,
    ) {
        $queryParams = $this->getQueryParamNames(
            $parameterName,
            $parameterInfo['type'],
            $description,
        );

        if (Php::count($queryParams) === 1) {
            // handle simple query parameter (includes the 'required' field)
            $parameters[] = $this->createQueryParam(
                $parameterName,
                $parameterInfo['type'],
                $description,
                $parameterInfo['required'],
            );
        } else {
            /*
             * Complex query parameters are represented by a set of names which describes the object's fields.
             *
             * Omits the 'required' field.
             */
            foreach ($queryParams as $name => $queryParamInfo) {
                $parameters[] = $this->createQueryParam(
                    $name,
                    $queryParamInfo['type'],
                    $queryParamInfo['description'],
                );
            }
        }

        return $parameters;
    }

    /**
     * Generate body schema.
     *
     * @param string $parameterName
     * @param array $parameterInfo
     * @param string $description
     * @param array $bodySchema
     *
     * @return array
     */
    private function generateBodySchema(
        $parameterName,
        $parameterInfo,
        $description,
        $bodySchema,
    ) {
        $required = $parameterInfo['required'] ?? null;

        /*
         * There can only be one body parameter, multiple PHP parameters are represented as different
         * properties of the body.
         */
        if ($required) {
            $bodySchema['required'][] = $parameterName;
        }

        $bodySchema['properties'][$parameterName] = $this->getObjectSchema(
            $parameterInfo['type'],
            $description,
        );

        $bodySchema['type'] = 'object';

        // Make sure we have a proper XML wrapper for request parameters for the XML format.
        if (! isset($bodySchema['xml']) || ! Validator::isArray($bodySchema['xml'])) {
            $bodySchema['xml'] = [];
        }

        if (
            ! isset($bodySchema['xml']['name']) ||
            Validator::isEmpty($bodySchema['xml']['name'])
        ) {
            $bodySchema['xml']['name'] = self::XML_SCHEMA_PARAMWRAPPER;
        }

        return $bodySchema;
    }

    /**
     * List out consumes data type.
     *
     * @return array
     */
    private function getConsumableDatatypes()
    {
        return ['application/json', 'application/xml'];
    }

    /**
     * List out produces data type.
     *
     * @return array
     */
    private function getProducibleDatatypes()
    {
        return ['application/json', 'application/xml'];
    }
}
