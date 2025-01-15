<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces;

use Maginium\Framework\Support\DataObject;

/**
 * Interface RendererInterface.
 *
 * Defines the contract for rendering React-based email templates by invoking a Node.js process.
 * Implementing classes must provide the logic for rendering templates and creating processes.
 */
interface RendererInterface
{
    /**
     * Renders a React-based email template and returns the output as an array.
     *
     * @param string $view The name of the React component to render.
     * @param array $data Data to pass as props to the component.
     *
     * @return DataObject The rendered template data.
     */
    public function render(string $view, array $data): DataObject;
}
