/**
 * Interface representing the arguments passed via command-line.
 */
export interface Args {
  /**
   * The view option specifies which view should be rendered.
   */
  view: string;

  /**
   * The json option specifies whether the output should be formatted as JSON.
   */
  json: string;

  /**
   * The output option determines whether the result should be outputted to the console or a file.
   */
  output: boolean;

  /**
   * The silent option suppresses output messages.
   */
  silent?: boolean;
}
