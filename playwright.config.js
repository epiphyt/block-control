/**
 * Playwright configuration for the end-to-end tests.
 *
 * WordPress is provided by the WordPress Playground CLI, which boots a real
 * WordPress on PHP-WASM without Docker or a database. The PHP version comes
 * from the CI matrix via `PLAYGROUND_PHP` and defaults to the locally
 * installed PHP version.
 */
const path = require( 'node:path' );
const { defineConfig, devices } = require( '@playwright/test' );

const { writeBlueprint } = require( './tests/e2e/blueprint' );
const {
	SUPPORTED_VERSIONS,
	getPhpVersion,
} = require( './tests/e2e/php-version' );

const phpVersion = getPhpVersion();
const wpVersion = process.env.PLAYGROUND_WP || 'latest';
// every PHP version gets its own port, so that a server left running from a
// previous run is never reused for a different PHP version
const port = Number(
	process.env.PLAYGROUND_PORT ||
		9400 + SUPPORTED_VERSIONS.indexOf( phpVersion )
);
// the IP is used instead of `localhost` to match the default site URL of the
// WordPress Playground CLI, as a mismatch breaks cookies and REST API nonces
const baseUrl = `http://127.0.0.1:${ port }`;

// @wordpress/e2e-test-utils-playwright reads its configuration from the
// environment, and the WordPress Playground defaults match its own defaults
// of `admin` and `password`
process.env.WP_BASE_URL = baseUrl;
process.env.WP_ARTIFACTS_PATH ??= path.join( process.cwd(), 'artifacts' );
process.env.STORAGE_STATE_PATH ??= path.join(
	process.env.WP_ARTIFACTS_PATH,
	'storage-states/admin.json'
);

const blueprint = writeBlueprint(
	phpVersion,
	wpVersion,
	process.env.WP_ARTIFACTS_PATH
);

module.exports = defineConfig( {
	expect: {
		timeout: 30_000,
	},
	forbidOnly: !! process.env.CI,
	fullyParallel: false,
	globalSetup: require.resolve(
		'@wordpress/scripts/config/playwright/global-setup.js'
	),
	outputDir: path.join( process.env.WP_ARTIFACTS_PATH, 'test-results' ),
	reporter: process.env.CI
		? [ [ 'github' ], [ 'html', { open: 'never' } ] ]
		: [ [ 'list' ] ],
	// a single WordPress Playground instance is shared by every test
	retries: process.env.CI ? 1 : 0,
	testDir: './tests/e2e/specs',
	timeout: 120_000,
	use: {
		baseURL: baseUrl,
		contextOptions: {
			reducedMotion: 'reduce',
			strictSelectors: true,
		},
		locale: 'en-US',
		screenshot: 'only-on-failure',
		storageState: process.env.STORAGE_STATE_PATH,
		trace: 'retain-on-failure',
		video: 'on-first-retry',
		viewport: { width: 1280, height: 800 },
	},
	webServer: {
		command: [
			'npx wp-playground-cli server',
			`--port=${ port }`,
			`--php=${ phpVersion }`,
			`--wp=${ wpVersion }`,
			'--mount=.:/wordpress/wp-content/plugins/block-control',
			`--blueprint=${ blueprint }`,
			'--verbosity=quiet',
		].join( ' ' ),
		reuseExistingServer: ! process.env.CI,
		stderr: 'pipe',
		stdout: 'pipe',
		timeout: 300_000,
		url: baseUrl,
	},
	workers: 1,
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
