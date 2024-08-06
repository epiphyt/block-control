# Block Control

Block Control allows you to take control of all the blocks on your website. Tailor a unique experience for your visitors.

Have you ever used WordPress’ new block editor Gutenberg and wished for a way to influence, when and to whom blocks are shown? We’ve been in this situation, that’s why we came up with _Block Control_. This nifty little plugin allows you to control, whether a block should be displayed under certain circumstances or not. And that’s of course true for both WordPress’ default blocks and blocks added by third-party plugins.

You can hide blocks based on:
* Device types (desktop, mobile, screen reader)
* Login status
* Date (start and end date)
* User roles
* Page types
* Posts of the current post type

## Requirements

PHP: 5.6<br>
WordPress: 6.2

## Installation

1. Upload the plugin files to the `/wp-content/plugins/block-control` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Now you can use the “Visibility” panel, _Block Control_ adds to every block withing the Gutenberg editor.


## Frequently Asked Questions

### How do I use Block Control?

After you install and activate _Block Control_, you will find a new panel “Visibility” added to every blocks right hand side sidebar. Open the panel to choose a condition for the display of a given block.

Conditional blocks configured this way will only be displayed under certain circumstances chosen by you. Please note, these conditions will only take effect in the front end of your site, not inside the editor itself.

### Does Block Control work with page caching plugins?

As Block Control removes content completely from the source code and not just hides it via CSS, it is mostly incompatible to any caching plugin because it may generate different HTML for every user.

### How to disable post type X from showing up?

Since version 1.1.0 you can hide blocks based on post type. Since you maybe have post types that don't make sense in this context, you can use the filter `block_control_ignored_post_types` to remove them.

E.g. if your post type slug is called `my_post_type`, you can use it like this:

```php
function my_filter_block_control_post_types( $post_types ) {
	unset( $post_types['my_post_type'];
	
	return $post_types;
}

add_filter( 'block_control_ignored_post_types', 'my_filter_block_control_post_types' );
```

### Who are you folks?

We are [Epiph.yt](https://epiph.yt/), your friendly neighborhood WordPress plugin shop from southern Germany.

## License

Block Control is free software, and is released under the terms of the GNU General Public License version 2 or (at your option) any later version. See [LICENSE.md](LICENSE.md) for complete license.

## How can I report security bugs?

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/block-control)
