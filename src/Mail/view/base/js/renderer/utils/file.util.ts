import fs from "fs"; // Importing the File System module for file operations.
import path from "path"; // Importing the Path module to handle file paths.

/**
 * Writes content to a specified file.
 *
 * This function writes the provided content to a file located at the given path.
 * If the file does not exist, it will be created. If it does exist, its contents will be overwritten.
 *
 * @function writeFile
 * @param {string} filePath - The absolute or relative path to the file where content will be written.
 * @param {string} content - The content to write into the file.
 *
 * @example
 * ```typescript
 * writeFile("output/email.html", "<h1>Hello, World!</h1>");
 * ```
 */
export function writeFile(filePath: string, content: string): void {
  // Synchronously writes the content to the file using UTF-8 encoding.
  fs.writeFileSync(filePath, content, "utf8");
}

/**
 * Constructs output paths for HTML and plain text files based on the input view file path.
 *
 * Given the path to a view file (typically a `.tsx` file representing an email template),
 * this function generates file paths for the corresponding HTML and plain text outputs.
 *
 * - The HTML file will have the same base name as the view file but with a `.html` extension.
 * - The plain text file will have the same base name as the view file but with a `.text` extension.
 *
 * @function getOutputPaths
 * @param {string} viewPath - The absolute or relative path to the view file (e.g., "src/emails/WelcomeEmail.tsx").
 * @returns {object} An object containing the following properties:
 * - **html**: The path for the HTML output file (e.g., "src/emails/WelcomeEmail.html").
 * - **text**: The path for the plain text output file (e.g., "src/emails/WelcomeEmail.text").
 *
 * @example
 * ```typescript
 * const paths = getOutputPaths("src/emails/WelcomeEmail.tsx");
 * console.log(paths.html); // Outputs: "src/emails/WelcomeEmail.html"
 * console.log(paths.text); // Outputs: "src/emails/WelcomeEmail.text"
 * ```
 */
export function getOutputPaths(viewPath: string): { html: string; text: string } {
  // Extract the directory of the view file.
  const dir = path.dirname(viewPath);

  // Extract the base name of the view file without its extension.
  const baseName = path.basename(viewPath, ".tsx");

  // Construct the output paths for the HTML and text files.
  return {
    html: path.join(dir, `${baseName}.html`), // Path for the HTML file.
    text: path.join(dir, `${baseName}.text`), // Path for the plain text file.
  };
}
