<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interceptors\Model\Template;

use Magento\Email\Model\Template\Filter as BaseFilter;
use Maginium\Framework\Mail\Models\TemplateFilter;

/**
 * Plugin for modifying template filtering logic.
 */
class Filter
{
    /**
     * @var TemplateFilter
     */
    private $templateFilter;

    /**
     * Constructor to inject dependencies.
     *
     * @param TemplateFilter $templateFilter The custom template filter logic.
     */
    public function __construct(TemplateFilter $templateFilter)
    {
        $this->templateFilter = $templateFilter;
    }

    /**
     * Before plugin to modify the value before the filter method is executed.
     *
     * @param BaseFilter $subject The original filter object.
     * @param string $value The input string to filter.
     *
     * @return array The modified input string as an array.
     */
    public function beforeFilter(BaseFilter $subject, string $value): array
    {
        return $this->templateFilter->process($subject, $value);
    }
}
