/**
 * The viewport control and the site-wide synchronization of media queries.
 *
 * Media queries typed in the editor are stored site-wide on save, which makes
 * them available to every other block afterwards.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const { openVisibilityPanel } = require( '../utils' );

const CONDITION = '(min-width: 1234px)';

/**
 * Get the viewport control of the currently selected block.
 *
 * @param	{import('@playwright/test').Locator}	panel The Visibility panel
 * @return	{import('@playwright/test').Locator} The viewport control
 */
const getViewportControl = ( panel ) =>
	panel.locator( '.block-control__viewports' );

test.describe( 'Viewport control', () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Viewport test' },
		} );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'a preset can be selected and removed', async ( {
		editor,
		page,
	} ) => {
		const panel = await openVisibilityPanel( page, editor );
		const control = getViewportControl( panel );

		await control
			.getByRole( 'button', { name: 'Add media query' } )
			.click();

		const select = control.getByRole( 'combobox', {
			name: 'Media query 1',
		} );

		await expect( select ).toBeVisible();
		await select.selectOption( 'mobile' );

		await expect
			.poll( async () => ( await editor.getBlocks() )[ 0 ].attributes )
			.toMatchObject( { hideViewports: [ 'mobile' ] } );

		await control
			.getByRole( 'button', { name: 'Remove media query' } )
			.click();

		await expect
			.poll( async () => ( await editor.getBlocks() )[ 0 ].attributes )
			.toMatchObject( { hideViewports: [] } );
	} );

	test( 'an invalid custom media query is rejected', async ( {
		editor,
		page,
	} ) => {
		const panel = await openVisibilityPanel( page, editor );
		const control = getViewportControl( panel );

		await control
			.getByRole( 'button', { name: 'Add media query' } )
			.click();
		await control
			.getByRole( 'button', { name: 'Use a custom media query' } )
			.click();

		const input = control.getByRole( 'textbox', {
			name: 'Media query 1',
		} );

		await input.fill( 'nonsense' );

		await expect(
			control.getByText( 'This is not a valid media query.' )
		).toBeVisible();

		await input.press( 'Enter' );

		// an invalid condition must not end up as a block attribute
		await expect
			.poll( async () => ( await editor.getBlocks() )[ 0 ].attributes )
			.toMatchObject( { hideViewports: [] } );
	} );

	test( 'a custom media query is stored site-wide on save', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		const panel = await openVisibilityPanel( page, editor );
		const control = getViewportControl( panel );

		await control
			.getByRole( 'button', { name: 'Add media query' } )
			.click();
		await control
			.getByRole( 'button', { name: 'Use a custom media query' } )
			.click();
		await control
			.getByRole( 'textbox', { name: 'Media query 1' } )
			.fill( CONDITION );
		await control
			.getByRole( 'textbox', { name: 'Media query 1' } )
			.press( 'Enter' );

		await expect
			.poll( async () => ( await editor.getBlocks() )[ 0 ].attributes )
			.toMatchObject( { hideViewports: [ CONDITION ] } );

		const syncRequest = page.waitForRequest(
			( request ) =>
				request.url().includes( '/block-control/v1/viewports' ) &&
				request.method() === 'POST'
		);

		await editor.publishPost();
		await syncRequest;

		// the condition is now available site-wide
		await expect
			.poll( async () => {
				const response = await requestUtils.rest( {
					path: '/block-control/v1/viewports',
				} );

				return response.viewports;
			} )
			.toContain( CONDITION );

		// and is offered as an option on another post
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Second post' },
		} );

		const secondPanel = await openVisibilityPanel( page, editor );
		const secondControl = getViewportControl( secondPanel );

		await secondControl
			.getByRole( 'button', { name: 'Add media query' } )
			.click();

		await expect(
			secondControl
				.getByRole( 'combobox', { name: 'Media query 1' } )
				.getByRole( 'option', { name: CONDITION } )
		).toBeAttached();
	} );
} );
