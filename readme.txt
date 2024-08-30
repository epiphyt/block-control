=== Block Control ===
Contributors: epiphyt, kittmedia, krafit
Tags: gutenberg, block, conditional, visibility, block editor
Requires at least: 6.2
Stable tag: 1.3.0
Tested up to: 6.6
Requires PHP: 5.6
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block Control allows you to take control of all the blocks on your website. Tailor a unique experience for your visitors.

== Description ==

Have you ever used WordPress’ new block editor Gutenberg and wished for a way to influence, when and to whom blocks are shown? We’ve been in this situation, that’s why we came up with _Block Control_. This nifty little plugin allows you to control, whether a block should be displayed under certain circumstances or not. And that’s of course true for both WordPress’ default blocks and blocks added by third-party plugins.

You can hide blocks based on:

* Device types (desktop, mobile, screen reader)
* Login status
* Date (start and end date)
* User roles
* Page types
* Posts of the current post type

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/block-control` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Now you can use the “Visibility” panel, _Block Control_ adds to every block withing the Gutenberg editor.


== Frequently Asked Questions ==

= How do I use Block Control? =

After you install and activate _Block Control_, you will find a new panel “Visibility” added to every blocks right hand side sidebar. Open the panel to choose a condition for the display of a given block.

Conditional blocks configured this way will only be displayed under certain circumstances chosen by you. Please note, these conditions will only take effect in the front end of your site, not inside the editor itself.

= Does Block Control work with page caching plugins? =

As Block Control removes content completely from the source code and not just hides it via CSS, it is mostly incompatible to any caching plugin because it may generate different HTML for every user.

= How to disable post type X from showing up? =

Since version 1.1.0 you can hide blocks based on post type. Since you maybe have post types that don't make sense in this context, you can use the filter `block_control_ignored_post_types` to remove them.

E.g. if your post type slug is called `my_post_type`, you can use it like this:

`
function my_filter_block_control_post_types( $post_types ) {
	unset( $post_types['my_post_type'];
	
	return $post_types;
}

add_filter( 'block_control_ignored_post_types', 'my_filter_block_control_post_types' );
`

= Who are you folks? =

We are [Epiph.yt](https://epiph.yt/), your friendly neighborhood WordPress plugin shop from southern Germany.

= Contributing =

You can contribute to the code on [GitHub](https://github.com/epiphyt/block-control).

= How can I report security bugs? =

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/block-control)

== Changelog ==

= 1.3.0 =
* We added the possibility to patterns to hide them by any post type available.
* We extended the list of ignored post types with font families and font faces.

= 1.2.0 =
* We added the option to disable blocks for screen readers. That means that the block is still visible, but will be ignored by screen readers entirely.

= 1.1.12 =
* We fixed all variants of hide by date setting to be properly checked.

= 1.1.11 =
* We fixed the hide by date setting if only one of the dates is used.

= 1.1.10 =
* We fixed the hide by date setting if both settings are in the future or both are in the past.
* We improved the settings for hide by date for better accessibility and functionality.

= 1.1.9 =
* We fixed a problem with some blocks no more saving the visibility settings.

= 1.1.8 =
* We fixed a problem with broken blocks after updating to version 1.1.7. The editor now doesn't contain any classes from _Block Control_ anymore.

= 1.1.7 =
* We fixed compatibility with dynamic blocks.

= 1.1.6 =
* We improved the ability to use the filter `blockControl.unsupportedBlocks` without needing to load the own JavaScript early.

= 1.1.5 =
* We added a filter `blockControl.unsupportedBlocks` to filter the list of unsupported blocks.
* We disabled _Block Control_ for the SimpleTOC block in order to display it properly in the backend.

= 1.1.4 =
* We disabled _Block Control_ for the Polylang language switcher in order to display it properly in the backend.

= 1.1.3 =
* We added full support for WordPress 6.1 (replaced a deprecated function).

= 1.1.2 =
* We fixed a potential error in blocks in the widget area.

= 1.1.1 =
* We fixed a dependency problem, which may result in preventing the settings panel to appear.

= 1.1.0 =
* Since the originally planned Pro version will never be completed, we decided to merge its code into the free version of Block Control.
* Hide blocks based on roles
* Hide blocks based on dates
* Hide blocks based on page types (`is_home`, `is_page`, etc.)
* Hide blocks based on posts of the current post type (useful especially for reusable blocks)

= 1.0.4 =
* We fixed a problem with assets loading in the backend

= 1.0.3 =
* We fixed a problem while checking for block attributes

= 1.0.2 =

* We fixed a problem that may load multiple editor scripts in the frontend even if they are not needed there
* We fixed a problem where Block Control options won’t be available for certain blocks

= 1.0.1 =
* We fixed the internationalization for all strings

= 1.0.0 =
* Initial release

== Upgrade Notice ==

== Screenshots ==

1. Block Control settings in the block's sidebar
