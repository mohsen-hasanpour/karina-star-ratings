=== Karina Star Ratings & Rich Snippets ===
Contributors: mohsenhasanpour
Tags: rating, star rating, schema, rich snippets, elementor
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, accessible star ratings for any post type with automatic Schema.org AggregateRating rich snippets, a shortcode, a block and an Elementor widget.

== Description ==

Karina Star Ratings adds a lightweight star rating widget to your posts, pages and custom post types and automatically generates **Schema.org AggregateRating** structured data for every rated post that displays the rating, so search engines can show review stars in their results.

= Highlights =

* **Automatic placement** before or after the content of the post types you choose.
* **Dynamic rich snippets**: JSON-LD is generated per post from its real votes and printed only on pages that display the rating. Choose the schema type globally or per post.
* **Shortcode** `[ratingpress]` for any theme, widget area or page builder.
* **Elementor widget** with full style controls (size, spacing, colors, typography, responsive alignment) that works in Theme Builder templates.
* **Block editor block** that also works inside Query Loops.
* **Top rated list** with `[ratingpress_top]`.
* **Import from kk Star Ratings** in one click. Existing votes and averages are preserved and re-running the import never doubles numbers. Old `[kkstarratings]` shortcodes keep working.
* **Duplicate protection**: one vote per browser (cookie and local storage), per IP address, or unlimited. Logged-in users vote once per post. Browser mode stops accidental repeat votes but not determined scripts; the built-in per-IP rate limit and IP mode add server-side protection.
* **Privacy friendly**: IP addresses are stored only as salted one-way hashes. Integrates with the WordPress personal data exporter and eraser.
* **Accessible**: keyboard operable, screen reader labels, respects reduced motion.
* **RTL ready** and fully translatable.
* **Cache friendly**: voting uses the REST API and keeps working on cached pages.
* **Fast**: no jQuery on the front end, a single small script loaded only where ratings are shown, aggregates cached in post meta.
* **Dashboard** with vote statistics, distribution, top rated and recent votes.
* **Sortable rating column** in the posts list and a per-post sidebar panel to disable ratings, override the schema type or reset votes.
* **Developer friendly**: template tags, overridable templates and many hooks.

= Shortcodes =

`[ratingpress]` – rating widget for the current post.

Attributes: `id`, `readonly`, `legend`, `heading`, `icon` (star, heart, circle), `align` (start, center, end), `layout` (inline, stacked), `size`, `class`.

`[ratingpress_top count="5" post_type="post" min_votes="1" thumbnail="true"]` – list of top rated posts. `post_type` accepts a comma separated list of public post types or `any`; `count` is limited to 50.

= Template tags =

`<?php if ( function_exists( 'ratingpress_the_rating' ) ) { ratingpress_the_rating(); } ?>`

`ratingpress_get_rating( $post_id )` returns the average, count, best rating and percentage.

To customize the markup, copy `views/frontend/rating.php` to `your-theme/ratingpress/rating.php`.

= Hooks =

* `ratingpress_schema` – filter the JSON-LD of a post.
* `ratingpress_print_schema` – filter whether the JSON-LD is printed on the current page (by default only where the rating widget is displayed, including widgets printed by a theme template tag).
* `ratingpress_schema_types` – filter the available schema types.
* `ratingpress_auto_insert` – filter automatic placement per post.
* `ratingpress_enqueue_assets` – filter whether the assets load in the head of a singular page.
* `ratingpress_can_vote` – return a `WP_Error` to reject a vote.
* `ratingpress_vote_recorded` – action after a vote is saved.
* `ratingpress_rating_updated` – action after the totals of a post change.
* `ratingpress_rating_html` / `ratingpress_view_args` – filter the widget output.
* `ratingpress_client_ip` – provide the visitor IP when behind a trusted proxy.
* `ratingpress_template` – filter the template file.

= Languages =

The interface is fully translatable and RTL ready. Translations are managed on translate.wordpress.org and are installed automatically by WordPress.

= Third-party resources =

* [Vazirmatn](https://github.com/rastikerdar/vazirmatn) font, used in the Karina Star Ratings admin screens. Copyright 2015 The Vazirmatn Project Authors. Licensed under the SIL Open Font License 1.1 (see `assets/fonts/vazirmatn/OFL.txt`). The font is bundled with the plugin; nothing is loaded from external servers.

== Installation ==

1. Upload the `karina-star-ratings` folder to `/wp-content/plugins/` or install the plugin from the Plugins screen.
2. Activate it.
3. Go to **Karina Star Ratings → Settings** to choose post types, placement and appearance.
4. Coming from kk Star Ratings? Open **Karina Star Ratings → Settings → Tools** and click **Start import**, then deactivate kk Star Ratings.

== Frequently Asked Questions ==

= Will search engines show stars for my posts? =

Karina Star Ratings outputs AggregateRating structured data on pages that display the rating. Whether stars are displayed is decided by the search engine. Google shows review stars only for specific types, which is why posts use "Creative Work Series" by default. Some types need more properties than a rating before Google accepts them: Event, Course, LocalBusiness, Recipe and Product have extra required fields (for example a date and location, a provider, an address, an image or offers) that you can add with the `ratingpress_schema` filter. Google also does not show stars for self-serving reviews of a Local Business or Organization. You can verify your pages with the Google Rich Results Test.

= Does it work with page caching? =

Yes. Votes are sent through the REST API, and the voted state is remembered in the browser, so cached pages work correctly. Updated averages appear once the cache of the page is refreshed.

= Does it work behind Cloudflare or a reverse proxy? =

By default the IP address is read from `REMOTE_ADDR`. Use the `ratingpress_client_ip` filter to read it from a header set by your trusted proxy.

= Can I change the number of stars later? =

Yes. Votes are stored on a normalized scale, so existing ratings are rescaled automatically.

= How do I remove all data? =

Enable **Delete data on uninstall** in the Tools tab before deleting the plugin.

== Privacy ==

When a visitor rates content, Karina Star Ratings stores the rating, the date, the user ID for logged-in users and a salted one-way hash of the IP address. To remember voted content, a cookie and a browser local storage entry (both named `ratingpress_voted`) containing only the IDs of the rated posts may be stored. No data is sent to external services. Suggested privacy policy text is added to the WordPress privacy policy guide.

== Screenshots ==

1. Rating widget on a post.
2. Settings with live preview.
3. Dashboard with statistics.
4. Elementor widget controls.
5. Post editor panel.

== Changelog ==

= 1.0.1 =
* Fix: the "Schema type" select in the post editor panel no longer overflows the block editor sidebar.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
Fixes the "Schema type" select overflowing the block editor sidebar.

= 1.0.0 =
Initial release.
