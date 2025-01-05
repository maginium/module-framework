<?php

declare(strict_types=1);

namespace Maginium\Framework\Swagger\Blocks;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Swagger\Api\Data\SchemaTypeInterface;
use Magento\Swagger\Block\Index as MagentoIndex;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Php;
use UnexpectedValueException;

/**
 * Block for swagger index page.
 */
class Index extends MagentoIndex
{
    /**
     * Configuration path for store name.
     */
    private const CONFIG_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @param Context $context
     * @param ProductMetadataInterface $productMetadata
     *
     * NOTE: OPTIONAL DI
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductMetadataInterface $productMetadata,

        // OPTIONAL DI
        array $data = [],
    ) {
        parent::__construct($context, $data);

        $this->productMetadata = $productMetadata;
    }

    /**
     * @return string|null
     *
     * @since 100.2.1
     */
    public function getSchemaUrl()
    {
        if ($this->getSchemaType() === null) {
            return;
        }

        return rtrim($this->getBaseUrl(), DIRECTORY_SEPARATOR) .
            $this->getSchemaType()->getSchemaUrlPath($this->getParamStore());
    }

    /**
     * Get the data for console output.
     *
     * @return array
     */
    public function getConsoleData()
    {
        $storeName = Config::getString(self::CONFIG_PATH_STORE_NAME);
        $versionParts = Php::explode('.', $this->productMetadata->getVersion());

        if (! isset($versionParts[0]) || ! isset($versionParts[1])) {
            return []; // Major and minor version are not set - return an empty response
        }

        $majorMinorVersion = $versionParts[0] . '.' . $versionParts[1];

        // TODO: REFACTOR THIS
        // Dummy values as placeholders
        $dummyValues = [
            'DEV_SERVER_URL' => 'http://localhost:8080',
            'PROD_SERVER_URL' => 'https://yourproductionserver.com',
        ];

        return [
            'appName' => $storeName,
            'description' => Config::getString('APP_KEYWORDS'),
            'systemInfo' => [
                // ENV AND VERSION INFO
                'magentoVersion' => $majorMinorVersion,
                'environment' => Config::getString('APP_ENV'),

                'host' => Config::getString('APP_HOST'),
                'port' => Config::getString('APP_PORT'),
                'devServerUrl' => $dummyValues['DEV_SERVER_URL'],
                'prodServerUrl' => $dummyValues['PROD_SERVER_URL'],
                'apiVersion' => Config::getString('SWAGGER_API_VERSION'),

                // CONTACT INFO
                'contactUrl' => Config::getString('TECHNICAL_CONTACT_URL'),
                'contactName' => Config::getString('TECHNICAL_CONTACT_NAME'),
                'contactEmail' => Config::getString('TECHNICAL_CONTACT_EMAIL'),
            ],
            'developerInfo' => [
                'name' => Config::getString('AUTHOR'),
                'email' => Config::getString('AUTHOR_EMAIL'),
                'github' => Config::getString('AUTHOR_GITHUB'),
            ],
        ];
    }

    /**
     * @return mixed|string
     */
    private function getParamStore()
    {
        return Request::getParam('store') ?: 'all';
    }

    /**
     * @return SchemaTypeInterface|null
     */
    private function getSchemaType()
    {
        if (! $this->hasSchemaTypes()) {
            return;
        }

        $schemaTypeCode = Request::getParam(
            'type',
            $this->getDefaultSchemaTypeCode(),
        );

        if (! Php::arrayKeyExists($schemaTypeCode, $this->getSchemaTypes())) {
            // Throw the exception
            throw new UnexpectedValueException(
                __('Unknown schema type supplied')->getText(),
            );
        }

        return $this->getSchemaTypes()[$schemaTypeCode];
    }
}
