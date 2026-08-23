/**
 * The WordPress Playground blueprint used by the end-to-end tests.
 *
 * The blueprint is generated instead of being a static file, because a
 * blueprint overrides the `--php` and `--wp` flags of the Playground CLI: a
 * blueprint without `preferredVersions` silently boots the newest available
 * PHP version, which would make every leg of the CI matrix test the same
 * version.
 */
const { mkdirSync, writeFileSync } = require( 'node:fs' );
const path = require( 'node:path' );

/**
 * Get the blueprint for a PHP and WordPress version.
 *
 * @param	{string}	phpVersion The PHP version to boot
 * @param	{string}	wpVersion The WordPress version to boot
 * @return	{object} The blueprint
 */
const getBlueprint = ( phpVersion, wpVersion ) => ( {
	$schema: 'https://playground.wordpress.net/blueprint-schema.json',
	preferredVersions: {
		php: phpVersion,
		wp: wpVersion,
	},
	steps: [
		{
			step: 'defineWpConfigConsts',
			consts: {
				SCRIPT_DEBUG: true,
				WP_DEBUG: true,
				WP_DEBUG_DISPLAY: false,
				WP_DEBUG_LOG: true,
			},
		},
		{
			// the date conditions are evaluated in the site timezone
			step: 'setSiteOptions',
			options: {
				blogname: 'Block Control E2E',
				timezone_string: 'UTC',
			},
		},
		{
			step: 'activatePlugin',
			pluginPath: 'block-control/block-control.php',
		},
	],
} );

/**
 * Write the blueprint to disk and get its path.
 *
 * @param	{string}	phpVersion The PHP version to boot
 * @param	{string}	wpVersion The WordPress version to boot
 * @param	{string}	directory The directory to write the blueprint to
 * @return	{string} The path of the written blueprint
 */
const writeBlueprint = ( phpVersion, wpVersion, directory ) => {
	const file = path.join( directory, 'blueprint.json' );

	mkdirSync( directory, { recursive: true } );
	writeFileSync(
		file,
		JSON.stringify( getBlueprint( phpVersion, wpVersion ), null, '\t' )
	);

	return file;
};

module.exports = { getBlueprint, writeBlueprint };
