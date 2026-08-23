/**
 * Device based visibility on the frontend.
 *
 * Mobile Detect works on the user agent, not on the viewport size, so these
 * tests use dedicated browser contexts instead of resizing the window.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const {
	ANDROID_TABLET_UA,
	DESKTOP_UA,
	IPHONE_UA,
	anonymousContext,
	getSource,
	paragraphMarkup,
} = require( '../utils' );

const SECRET = 'Block Control device probe';

/**
 * Get the HTML source of a URL as seen by a specific device.
 *
 * @param	{import('@playwright/test').Browser}	browser The browser instance
 * @param	{string}	userAgent The user agent to send
 * @param	{string}	url The URL to request
 * @return	{Promise<string>} The HTML source
 */
const getSourceAs = async ( browser, userAgent, url ) => {
	const { context, page } = await anonymousContext( browser, { userAgent } );
	const html = await getSource( page, url );

	await context.close();

	return html;
};

test.describe( 'Frontend device visibility', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'hideMobile hides the block on smartphones only', async ( {
		browser,
		requestUtils,
	} ) => {
		const post = await requestUtils.createPost( {
			content: paragraphMarkup( { hideMobile: true }, SECRET ),
			status: 'publish',
			title: 'Hide on mobile',
		} );

		expect( await getSourceAs( browser, IPHONE_UA, post.link ) )
			.not.toContain( SECRET );
		expect( await getSourceAs( browser, DESKTOP_UA, post.link ) )
			.toContain( SECRET );
		// tablets are not treated as mobile
		expect( await getSourceAs( browser, ANDROID_TABLET_UA, post.link ) )
			.toContain( SECRET );
	} );

	test( 'hideDesktop hides the block on desktops and tablets', async ( {
		browser,
		requestUtils,
	} ) => {
		const post = await requestUtils.createPost( {
			content: paragraphMarkup( { hideDesktop: true }, SECRET ),
			status: 'publish',
			title: 'Hide on desktop',
		} );

		expect( await getSourceAs( browser, DESKTOP_UA, post.link ) )
			.not.toContain( SECRET );
		// tablets count as desktop
		expect( await getSourceAs( browser, ANDROID_TABLET_UA, post.link ) )
			.not.toContain( SECRET );
		expect( await getSourceAs( browser, IPHONE_UA, post.link ) )
			.toContain( SECRET );
	} );
} );
