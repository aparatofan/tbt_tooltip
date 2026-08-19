# TBT Tooltip — Claude Code guidance

Keep this file concise. It is loaded at the start of every Claude Code session.

## Start here

- Work from the task, not from a repository-wide scan.
- Check `git status`, search for the relevant symbol/selector, and read only the needed file sections.
- The deployable plugin lives under `tbt-tooltip/`; the repository root also contains docs/workflow files.
- Do not change unrelated tooltip markup, generator output, copy, styling, or formatting.
- Inspect the final diff before finishing.

## Project basics

- WordPress plugin for The Blue Tree English lessons.
- Main file: `tbt-tooltip/tbt-tooltip.php`.
- Admin generator: `tbt-tooltip/admin/`.
- Saved-tooltip post type/shortcode logic: `tbt-tooltip/includes/`.
- Frontend assets: `tbt-tooltip/assets/`.
- Saved tooltips embed with `[tbt_tooltip id="…"]`.
- The plugin registers itself on TBT Hub, but its core behavior must not depend on Hub being active.

## Behavior to preserve

- The admin generator turns pasted lesson text into ready-to-use tooltip HTML for WordPress/Divi Code blocks.
- Existing hand-pasted tooltip markup is a compatibility surface; do not casually rename classes, data attributes, or output structure.
- Frontend tooltip CSS/JS is deliberately enqueued on every public page. Do not optimize this into shortcode/post-content detection without an explicit task: Divi Theme Builder content can evade such detection.
- Keep the unique `tbt-tooltip-css` and `tbt-tooltip-js` asset handles.
- Preserve both saved-tooltip shortcode rendering and manually pasted tooltip markup.

## Security and WordPress rules

- Preserve `edit_posts`/existing capability boundaries for authoring tools.
- Sanitize stored/generated values and escape PHP output for its context.
- Treat pasted generator input as untrusted before placing it into generated HTML or preview DOM.
- Never commit credentials, FTP details, API keys, or local configuration.

## Coding style

- Follow the surrounding PHP and vanilla-JS/CSS style rather than reformatting whole files.
- Prefer small local changes and reuse existing classes, hooks, selectors, and handles.
- Keep comments that explain non-obvious compatibility decisions, especially unconditional frontend asset loading.
- Do not add a framework or build system for a focused change.

## Validation

For changed PHP files, run `php -l <file>`.

For changed JavaScript files, run `node --check <file>` when Node is available.

For tooltip behavior changes, check both:
- saved `[tbt_tooltip]` output, and
- manually pasted tooltip HTML in a lesson/Divi context.

CSS positioning, hover/focus behavior, and cache/minification interactions still require a live browser check when affected.

## Git and deployment

- `main` is the integration branch; use a focused feature branch.
- A push to `main` deploys `./tbt-tooltip/` to `/tbt-tooltip/` over FTPS.
- Markdown is excluded from the FTP upload, though a push to `main` still starts the workflow.
- Never alter deployment paths or credentials unless the task specifically concerns deployment.

## Context discipline

- Prefer targeted search + narrow reads over broad exploration.
- Read `README.md` or `docs/` only when the task needs historical/setup context.
- Do not paste large generated HTML or entire source files into the conversation when an excerpt is enough.
- Finish with a short summary of changes, validation, and any remaining live-site check.
- For a new unrelated task, prefer a fresh Claude Code session.
