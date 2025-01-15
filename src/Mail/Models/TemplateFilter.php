<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Magento\Email\Model\AbstractTemplate;
use Magento\Email\Model\Template\Config;
use Magento\Email\Model\Template\Filter;
use Maginium\Framework\Mail\Interfaces\TemplateTypesInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facade\Renderer;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;

/**
 * Class Filter.
 *
 * This class intercepts and modifies the email template filtering process. It allows for custom logic
 * before and after the filtering process, enabling more advanced template processing. It utilizes reflection
 * to retrieve and process the template variables and is capable of handling various template types,
 * such as React templates. It also includes error handling and logging for debugging and production environments.
 */
class TemplateFilter
{
    /**
     * @var Config
     *
     * Email configuration object injected via constructor. This contains various email-related configurations.
     */
    protected $emailConfig;

    /**
     * @var AbstractTemplate
     *
     * Holds the template object that will be used in processing.
     */
    protected AbstractTemplate $abstractTemplate;

    /**
     * Constructor to inject dependencies.
     *
     * @param Config $emailConfig The email configuration.
     *
     * Initializes the emailConfig property with the passed $emailConfig object.
     */
    public function __construct(Config $emailConfig)
    {
        $this->emailConfig = $emailConfig;
    }

    /**
     * Before plugin to modify the value before the filter method.
     *
     * @param Filter $filter
     * @param string $value
     *
     * @return array Modified value as an array to proceed with the original method.
     */
    public function process(Filter $filter, string $value): array
    {
        if (! $this->isReactEmail($value)) {
            return [$value];
        }

        // Access the template variables using reflection from the filter (BaseFilter object).
        $templateVars = $this->getTemplateVars($filter);

        // After obtaining the template variables, process the template (e.g., for rendering React templates).
        $processedContent = $this->processTemplate($templateVars);

        // Return the modified value as an array, as Magento expects the arguments to be passed like this in the before plugin.
        return [$processedContent ?? $value];
    }

    /**
     * Get the templateVars property using reflection and prepare them.
     *
     * This method uses reflection to access the private 'templateVars' property and process its values.
     *
     * @param Filter $filter
     *
     * @return DataObject A DataObject instance containing the processed template variables.
     */
    private function getTemplateVars(Filter $filter): DataObject
    {
        // Use reflection to get the 'templateVars' property from the Filter object.
        $property = Reflection::getProperty($filter, 'templateVars');
        $property->setAccessible(true);

        // Prepare the template variables by converting arrays to DataObjects.
        $templateVars = $this->prepareVars($property->getValue($filter));

        // Extract the 'this' context from the variables and store it in the abstractTemplate property.
        $this->abstractTemplate = $templateVars->getThis();

        // Remove the 'this' key from the template variables as it is now stored separately.
        $templateVars->unsetData('this');

        // Return the processed template variables.
        return $templateVars;
    }

    /**
     * Process the template if required, e.g., render React templates.
     *
     * This method checks if the template is of a specific type (e.g., React) and processes it accordingly.
     *
     * @param DataObject $templateVars The processed template variables.
     *
     * @return string|null The processed content if the template type is 'react', otherwise null.
     */
    private function processTemplate(DataObject $templateVars): ?string
    {
        // Get the template ID and type from the abstract template object.
        $templateId = $this->abstractTemplate->getId();
        $templateType = $this->abstractTemplate->getType();

        // Check if the template type is 'react'.
        if ($templateType === TemplateTypesInterface::TYPE_REACT) {
            // Get the file path
            $view = $this->emailConfig->getTemplateFilename($templateId);

            // Use the custom Renderer to render the React template with the provided template variables.
            $processedContent = Renderer::render($view, $templateVars->toArray());

            // Return the HTML content of the processed template.
            return $processedContent->getHtml();
        }

        // If no processing is required (template type is not 'react'), return null.
        return null;
    }

    /**
     * Determines if the given string indicates React usage.
     *
     * The function checks for specific patterns, such as JSX syntax,
     * React imports, or React component usage, to identify React code.
     *
     * @param string $inputString The string to analyze.
     *
     * @return bool True if the string indicates React usage; otherwise, false.
     */
    private function isReactEmail(string $inputString): bool
    {
        // Define patterns to check for React usage
        $patterns = [
            '/<\w+.*?>.*<\/\w+>/s', // JSX-like tags
            '/import\s+.*\s+from\s+[\'"]react[\'"];?/i', // React import
            '/@react-email\/components/i', // React email components
        ];

        // Check if any of the patterns match the input string
        foreach ($patterns as $pattern) {
            if (Str::match($pattern, $inputString)) {
                // React usage detected
                return true;
            }
        }

        // No React patterns detected
        return false;
    }

    /**
     * Recursively prepare variables and convert arrays into DataObject.
     *
     * This method ensures that all array-like structures within the variables are recursively processed and converted into DataObject instances.
     *
     * @param array $vars The variables to be processed, which can be an array or object.
     *
     * @return DataObject A DataObject instance wrapping the variables.
     */
    private function prepareVars(array $vars): DataObject
    {
        // Check if $vars is an array.
        if (is_array($vars)) {
            // Loop through the array and recursively process any nested arrays.
            foreach ($vars as $key => $value) {
                // If a value is an array, recursively convert it into a DataObject.
                if (is_array($value)) {
                    // Recursively process the nested array.
                    $vars[$key] = $this->prepareVars($value);
                }
            }
        }

        // Return a DataObject instance wrapping the processed array.
        return DataObject::make($vars);
    }
}
