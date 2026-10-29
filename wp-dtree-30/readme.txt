=== WP-dTree ===
Contributors: ulfben
Donate link: http://www.amazon.com/gp/registry/wishlist/2QB6SQ5XX2U0N/105-3209188-5640446?reveal=unpurchased&filter=all&sort=priority&layout=standard&x=11&y=10
Tags: archive, navigation, category, tree, sidebar
Requires at least: 6.0.1
Tested up to: 7.1
Stable tag: 4.5.5
Requires PHP: 7.4
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Legacy collapsible navigation trees for WordPress. For a modern alternative with native blocks, see Content Trees.

== Description ==

= Looking for a modern alternative? =

WP-dTree has a modern successor: [Content Trees](https://wordpress.org/plugins/content-trees/).

Content Trees offers native WordPress blocks with live previews, keyboard and screen-reader support, and on-demand post loading. It can run alongside WP-dTree, allowing you to replace your navigation trees gradually.

Existing widgets and settings are not migrated automatically, and some legacy features are not yet supported. Content Trees requires WordPress 6.3 or later; see its listing for current features and requirements.

WP-dTree remains supported for security and compatibility fixes. New feature development is focused on Content Trees.

= WP-dTree features =

This plugin provides collapsible navigation trees for archives, categories, pages, links, custom taxonomies and menus. Use the widgets in a widget area or the template functions documented below. Archive trees can group posts by year and month.

Requires WordPress 6.0.1 or later and PHP 7.4 or later.

== Frequently Asked Questions ==

= How are trees cached? =
Each tree is cached in the database. Changes to posts and other relevant content invalidate the cache; the next request rebuilds the tree. After upgrading, clear any separate page cache or CDN cache so visitors receive the updated output and assets.

= How do archive category filters work? =
Include categories shows posts assigned to any listed category ID. Exclude categories hides posts assigned to any listed category, even if they also belong to another category. If both fields are filled in, Include categories takes precedence. Only the listed categories are matched; list child category IDs separately. Matching posts appear and count once, regardless of how many categories they belong to. Counts include all matching posts before the monthly display limit is applied.

= Where do posts with several categories or terms appear? =
With Allow duplicate entries enabled, posts can appear under every matching visible term. With it disabled, each post belongs to its first matching visible term in the configured term sort order. The per-branch post limit is then applied; a post hidden by that limit is not moved to another branch. Equal post sort values use post ID as a stable tie-breaker. Counts and Show more links still refer to the full term archive, which can include posts shown elsewhere in the tree or hidden by widget settings.

= How do I style the selected item? =
Enable "Highlight selection" in the widget. The selected link receives the CSS class `.nodeSel`; the supplied stylesheet underlines it. You can customize `.dtree a.nodeSel` in your theme's CSS. If you disable the plugin stylesheet in Settings -> WP-dTree, supply your own styles.

== Installation ==

1. Back up your site. Install the ZIP using Plugins -> Add New -> Upload Plugin, or upload the `wp-dtree-30` folder to `/wp-content/plugins/` using FTP.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the 'WP-dTree' under 'Settings' to adjust your preferences
1. Go to Appearance -> Widgets and add the widgets to a widget area supported by your theme.
1. Configure the active Widget to your liking
1. Customize the presentation using your theme's CSS. See the FAQ for selection highlighting.

= For developers =

WP-dTree exposes the following [template tag functions](http://codex.wordpress.org/Template_Tags):

* `wpdt_list_archives();`			
* `wpdt_list_categories();`
* `wpdt_list_taxonomies();`
* `wpdt_list_menu();`
* `wpdt_list_pages();`
* `wpdt_list_links();`
* `wpdt_list_bookmarks(); //alias for wpdt_list_links`

They function a lot like WordPress own wp_list_* functions:

* They take an (optional) [query-string or associative array](http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters) with arguments
* They print by default, but if passed 'echo=0' they return the string of markup
* Most of them use the WordPress namesake for generating noscript-content

**Here's an example:**

    <div class="dtree">
        <?php
        if ( function_exists( 'wpdt_list_archives' ) ) {
            wpdt_list_archives( 'type=yearly&useicons=1' );
        }
        ?>
    </div>

I've tried to keep the same argument-lists as the WordPress "equivalents" but there are some discrepancies: style- and markup related arguments are not applicable to WP-dTree (but gets passed through for the noscript content).
There is also some inconsistency within WordPress, like some methods takes `sortby` while others takes `sort_column`. I've tried for WP-dTree to accept both but I'm sure I've missed a bunch of these cases.

So, to find out what arguments are definitely available grab the default `$args`:

* `wpdt_get_archives_defaults();` 
* `wpdt_get_categories_defaults();` 
* `wpdt_get_taxonomies_defaults()`
* `wpdt_get_pages_defaults();`
* `wpdt_get_links_defaults();`
* `wpdt_get_menu_defaults();`

They all return associative arrays with all arguments defaulted.

== Upgrade Notice ==

= 4.5.5 =
Introduces Content Trees, WP-dTree's modern successor, with guidance for gradually replacing your trees. WP-dTree continues to receive security and compatibility fixes.

= 4.5.4 =
Improves menu updates and remembered tree state, and avoids selecting unrelated items. Requires WordPress 6.0.1 or later. Clear any separate page cache after upgrading.

= 4.5.3 =
Fixes missing or duplicate posts in category and custom taxonomy trees, and PHP warnings from incomplete widget settings. Clear any separate page cache after upgrading.

= 4.5.2 =
Fixes archive category filtering, duplicate entries and counts, and refreshes cached trees after category and custom taxonomy changes. Clear any separate page cache after upgrading.

= 4.5.1 =
Fixes which posts appear when limiting posts per month and reduces server work when building trees. Clear any separate page cache after upgrading.

= 4.5.0 =
Security update for stored/reflected XSS and settings CSRF. Requires WordPress 6.0 and PHP 7.4. Tree caches rebuild automatically; clear any separate page cache after upgrading.

== Changelog ==

= 4.5.5 =
* Introduce Content Trees, the modern successor to WP-dTree, with migration guidance and a permanently dismissible administrator notice.
* Clarify that WP-dTree continues to receive security and compatibility fixes.

= 4.5.4 =
* Reduce server work when building large menu trees.
* Require WordPress 6.0.1 or later to avoid the term-cache bug in WordPress 6.0.
* Avoid opening or highlighting an unrelated item when the requested item is absent from the tree.
* Prevent similarly named cookies from affecting remembered tree state.
* Fix newly added menu items sometimes missing from cached trees.
* Prevent tree controls from overwriting variables used by other scripts on the page.

= 4.5.3 =
* Fix PHP warnings and missing trees when widget settings are empty or incomplete.
* Respect disabled duplicate entries in category and custom taxonomy trees, including when limiting posts per branch.
* Keep posts under their first matching visible term and avoid missing or hidden-parent entries.
* Fix post listing on databases enforcing strict grouping rules.
* Fix custom taxonomy Show more titles and an error when using Show more with Advanced Post Types Order.

= 4.5.2 =
* Fix duplicate archive entries and inflated counts when filtering by several categories.
* Excluding an archive category now also hides posts that belong to other categories.
* Keep cached trees up to date when categories or custom taxonomy terms are edited, assigned or removed.

= 4.5.1 =
* Archive posts are now sorted before the monthly limit is applied.
* Keep posts with matching dates or other sort values in a consistent order.
* Reduce server work when building page, category and custom taxonomy trees.

= 4.5.0 =
* Fix stored and reflected cross-site scripting and protect settings changes against CSRF (CVE-2022-47423, CVE-2023-41662 and CVE-2023-41667).
* Harden tree output, query parameters and caching; rebuild old tree caches after upgrading.
* Update PHP and WordPress compatibility and preserve returned output from the archive template function.
* Respect unchecked widget options, including icons and lines, when settings contain explicit false values.
* Correct the selected-link underline CSS.
* Include posts published exactly at the start of a month in archive trees.

= 4.4.5 =
* Fix a translation regression.

= Changes in v4.4.4 (2019-03-24) =
* Updated [for PHP 7](https://wordpress.org/support/topic/support-for-php-704/?replies=2)
* [Fixed https support](https://wordpress.org/support/topic/links-prepending-with-name-of-site/?replies=3) for links in the Links-widget
* Added support to [always include parent page in hierarchy](https://wordpress.org/support/topic/include-parent-page-in-tree-hierarchy/?replies=7) when selecting child ID's.

= Changes in v4.4.3.1 (2015-03-20) =
* Added support for category-specific archives
* Added support to use description instead of name for taxonomy trees.
* Fixed the sort ordering of archives
* Fixed potential "duplicate entry"-mysql error
* Removed forgotten debug-call.

= Changes in v4.4.2 (2015-01-03) =
* Better sorting options for posts
* Archives now support Custom Post Types
* Support for [Post Types Order](https://wordpress.org/support/topic/wp-dtree-update?replies=1#post-4583198) (thank you, sydcode)
* Minor bug fixes

= Changes in v4.4 (2014-12-22) =
* Added support for WordPress' Menus.
* Added multisite support. (thanks, John Pariseau)
* Added option to set navigation root to the current page or category. 
* Fixed broken l10n.
* Fixed a XSS vulnerability. (thanks, Patrick Riggs)
* Replaced all deprecated functions.
* Fixed tons of PHP warnings and notices.

= Changes in v4.3.1 (2012-01-11) =
* Fixed fallbacks for uninitialized settings: never print [wierd output](http://wordpress.org/support/topic/plugin-wp-dtree-works-but-has-bugs?replies=3).

= Changes in v4.3 (2012-01-01) =
* Added: support and widget for custom taxonomies. (needs testing, for sure...)
* Added: configurable open/close links for each tree
* Added: [configurable open/close separator for each tree](http://wordpress.org/support/topic/plugin-wp-dtree-pipe-character-between-open-all-and-close-all?replies=2)
* Fixed: various PHP [errors](http://wordpress.org/support/topic/plugin-wp-dtree-stripos-function-error-followed-by-crash-of-site?replies=1#post-2393280) and warnings.
* [Fixed: feed links being wrong](http://wordpress.org/support/topic/rss-problem-36?replies=2#post-2221275)
* Fixed: initialization routines
* Fixed: excluding categories from the archive tree
* Fixed: excluding posts from category tree
* Fixed: open/close link separator behavior (only show when both open/close is shown)
* Fixed: [link children are sorted correctly](http://wordpress.org/support/topic/plugin-wp-dtree-sorting-links-by-id-not-working?replies=1)
* Fixed: Open to selection supports pagination
* Fixed: [Open to selection supports categories again](http://wordpress.org/support/topic/plugin-wp-dtree-how-to-force-open-wdtree?replies=3#post-2523443)

= Changes in v4.2 (2010-10-29) =
* Fixed the uninstallation procedure.

= Changes in v4.1 (2010-10-23) =
* Improved installation to fix [database error for some users](http://tinyurl.com/37352en)
* Added: ["limit posts" for categories and archives](http://wordpress.org/support/topic/plugin-wp-dtree-limit-posts-under-categories?replies=2)
* Added: error catching - dtree can't blow up you other scripts
* Building archives is MUCH faster (2 queries total, from 1 per month)

= Changes in v4.0 (2010-10-17) =
* Completely recoded from the ground up for a much needed code overhaul.
*    **All previous settings will be lost!** Write them down before upgrading.
* Added: support for multiple tree instances
* Added: support for per-tree configurations 
* Added: [template tags](http://wordpress.org/extend/plugins/wp-dtree-30/other_notes/) for theme developers
* Added: (optional) noscript for JS-disabled visitors
* Added: uses category descriptions for link titles
* Added: translation support
* Added: optional JS-escape (XHTML, HTML or none) to ease validation
* Added: caching is optional (per instance, to boot)
* Fixed: should properly encode quotes and HTML-entities
* Replaced Scriptacolous with jQuery
* Made truncation optional
* Removed support for WP <2.3
* Removed all CSS-options from admin area
* [Removed all non-essential CSS-rules](http://wordpress.org/extend/plugins/wp-dtree-30/faq/)
* Only load jQuery if animation is on
* Minified JS and CSS (9KB vs. 16KB!)
* Cache is created on site visit (faster admin, less server load)

**Known issues in 4.0:** 

* This is a true .0 release - please explore and play with all the settings. [Let me know](http://wordpress.org/tags/wp-dtree-30) when something breaks (provide links!).
* Only tested in Chrome
* `opentoselection` doesn't handle paging

= Changes in v3.5 (2008-11-26) =

* New option: "shut down unused trees" (performance!)
* New option: "force open to"
* New option: per-tree truncation setting
* New option: custom sort order for archives
* New option: custom sort order for posts in categories
* New option: exclude posts from category tree
* New option: more CSS options avaliable from the admin
* Added: widget preview in the admin area
* Added: link target attributes in link tree
* Added: path defines to support non-standard WP-installations
* Added: uninstall.php for nice WP 2.7 plugin cleanup.
* Fixed: include sub-categories when counting posts
* Fixed: "close same level" 
* Fixed: Quotes "" in titles breaks alt-texts
* Fixed: Nestled cats get excluded if parent is empty
* Fixed: RSS-icons don't show in IE
* Fixed: Unwanted spacing in IE
* Misc: improved admin screen feng-shui.
* Misc: Moved config screen to "settings"-section of admin
* Misc: CSS should be a bit more robust now

= Changes in v3.4.2 (2008-10-19) =
* Bug: incorrect WP version detection. ([thanks: StMD](http://wordpress.org/support/topic/211402))

= Changes in v3.4.1 (2008-07-20) =
* Validates: both CSS and XHTML 1.0 Transitional ([thanks: ar-jar](http://wordpress.org/support/topic/189643))

= Changes in v3.4 (2008-07-12) =
* Added support for link trees. (needs testing!)
* Fixed breakage in WP 2.5, 2.6
* Fixed invalid XHTML output. ([props: jberghem](http://wordpress.org/support/topic/150888))
* Fixed a CSS-issue. ([props: wenzlerm](http://wordpress.org/support/topic/186314))
* Renamed the dTree script to avoid collisions with plugins using an unmodified version.

= Changes in v3.3.2 (2007-11-26) =
* Fixed bug with excluding multiple categories.

= Changes in v3.3.1 (2007-11-02) =
* Removed redundant `li`-tags from widgets. (props: Alexey Zamulla) 
* Support for non-ascii characters. ([props: michuw](http://wordpress.org/support/topic/141554))
* Properly encoded ampersands (&) in javascript URLs.

= Changes in v3.3 (2007-10-26) =
* Optimized the dtree script, up to **40% less data** required to feed the script. Using dTree now generates less markup than normal HTML.
* New option: Show RSS icon for archives
* New option: Show post count for archives
* Fix: Open to requested node
* Fix: images URL not working on some servers ([props: Zarquod](http://wordpress.org/support/topic/136547))
* Fix: somewhat more IE compatible...

*Known issues:* RSS icons wont show **in IE** if `post count` is on.

= Changes in v3.2 (2007-10-15) =
* Support for WP's bundled scriptacolous library! (turn effects on in the WP-dTree options page)
* New cache structure reduces cache size with ~33% compared to previous implementations.	 
* New option: Show RSS icon for categories
* New option: Show post count for categories
* New option: Effect duration

*Regressions:* `open to selection` is broken again. It'll be back in the next version, but if it's vital for you, stay with 3.1

= Changes in v3.1: (2007-10-06) =
* Updated to comply with WordPress 2.3's new taxonomy tables for categories.
* Widgetized! You no longer need to edit your sidebar manually.
* Fixed "Open To Selection"-option.
		
= Changes in v3.0: (2007-08-17) =
* Forked from the dead [WP-dTree 2.0](http://www.silpstream.com/blog/wp-dtree/)
* Added caching. The plugin creates the trees (only) when blog content changes, instead of creating them on every visit. Displaying the front page on the dev site went from 411 queries to 18. :)
	
== Frequently Asked Questions ==

= WP-dTree looks horrible on my blog and I hate you for it! = 
WP-dTree 4.0 has almost no styles of its own - it inherits from your theme. To help you apply your own styling I've included a template CSS-file. Open `wp-dtree.css` and copy all the selectors into your theme's stylesheet. 
Now disable the plugins default CSS (from the Settings-panel) and hack away at your own file to make it pretty.

Remember - do not edit `wp-dtree.css`, as this will be replaced on every update of the plugin.

= Can I help you in any way? =
Absolutely! If value my plugins, please consider [sending me a book or two](http://www.amazon.com/gp/registry/wishlist/2QB6SQ5XX2U0N/105-3209188-5640446?reveal=unpurchased&filter=all&sort=priority&layout=standard&x). (used are fine!) 

Or perhaps some [games on Steam](http://steamcommunity.com/id/ulfben/wishlist). :)

= Why is there no "Show more"-link for Archives? =
First: a simple workaround is to use 'Folders are links' and 'Show post count'. This way a visitor can easily see that a folder has more content than is on display, and clicking the folder name will bring her to it.

The reason there's no "Show more"-link for Archives is this: [limit posts in categories](http://wordpress.org/support/topic/plugin-wp-dtree-limit-posts-under-categories?replies=2) was a *paid* request and I added that feature to archives too because it was reasonably simple and actually helped me optimize the code slightly. The "show more"-link was a simple addon for the Category generation code, but not so for Archives - it simply requires a lot of data to be available where it currently is **not**. So until someone feels strongly enough to pay me for the inconvenience, I won't bother bloating the code for this rather narrow feature.

= Can I change the images used by WP-dTree? =
The images are all stored in the 'wp-dtree/dtree-img/' directory. You can change them if you like. Just remember to keep the names the same or they won't appear.

== Screenshots ==

1. The category tree over at game.hgo.se
2. The archive widget configuration screen
3. Archives, pages and categories (with post count and RSS-icons enabled).

== Additional Info ==

The original 'WP-dTree' was created by [Christopher Hwang](http://www.silpstream.com/blog/) in ~2005. By 2007 Mr. Hwang seemed to have dropped of the internet for good so [Ulf Benjaminsson](http://www.ulfbenjaminsson.com/) forked the plugin in and named it 'WP-dTree 3.0' (note to self: having a release number in the title was *stupid*).

The fork was focused on performance improvements - mainly caching - but soon expanded to add a lot of new features and modernizations; compatibility with WP 2.7 through WP 3.3, widgets, out-of-the-box Scriptaculous support, link trees, feed icons and more. 

For version 4.0 the entire plugin has been rewritten from scratch by Ulf, bringing it in line with the much matured WP 3.x API.

*WP-dTree (3.0 and up) is Copyright (C) 2007-2021 Ulf Benjaminsson (email: hello at my full name dot com)

*WP-dTree (3.x and lower) Copyright (C) 2006 Christopher Hwang (email: chris at silpstream dot com).

*[dTree](http://www.destroydrop.com/javascripts/tree/)-JavaScript is Copyright (c) 2002-2003 Geir Landrö

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
