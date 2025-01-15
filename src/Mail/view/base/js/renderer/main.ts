import renderEmail from "./renderer";

// Automatically invoke the default export when the script is executed directly.
if (require.main === module) {
  // Step 8: Extract command-line arguments.
  // The first two arguments (node and script paths) are ignored.
  const [node, script, view, json, output = false] = process.argv;

  // Step 9: Validate required arguments (view and json).
  // If either is missing, log an error and exit the process.
  if (!view || !json) {
    console.error("❌ Error: Both the view module and JSON props are required.");
    process.exit(1);
  }

  // Step 10: Parse the output argument to determine whether files should be saved.
  const parsedOutput = output === "true";

  // Step 11: Start the rendering process with appropriate flags.
  // The silent flag suppresses logs when not saving files.
  if (parsedOutput) console.log("🚀 Starting render process...");
  renderEmail({ view, json, output: parsedOutput, silent: parsedOutput });
}
