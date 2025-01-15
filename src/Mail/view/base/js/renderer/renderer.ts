import { Args } from "./interfaces";
import { ViewModule } from "./types";
import { renderEmailContent, parseProps, getOutputPaths, writeFile } from "./utils";

/**
 * Renders the email template to both HTML and plain text formats.
 * The generated HTML and text are either saved to files or output directly as JSON.
 *
 * @function renderEmail
 * @param {Args} args - The arguments for rendering the email (view module, JSON props, and optional output directory).
 * @param {boolean} silent - Whether to suppress log messages (useful for JSON-only output).
 * @returns {Promise<void>} - A promise that resolves when the rendering and file saving is complete.
 * @throws {Error} - If there is an issue with rendering or saving the files.
 */
export default async function renderEmail(args: Args): Promise<void> {
  try {
    // Step 1: Import the view module dynamically.
    // This allows the script to render a specific email component by its path.
    if (args.silent) console.log("🔄 Importing view module...");
    const module = (await import(args.view)) as ViewModule;

    // Step 2: Check if the module has a default export.
    // If not, throw an error to inform the user of the invalid module structure.
    if (!module.default) {
      throw new Error(`❌ The module at ${args.view} does not have a default export.`);
    }

    // Step 3: Parse the JSON string provided as input.
    // Converts the JSON props into an object that can be passed to the email component.
    const props = parseProps(args.json);

    // Step 4: Render the email content into both HTML and plain text formats.
    // The renderEmailContent utility handles rendering and formatting.
    if (args.silent) console.log("📄 Rendering content...");
    const { html, text } = await renderEmailContent(module.default, props);

    // Step 5: Handle output based on the `output` argument.
    // If output is specified, save the rendered HTML and text to files.
    if (args.output) {
      // Step 5.1: Generate output file paths based on the view module's name.
      const { html: htmlPath, text: textPath } = getOutputPaths(args.view);

      // Step 5.2: Save the rendered content to the respective files.
      if (args.silent) console.log("💾 Saving output files...");
      writeFile(htmlPath, html);
      writeFile(textPath, text);

      // Step 5.3: Log success messages with the file paths for user reference.
      if (args.silent) {
        console.log(`✅ HTML output saved to: ${htmlPath}`);
        console.log(`✅ Text output saved to: ${textPath}`);
      }
    } else {
      // Step 6: If no output directory is specified, log the content as JSON.
      // Useful for piping the output to another process.
      console.log(JSON.stringify({ html, text }));
    }
  } catch (error) {
    // Step 7: Handle errors gracefully.
    // Log a meaningful error message and exit the process with a failure code.
    if (error instanceof Error) {
      console.error("❌ Render error:", error.message);
    } else {
      console.error("❗ Unexpected error:", error);
    }
    process.exit(1);
  }
}
