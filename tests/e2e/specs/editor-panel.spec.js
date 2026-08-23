/**
 * The “Visibility” panel in the block inspector.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const { openVisibilityPanel } = require( '../utils' );

const ACTIVE_LABEL = 'Visibility settings apply to this block.';

test.describe( 'Visibility panel', () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Panel test' },
		} );
	} );

	test( 'contains every settings section', async ( { editor, page } ) => {
		const panel = await openVisibilityPanel( page, editor );

		for ( const legend of [
			'Hide device types',
			'Hide by viewport',
			'Hide for user roles',
			'Hide for specific page types',
			'Hide on numbered pages',
		] ) {
			await expect(
				panel.getByRole( 'group', { name: legend } )
			).toBeVisible();
		}

		await expect(
			panel.getByRole( 'radiogroup', { name: 'Hide by login status' } )
		).toBeVisible();
		await expect(
			panel.getByRole( 'checkbox', { name: 'Hide by date' } )
		).toBeVisible();
	} );

	test( 'checking a device option updates the block attribute', async ( {
		editor,
		page,
	} ) => {
		const panel = await openVisibilityPanel( page, editor );

		await panel
			.getByRole( 'checkbox', { name: 'Hide on smartphones' } )
			.check();

		await expect
			.poll( async () => ( await editor.getBlocks() )[ 0 ].attributes )
			.toMatchObject( { hideMobile: true } );
	} );

	test( 'the login status can be changed', async ( { editor, page } ) => {
		const panel = await openVisibilityPanel( page, editor );

		await panel
			.getByRole( 'radio', { name: 'Show for logged in users' } )
			.check();

		await expect
			.poll( async () => ( await editor.getBlocks() )[ 0 ].attributes )
			.toMatchObject( { loginStatus: 'logged-in' } );
	} );

	test( 'active settings are indicated in the canvas and the list view', async ( {
		editor,
		page,
	} ) => {
		const panel = await openVisibilityPanel( page, editor );
		const checkbox = panel.getByRole( 'checkbox', {
			name: 'Hide on smartphones',
		} );
		const block = editor.canvas.locator( '[data-type="core/paragraph"]' );

		await expect( block ).not.toHaveClass( /block-control-is-hidden/ );

		await checkbox.check();

		await expect( block ).toHaveClass( /block-control-is-hidden/ );
		await expect( panel.getByText( ACTIVE_LABEL ) ).toBeAttached();

		await page
			.getByRole( 'button', { name: 'Document Overview' } )
			.click();

		const listView = page.getByRole( 'treegrid', {
			name: 'Block navigation structure',
		} );

		await expect(
			listView.locator( '.block-control-list-view-indicator' )
		).toBeAttached();

		// removing the setting removes the indicators again
		await checkbox.uncheck();

		await expect( block ).not.toHaveClass( /block-control-is-hidden/ );
		await expect(
			listView.locator( '.block-control-list-view-indicator' )
		).toHaveCount( 0 );
	} );

	test( 'the date fields appear and the panel stays open', async ( {
		editor,
		page,
	} ) => {
		const panel = await openVisibilityPanel( page, editor );
		const toggle = page
			.getByRole( 'region', { name: 'Editor settings' } )
			.getByRole( 'button', { name: 'Visibility' } );

		await panel.getByRole( 'checkbox', { name: 'Hide by date' } ).check();

		await expect( panel.getByText( 'Hide date:' ) ).toBeVisible();
		await expect( panel.getByText( 'Display date:' ) ).toBeVisible();

		const setDate = panel.getByRole( 'button', { name: 'Set date' } );

		await expect( setDate ).toHaveCount( 2 );

		await setDate.first().click();

		// the picker is rendered in a popover outside of the panel
		await expect(
			page.locator( '.block-control-datetime-picker' )
		).toBeVisible();
		// the panel must not collapse while a date is being picked
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
	} );

	test( 'the page number field appears for specific pages', async ( {
		editor,
		page,
	} ) => {
		const panel = await openVisibilityPanel( page, editor );
		const numberedPages = panel.getByRole( 'group', {
			name: 'Hide on numbered pages',
		} );

		await numberedPages
			.getByRole( 'checkbox', { name: 'Specific page(s)' } )
			.check();

		await expect(
			panel.getByRole( 'combobox', { name: 'Page numbers' } )
		).toBeVisible();
	} );
} );
