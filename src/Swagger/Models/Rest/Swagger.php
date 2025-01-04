<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Swagger\Models\Rest;

use Magento\Webapi\Model\Rest\Swagger as MagentoSwagger;
use Pixicommerce\Framework\Support\Arr;
use Pixicommerce\Framework\Support\Validator;

/**
 * Custom Swagger Specification Model
 * Extends Magento's Swagger class to customize Swagger specification.
 *
 * @link https://github.com/swagger-api/swagger-spec/blob/master/versions/2.0.md Swagger specification
 *
 * @method Swagger setHost(string $host)
 * @method Swagger setDefinitions(array $definitions)
 * @method Swagger setSchemes(array $schemes)
 */
class Swagger extends MagentoSwagger
{
    /**
     * Constructor
     * Initializes the Swagger specification with default values.
     */
    public function __construct()
    {
        // Call the parent constructor to set up default values
        parent::__construct();

        // Define custom data for securityDefinitions, swagger version, and info
        $customData = [
            'securityDefinitions' => [
                'api_key' => [
                    'type' => 'apiKey',
                    'name' => 'api_key',
                    'in' => 'header',
                ],
            ],
            'swagger' => parent::SWAGGER_VERSION,
            'info' => [
                'title' => '',
                'description' => '',
                'version' => '',
                'contact' => [
                    'url' => '',
                    'name' => '',
                    'email' => '',
                ],
                'license' => '',
                'termsOfService' => '',
            ],
        ];

        // Merge the custom data with the existing data
        $this->_data = Arr::merge($this->_data, $customData);
    }

    /**
     * Add Info section data.
     *
     * @param array $info
     *
     * @return $this
     */
    public function setInfo($info)
    {
        if (! Validator::isArray($info)) {
            return $this;
        }

        if (isset($info['title'])) {
            $this->_data['info']['title'] = $info['title'];
        }

        if (isset($info['version'])) {
            $this->_data['info']['version'] = $info['version'];
        }

        if (isset($info['description'])) {
            $this->_data['info']['description'] = $info['description'];
        }

        if (isset($info['termsOfService'])) {
            $this->_data['info']['termsOfService'] = $info['termsOfService'];
        }

        if (isset($info['contact'])) {
            $this->_data['info']['contact'] = $info['contact'];
        }

        if (isset($info['license'])) {
            $this->_data['info']['license'] = $info['license'];
        }

        return $this;
    }
}
