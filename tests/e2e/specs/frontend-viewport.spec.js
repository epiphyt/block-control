/**
 * Viewport based visibility on the frontend.
 *
 * Unlike every other condition, this one works via CSS: the content stays in
 * the source and is only hidden visually, which keeps it compatible with page
 * caching.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const {
	anonymousContext,
	customViewportClass,
	getSource,
	paragraphMarkup,
} = require( '../utils' );

const SECRET = 'Block Control viewport probe';
const CUSTOM_CONDITION = '(min-width: 1200px)';

test.describe( 'Frontend viewport visibility', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'a preset adds its class and hides the block on small screens', async ( {
		browser,
		requestUtils,
	} ) => {
		const post = await requestUtils.createPost( {
			content: paragraphMarkup( { hideViewports: [ 'mobile' ] }, SECRET ),
			status: 'publish',
			title: 'Hide on mobile viewport',
		} );
		const { context, page } = await anonymousContext( browser );
		const html = await getSource( page, post.link );

		// the content stays in the source, it is only hidden visually
		expect( html ).toContain( SECRET );
		expect( html ).toContain( 'block-control-hidden-mobile' );

		const paragraph = page.getByText( SECRET );

		await expect( paragraph ).toHaveClass( /block-control-hidden-mobile/ );

		await page.setViewportSize( { width: 1280, height: 800 } );
		await expect( paragraph ).toBeVisible();

		await page.setViewportSize( { width: 375, height: 800 } );
		await expect( paragraph ).toBeHidden();

		await context.close();
	} );

	test( 'a custom media condition adds its hashed class', async ( {
		browser,
		requestUtils,
	} ) => {
		const className = customViewportClass( CUSTOM_CONDITION );
		const post = await requestUtils.createPost( {
			content: paragraphMarkup(
				{ hideViewports: [ CUSTOM_CONDITION ] },
				SECRET
			),
			status: 'publish',
			title: 'Hide on custom viewport',
		} );
		const { context, page } = await anonymousContext( browser );
		const html = await getSource( page, post.link );

		expect( html ).toContain( SECRET );
		expect( html ).toContain( className );

		const paragraph = page.getByText( SECRET );

		await page.setViewportSize( { width: 1280, height: 800 } );
		await expect( paragraph ).toBeHidden();

		await page.setViewportSize( { width: 900, height: 800 } );
		await expect( paragraph ).toBeVisible();

		await context.close();
	} );

	test( 'no viewport class is emitted when the block is already hidden', async ( {
		browser,
		requestUtils,
	} ) => {
		const post = await requestUtils.createPost( {
			content: paragraphMarkup(
				{ hideViewports: [ 'mobile' ], loginStatus: 'logged-in' },
				SECRET
			),
			status: 'publish',
			title: 'Hidden before the viewport is applied',
		} );
		const { context, page } = await anonymousContext( browser );
		const html = await getSource( page, post.link );

		expect( html ).not.toContain( SECRET );
		expect( html ).not.toContain( 'block-control-hidden-mobile' );

		await context.close();
	} );
} );
