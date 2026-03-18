/**
 * This runs anytime before committing files
 */
export default {
  "**/*.{js,css}": [
    "prettier --write",
    // () => "pnpm run build", // ← ignore files
    // () => "git add ./assets", // ← ignore files
  ],
  "**/*.php": [
    "vendor/bin/pint",
    () => "composer analyse", // ← ignore files (otherwise pest files would be analysed, too)
    () => "tools/make-pot.sh", // ← ignore files
    () => "git add ./languages", // ← ignore files
  ],
};
