# Vendored libraries

Third-party browser libraries bundled with the plugin (no build step; commit the file as-is).

## idiomorph.min.js
- **Version:** 0.7.3
- **Source:** https://github.com/bigskysoftware/idiomorph
- **License:** 0BSD
- **Used by:** `assets/js/completion.js` — DOM-morphs the server-rendered response after a
  completion write so the page updates in place instead of reloading.

To update: download the matching `dist/idiomorph.min.js` for the new version and bump the
handle version in `CourseExp_Core::enqueue_public_assets()`.
