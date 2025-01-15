import * as React from "react";
import { render } from "@react-email/render";
import prettier from "prettier";

/**
 * Renders the email component into both HTML and plain text formats.
 *
 * This utility function takes a React functional component and its props,
 * and generates two formats of the email:
 * - **HTML**: A well-structured and optionally formatted HTML string.
 * - **Plain Text**: A plain text representation of the email.
 *
 * The function also applies Prettier formatting to the generated HTML to ensure it adheres
 * to a consistent style.
 *
 * @function renderEmailContent
 * @param {React.FC<any>} Component - A React functional component representing the email template.
 * @param {any} props - Props to pass to the component for rendering. These are typically dynamic data for the email.
 * @returns {Promise<{ html: string; text: string }>} A promise resolving to an object containing:
 * - **html**: The formatted HTML string.
 * - **text**: The plain text string.
 *
 * @example
 * ```typescript
 * import { renderEmailContent } from './utils/render-utils.util';
 *
 * const emailProps = { userName: 'John Doe', activationLink: 'https://example.com' };
 * const { html, text } = await renderEmailContent(ActivationEmail, emailProps);
 *
 * console.log(html); // Rendered and formatted HTML
 * console.log(text); // Plain text representation
 * ```
 */
export async function renderEmailContent(
  Component: React.FC<any>, // The React component to render (e.g., the email template).
  props: any, // The props to pass into the component, typically dynamic data for rendering.
): Promise<{ html: string; text: string }> {
  // Step 1: Render the React component into an HTML string.
  const html = await render(<Component {...props} />);

  // Step 2: Format the generated HTML using Prettier to ensure consistent styling.
  const formattedHtml = await prettier.format(html, { parser: "html" });

  // Step 3: Render the React component into a plain text string.
  const text = await render(<Component {...props} />, {
    plainText: true, // This option ensures the output is in plain text format.
  });

  // Step 4: Return an object containing the formatted HTML and plain text versions of the email.
  return { html: formattedHtml, text };
}
