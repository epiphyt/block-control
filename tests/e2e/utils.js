/**
 * Shared helpers for the end-to-end tests.
 */
const { createHash } = require( 'node:crypto' );

/**
 * User agent of a smartphone, which Mobile Detect identifies as mobile.
 *
 * @type {string}
 */
const IPHONE_UA =
	'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15' +
	' (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

/**
 * User agent of a tablet, which Mobile Detect identifies as mobile and tablet.
 *
 * @type {string}
 */
const ANDROID_TABLET_UA =
	'Mozilla/5.0 (Linux; Android 13; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko)' +
	' Chrome/120.0.0.0 Safari/537.36';

/**
 * User agent of a desktop computer.
 *
 * @type {string}
 */
const DESKTOP_UA =
	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko)' +
	' Chrome/120.0.0.0 Safari/537.36';

/**
 * Get the serialized markup of a paragraph block.
 *
 * @param	{object}	attributes The Block Control attributes to set
 * @param	{string}	text The text content of the paragraph
 * @return	{string} The serialized block markup
 */
const paragraphMarkup = ( attributes, text ) => {
	const attributeString = Object.keys( attributes ).length
		? ' ' + JSON.stringify( attributes )
		: '';

	return (
		`<!-- wp:paragraph${ attributeString } -->\n` +
		`<p>${ text }</p>\n` +
		'<!-- /wp:paragraph -->'
	);
};

/**
 * Get the class name Block Control generates for a custom media condition.
 *
 * Mirrors `Viewport::get_class_name()` in inc/class-viewport.php.
 *
 * @param	{string}	condition The media condition
 * @return	{string} The class name
 */
const customViewportClass = ( condition ) =>
	'block-control-hidden-' +
	createHash( 'md5' ).update( condition ).digest( 'hex' ).slice( 0, 10 );

/**
 * Open a page as an anonymous visitor.
 *
 * The default browser context is authenticated as administrator, so a fresh
 * context is required to test the output for logged out visitors and for
 * specific devices.
 *
 * @param	{import('@playwright/test').Browser}	browser The browser instance
 * @param	{object}	options Additional context options, like a user agent
 * @return	{Promise<{context: object, page: object}>} The context and its page
 */
const anonymousContext = async ( browser, options = {} ) => {
	const context = await browser.newContext( {
		storageState: undefined,
		...options,
	} );

	return { context, page: await context.newPage() };
};

/**
 * Open a page as a specific user.
 *
 * @param	{import('@playwright/test').Browser}	browser The browser instance
 * @param	{string}	username The user to log in as
 * @param	{string}	password The password of the user
 * @return	{Promise<{context: object, page: object}>} The context and its page
 */
const loginAs = async ( browser, username, password ) => {
	const { context, page } = await anonymousContext( browser );

	await page.goto( '/wp-login.php' );
	await page.getByLabel( 'Username or Email Address' ).fill( username );
	await page.getByLabel( 'Password', { exact: true } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click();
	await page.waitForURL( /wp-admin/ );

	return { context, page };
};

/**
 * Navigate to a URL and get its raw HTML source.
 *
 * Blocks hidden on the server are missing from the source entirely, so the
 * source is what these tests need to assert on.
 *
 * @param	{import('@playwright/test').Page}	page The page instance
 * @param	{string}	url The URL to navigate to
 * @return	{Promise<string>} The HTML source
 */
const getSource = async ( page, url ) => {
	const response = await page.goto( url );

	return response.text();
};

/**
 * Open the “Visibility” panel of the currently selected block.
 *
 * @param	{import('@playwright/test').Page}	page The page instance
 * @param	{import('@wordpress/e2e-test-utils-playwright').Editor}	editor The editor utils
 * @return	{Promise<import('@playwright/test').Locator>} The panel body
 */
const openVisibilityPanel = async ( page, editor ) => {
	await editor.openDocumentSettingsSidebar();

	const settings = page.getByRole( 'region', { name: 'Editor settings' } );
	const toggle = settings.getByRole( 'button', { name: 'Visibility' } );

	if ( ( await toggle.getAttribute( 'aria-expanded' ) ) === 'false' ) {
		await toggle.click();
	}

	return settings.locator( '.block-control-panel' );
};

module.exports = {
	ANDROID_TABLET_UA,
	DESKTOP_UA,
	IPHONE_UA,
	anonymousContext,
	customViewportClass,
	getSource,
	loginAs,
	openVisibilityPanel,
	paragraphMarkup,
};
