<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Maginium\Foundation\Concerns\HasOptionSource;
use Override;

/**
 * Class WebSafeFonts
 * Provides options for selecting web safe fonts.
 */
class WebSafeFonts implements OptionSourceInterface
{
    use HasOptionSource;

    /**
     * Retrieve options in a "key-value" format.
     *
     * This method must be implemented by child classes to provide
     * specific key-value options for configuration.
     *
     * @return array An associative array of options in "key => value" format.
     */
    public function toArray(): array
    {
        return [
            'sans-serif' => [
                'Arial_sans-serif' => 'Arial',
                'Helvetica_sans-serif' => 'Helvetica',
                'Trebuchet+MS_sans-serif' => 'Trebuchet MS',
                'Comic+Sans+MS_sans-serif' => 'Comic Sans MS',
                'Lucida+Grande_sans-serif' => 'Lucida Grande',
                'Verdana_sans-serif' => 'Verdana',
            ],
            'serif' => [
                'Courier_serif' => 'Courier',
                'Georgia_serif' => 'Georgia',
                'Times+New+Roman_serif' => 'Times New Roman',
            ],
        ];
    }

    /**
     * Retrieve options array.
     *
     * @return array
     */
    #[Override]
    public function toOptionArray()
    {
        $fonts = $this->toArray();

        $options = [];

        // First option (inherit)
        $options[] = [
            'value' => 'inherit',
            'label' => __('Inherit (from its parent)'),
        ];

        // Generate options for sans-serif fonts
        $ssOptions = [];

        foreach ($fonts['sans-serif'] as $value => $label) {
            $ssOptions[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        // Add sans-serif options to main options array
        $options[] = [
            'value' => $ssOptions,
            'label' => __('sans-serif'),
        ];

        // Generate options for serif fonts
        $sOptions = [];

        foreach ($fonts['serif'] as $value => $label) {
            $sOptions[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        // Add serif options to main options array
        $options[] = [
            'value' => $sOptions,
            'label' => __('serif'),
        ];

        return $options;
    }
}
