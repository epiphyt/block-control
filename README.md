=== Block Control ===
Contributors: epiphyt, kittmedia, krafit
Tags: gutenberg, block, conditional
Requires at least: 5.2
Tested up to: 5.2
Requires PHP: 5.6
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block Control allows you to take control of all the blocks on your website. Tailor a unique experience for your visitors.

== Description ==

Have you ever used WordPress’ new block editor Gutenberg and wished for a way to influence, when and to whom blocks are shown? We’ve been in this situation, that’s why we came up with _Block Control_. This nifty little plugin allows you to control, whether a block should be displayed under certain circumstances or not. And that’s of course true for both WordPress’ default blocks and blocks added by third-party plugins.

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

= Who are you folks? =

We are [Epiph.yt](https://epiph.yt/), your friendly neighborhood WordPress plugin shop from southern Germany.

== Changelog ==

= 1.0.1 =
* We fixed the internationalization for all strings

= 1.0.0 =
* Initial release

== Upgrade Notice ==
