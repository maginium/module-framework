<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum for attribute input types.
 *
 * This enum defines the various input types available for attributes in Magento 2.
 * Each type corresponds to a different form field style, allowing customization
 * of how data is captured and displayed in the Magento backend and frontend.
 *
 * @method static self TEXT() Represents a single-line text input type.
 * @method static self TEXTAREA() Represents a multi-line textarea input type.
 * @method static self SELECT() Represents a dropdown select input type.
 * @method static self MULTISELECT() Represents a multi-select input type allowing multiple choices.
 * @method static self BOOLEAN() Represents a boolean input type, typically rendered as a Yes/No toggle.
 * @method static self PRICE() Represents a price input type with currency formatting.
 * @method static self DATE() Represents a date input type using a date picker.
 * @method static self MEDIA_IMAGE() Represents an image uploader input type for media files.
 * @method static self GALLERY() Represents a gallery input type for managing multiple images.
 * @method static self INT() Represents an integer input type for numeric values.
 */
class AttributeInputType extends Enum
{
    /**
     * Represents a standard single-line text input field.
     *
     * Used for shorter text fields like names, titles, or small descriptions.
     */
    #[Label('Text')]
    #[Description('Standard single-line text input field.')]
    public const TEXT = 'text';

    /**
     * Represents a multi-line text input field.
     *
     * Useful for longer text entries, such as detailed descriptions or comments.
     */
    #[Label('Textarea')]
    #[Description('Multi-line text input field.')]
    public const TEXTAREA = 'textarea';

    /**
     * Represents a dropdown select field with a single selection option.
     *
     * Suitable for fields where only one choice is allowed from a list of options.
     */
    #[Label('Select')]
    #[Description('Dropdown select field with single selection.')]
    public const SELECT = 'select';

    /**
     * Represents a multi-select dropdown field, allowing multiple selections.
     *
     * Allows users to select more than one option, useful for categories, tags, etc.
     */
    #[Label('Multi-Select')]
    #[Description('Dropdown select field with multiple selections allowed.')]
    public const MULTISELECT = 'multiselect';

    /**
     * Represents a boolean input type, typically rendered as a Yes/No toggle.
     *
     * Commonly used for status fields, switches, or other binary choices.
     */
    #[Label('Boolean')]
    #[Description('Yes/No toggle input.')]
    public const BOOLEAN = 'boolean';

    /**
     * Represents a price input field with currency formatting.
     *
     * Suitable for fields capturing monetary values, like product prices.
     */
    #[Label('Price')]
    #[Description('Price input with currency formatting.')]
    public const PRICE = 'price';

    /**
     * Represents a date input field, often rendered with a date picker.
     *
     * Used for capturing specific dates, such as order dates or expiration dates.
     */
    #[Label('Date')]
    #[Description('Date picker input.')]
    public const DATE = 'date';

    /**
     * Represents an image uploader input for media files.
     *
     * Allows users to upload a single image file, useful for product images or avatars.
     */
    #[Label('Image')]
    #[Description('Image uploader input for media files.')]
    public const IMAGE = 'image';

    /**
     * Represents a gallery input for managing multiple images.
     *
     * Useful for creating an image gallery, such as a product image gallery.
     */
    #[Label('Gallery')]
    #[Description('Gallery input for multiple images.')]
    public const GALLERY = 'gallery';

    /**
     * Represents an integer input field for numeric values.
     *
     * Useful for capturing whole number inputs like quantities or IDs.
     */
    #[Label('Integer')]
    #[Description('Integer input for numeric values.')]
    public const INT = 'int';
}
