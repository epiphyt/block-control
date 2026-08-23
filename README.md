# Block Control

Block Control allows you to take control of all the blocks on your website. Tailor a unique experience for your visitors.

Have you ever used WordPress’ new block editor Gutenberg and wished for a way to influence, when and to whom blocks are shown? We’ve been in this situation, that’s why we came up with Block Control. This nifty little plugin allows you to control, whether a block should be displayed under certain circumstances or not. And that’s of course true for both WordPress’ default blocks and blocks added by third-party plugins.

You can hide blocks based on:
* Device types (desktop, mobile, screen reader)
* Viewports/CSS breakpoints
* Login status
* Date (start and end date)
* User roles
* Page types
* Feeds
* Posts of the current post type

Additionally, you can set an inline formatting to display certain text only for screen readers.

## Requirements

PHP: 8.0<br>
WordPress: 6.8

## Testing

### Manual e2e testing

End-to-end testing is done via each PHP version. If you want to test a specific version manually and access it interactively, you can run the following command:

```
node -e "require('./tests/e2e/blueprint').writeBlueprint('8.0','latest','artifacts/php-8.0')" && \
npx wp-playground-cli server --port=9400 \
  --mount=.:/wordpress/wp-content/plugins/block-control \
  --blueprint=artifacts/php-8.0/blueprint.json
```

Replace each `8.0` with your desired PHP version.

## License

Block Control is free software, and is released under the terms of the GNU General Public License version 2 or (at your option) any later version. See [LICENSE.md](LICENSE.md) for complete license.

## How can I report security bugs?

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/block-control)
