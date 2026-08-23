/**
 * Basic checks that run against every supported PHP version.
 *
 * These are the tests that earn their keep in the CI matrix: they catch a
 * plugin that fatals, warns or fails to register its blocks on one particular
 * PHP version.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const { anonymousContext, getSource } = require( '../utils' );
const { getPhpVersion } = require( '../php-version' );

const CRITICAL_ERROR = 'There has been a critical error on this website';

test.describe( 'Smoke', () => {
	test( 'WordPress runs on the requested PHP version', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( 'site-health.php', 'tab=debug' );

		const version = await page
			.locator( 'tr' )
			.filter( {
				has: page.locator( 'th', { hasText: /^PHP version$/ } ),
			} )
			.locator( 'td' )
			.innerText();

		// without this, a silent fallback would make every leg of the CI
		// matrix test the very same PHP version
		expect( version ).toMatch(
			new RegExp( `^${ getPhpVersion().replace( '.', '\\.' ) }\\.` )
		);
	} );

	test( 'the frontend renders without a critical error', async ( {
		browser,
	} ) => {
		const { context, page } = await anonymousContext( browser );
		const html = await getSource( page, '/' );

		expect( html ).not.toContain( CRITICAL_ERROR );

		await context.close();
	} );

	test( 'the plugin is active', async ( { requestUtils } ) => {
		const plugins = await requestUtils.rest( { path: '/wp/v2/plugins' } );
		const blockControl = plugins.find(
			( plugin ) => plugin.plugin === 'block-control/block-control'
		);

		expect( blockControl ).toBeDefined();
		expect( blockControl.status ).toBe( 'active' );
	} );

	test( 'the REST API is intact and exposes the viewports route', async ( {
		requestUtils,
	} ) => {
		const response = await requestUtils.rest( {
			path: '/block-control/v1/viewports',
		} );

		expect( response ).toHaveProperty( 'viewports' );
		expect( response ).toHaveProperty( 'presets' );
	} );

	test( 'the editor loads without a page error', async ( {
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: 'core/paragraph' } );

		expect( await admin.getPageError() ).toBeNull();
	} );

	test( 'the Visibility panel is registered', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Smoke test' },
		} );
		await editor.openDocumentSettingsSidebar();

		await expect(
			page
				.getByRole( 'region', { name: 'Editor settings' } )
				.getByRole( 'button', { name: 'Visibility' } )
		).toBeVisible();
	} );
} );
