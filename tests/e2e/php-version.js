/**
 * Resolution of the PHP version WordPress Playground should boot with.
 *
 * In CI, the version is provided by the workflow matrix via `PLAYGROUND_PHP`.
 * Locally, the version of the active `php` binary is used, so the tests run
 * against the same PHP the developer is working with.
 */
const { execFileSync } = require( 'node:child_process' );

/**
 * PHP versions WordPress Playground provides a runtime for.
 *
 * Keep in sync with `npx wp-playground-cli server --help`.
 *
 * @type {string[]}
 */
const SUPPORTED_VERSIONS = [ '8.0', '8.1', '8.2', '8.3', '8.4', '8.5' ];

/**
 * PHP version to fall back to if no supported version can be determined.
 *
 * @type {string}
 */
const FALLBACK_VERSION = '8.3';

/**
 * Get the version of the locally installed PHP binary.
 *
 * @return {string|null} The version as `major.minor`, or null if unavailable
 */
const getLocalPhpVersion = () => {
	try {
		return execFileSync(
			'php',
			[ '-r', 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' ],
			{ encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'ignore' ] }
		).trim();
	} catch {
		return null;
	}
};

/**
 * Get the PHP version WordPress Playground should use.
 *
 * @return {string} The PHP version
 */
const getPhpVersion = () => {
	const requested = process.env.PLAYGROUND_PHP;

	if ( requested ) {
		if ( ! SUPPORTED_VERSIONS.includes( requested ) ) {
			throw new Error(
				`PLAYGROUND_PHP is set to '${ requested }', which WordPress Playground ` +
					`does not provide. Supported: ${ SUPPORTED_VERSIONS.join( ', ' ) }.`
			);
		}

		return requested;
	}

	const local = getLocalPhpVersion();

	if ( local && SUPPORTED_VERSIONS.includes( local ) ) {
		return local;
	}

	// eslint-disable-next-line no-console
	console.warn(
		local
			? `Local PHP ${ local } is not supported by WordPress Playground, ` +
					`falling back to PHP ${ FALLBACK_VERSION }.`
			: `No local PHP binary found, falling back to PHP ${ FALLBACK_VERSION }.`
	);

	return FALLBACK_VERSION;
};

module.exports = { FALLBACK_VERSION, SUPPORTED_VERSIONS, getPhpVersion };
