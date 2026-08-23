/**
 * Server-side visibility on the frontend.
 *
 * A block hidden by one of these conditions is removed from the output
 * entirely, so every assertion checks the HTML source rather than the
 * rendered visibility.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const {
	anonymousContext,
	getSource,
	loginAs,
	paragraphMarkup,
} = require( '../utils' );

const SECRET = 'Block Control visibility probe';

/**
 * Create a published post containing a single paragraph.
 *
 * @param	{object}	requestUtils The request utils
 * @param	{object}	attributes The Block Control attributes
 * @return	{Promise<object>} The created post
 */
const createPostWith = ( requestUtils, attributes ) =>
	requestUtils.createPost( {
		content: paragraphMarkup( attributes, SECRET ),
		status: 'publish',
		title: 'Visibility',
	} );

test.describe( 'Frontend visibility', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'loginStatus "logged-in" hides the block for logged out visitors', async ( {
		browser,
		page,
		requestUtils,
	} ) => {
		const post = await createPostWith( requestUtils, {
			loginStatus: 'logged-in',
		} );

		expect( await getSource( page, post.link ) ).toContain( SECRET );

		const { context, page: anonymous } = await anonymousContext( browser );

		expect( await getSource( anonymous, post.link ) ).not.toContain(
			SECRET
		);

		await context.close();
	} );

	test( 'loginStatus "logged-out" hides the block for logged in users', async ( {
		browser,
		page,
		requestUtils,
	} ) => {
		const post = await createPostWith( requestUtils, {
			loginStatus: 'logged-out',
		} );

		expect( await getSource( page, post.link ) ).not.toContain( SECRET );

		const { context, page: anonymous } = await anonymousContext( browser );

		expect( await getSource( anonymous, post.link ) ).toContain( SECRET );

		await context.close();
	} );

	test( 'hideRoles hides the block only for the selected role', async ( {
		browser,
		page,
		requestUtils,
	} ) => {
		await requestUtils.createUser( {
			username: 'block-control-editor',
			email: 'editor@block-control.test',
			password: 'block-control-editor-pw',
			roles: [ 'editor' ],
		} );

		// both roles are listed explicitly: a role that is missing from the
		// map is treated as hidden by hide_roles()
		const post = await createPostWith( requestUtils, {
			hideRoles: { administrator: true, editor: false },
		} );

		expect( await getSource( page, post.link ) ).not.toContain( SECRET );

		const { context, page: editorPage } = await loginAs(
			browser,
			'block-control-editor',
			'block-control-editor-pw'
		);

		expect( await getSource( editorPage, post.link ) ).toContain( SECRET );

		await context.close();
		await requestUtils.deleteAllUsers();
	} );

	test( 'hideByDate hides the block once the start date has passed', async ( {
		page,
		requestUtils,
	} ) => {
		const past = await createPostWith( requestUtils, {
			hideByDate: true,
			hideByDateStart: '2020-01-01T00:00:00',
		} );

		expect( await getSource( page, past.link ) ).not.toContain( SECRET );
	} );

	test( 'hideByDate keeps the block visible before the start date', async ( {
		page,
		requestUtils,
	} ) => {
		const future = await createPostWith( requestUtils, {
			hideByDate: true,
			hideByDateStart: '2099-01-01T00:00:00',
		} );

		expect( await getSource( page, future.link ) ).toContain( SECRET );
	} );

	test( 'hideConditionalTags hides the block on matching page types', async ( {
		page,
		requestUtils,
	} ) => {
		const post = await createPostWith( requestUtils, {
			hideConditionalTags: { is_single: true },
		} );

		expect( await getSource( page, post.link ) ).not.toContain( SECRET );
	} );

	test( 'hideScreenReader keeps the block but marks it aria-hidden once', async ( {
		page,
		requestUtils,
	} ) => {
		// the inner element makes sure the attribute is not added to every tag
		const post = await requestUtils.createPost( {
			content:
				'<!-- wp:paragraph {"hideScreenReader":true} -->\n' +
				`<p>${ SECRET } <strong>emphasis</strong></p>\n` +
				'<!-- /wp:paragraph -->',
			status: 'publish',
			title: 'Screen reader',
		} );

		expect( await getSource( page, post.link ) ).toContain( SECRET );

		const paragraph = page.locator( 'p', { hasText: SECRET } );

		await expect( paragraph ).toBeVisible();
		await expect( paragraph ).toHaveAttribute( 'aria-hidden', 'true' );

		// the attribute belongs on the outer element only
		const markup = await paragraph.evaluate(
			( element ) => element.outerHTML
		);

		expect( markup.match( /aria-hidden="true"/g ) ).toHaveLength( 1 );
	} );
} );
