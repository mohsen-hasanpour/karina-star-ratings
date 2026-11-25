# Karina Star Ratings & Rich Snippets

Fast, accessible star ratings for any post type with automatic Schema.org **AggregateRating** rich snippets, a shortcode, a Gutenberg block and an Elementor widget.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/karina-star-ratings)](https://wordpress.org/plugins/karina-star-ratings/)
[![License: GPL v2+](https://img.shields.io/badge/license-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Install from WordPress.org:** https://wordpress.org/plugins/karina-star-ratings/

## Features

- Automatic placement before or after the content of the post types you choose
- Per-post JSON-LD rich snippets generated from real votes
- `[ratingpress]` and `[ratingpress_top]` shortcodes, a block and an Elementor widget
- Settings with a live preview, and a dashboard with vote statistics
- Works with page caching (votes go through the REST API)
- Importer for kk Star Ratings data
- Privacy-friendly: IP addresses are stored only as salted one-way hashes

## Requirements

- WordPress 5.8 or later
- PHP 7.4 or later

## Development

This repository is the source of truth for the plugin. Releases are deployed to the
[WordPress.org SVN repository](https://plugins.svn.wordpress.org/karina-star-ratings/) by the
[`Deploy to WordPress.org`](.github/workflows/deploy.yml) workflow.

### Releasing a new version

1. Bump the version in the plugin header (`Version:`), `RATINGPRESS_VERSION` and the `Stable tag` in `readme.txt`.
2. Add a changelog entry (`= x.y.z =`) to `readme.txt`.
3. Commit, then tag and push:
   ```sh
   git tag vX.Y.Z
   git push origin main vX.Y.Z
   ```

The workflow checks that all version numbers match the tag, then copies the plugin to SVN `trunk`,
creates `tags/X.Y.Z`, and syncs `.wordpress-org/` (banners, icons, screenshots) to SVN `assets`.
Files listed in [`.distignore`](.distignore) are not deployed.

To test without publishing, run the workflow manually from the **Actions** tab: it performs a dry run
and lists what would change in SVN.

See [`readme.txt`](readme.txt) for the full description, FAQ and changelog.

## License

GPL-2.0-or-later. The bundled Vazirmatn font is licensed under the SIL Open Font License (see `assets/fonts/vazirmatn/OFL.txt`).
