/**
 * Parses a JSON string into a JavaScript object.
 *
 * This utility function takes a JSON string as input and attempts to parse it into a
 * JavaScript object. If the JSON string is invalid or improperly formatted, an error is thrown,
 * allowing the calling code to handle it appropriately.
 *
 * @function parseProps
 * @param {string} json - The JSON string to be parsed. It should follow the JSON specification.
 * @returns {any} - The parsed JavaScript object resulting from the JSON string.
 *
 * @throws {Error} - Throws an error if:
 * - The input string is not valid JSON.
 * - A syntax issue is present in the JSON string.
 *
 * @example
 * ```typescript
 * try {
 *   const props = parseProps('{"name": "John", "age": 30}');
 *   console.log(props.name); // Outputs: John
 * } catch (error) {
 *   console.error(error.message);
 * }
 * ```
 *
 * @example
 * ```typescript
 * // Example with invalid JSON
 * try {
 *   const props = parseProps('{"name": "John", "age": 30,}');
 * } catch (error) {
 *   console.error(error.message); // Outputs: ⚠️ Error parsing JSON: Unexpected token } in JSON at position ...
 * }
 * ```
 */
export function parseProps(json: string): any {
  try {
    // Parse the input JSON string into a JavaScript object.
    return JSON.parse(json);
  } catch (parseError: any) {
    // If parsing fails, throw a descriptive error with the original error message.
    throw new Error(`⚠️ Error parsing JSON: ${parseError.message}`);
  }
}
