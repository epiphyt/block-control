<?php
namespace epiphyt\Block_Control;

/**
 * Admin-related functionality
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Block_Control
 */
final class Admin {
	/**
	 * Initialize functionality.
	 */
	public static function init() {
		\add_filter( 'plugin_row_meta', [ self::class, 'render_plugin_documentation_link' ], 10, 2 );
	}
	
	/**
	 * Add plugin meta links.
	 * 
	 * @param	array	$input Registered links.
	 * @param	string	$file  Current plugin file.
	 * @return	array Merged links
	 */
	public static function render_plugin_documentation_link( $input, $file ) {
		if ( ! \str_ends_with( \EPI_BLOCK_CONTROL_FILE, $file ) ) {
			return $input;
		}
		
		return \array_merge(
			$input,
			[
				/* translators: plugin version */
				'<a href="' . \esc_url( \sprintf( \__( 'https://docs.epiph.yt/block-control/?version=%s', 'block-control' ), \get_plugin_data( \EPI_BLOCK_CONTROL_FILE )['Version'] ) ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Documentation', 'block-control' ) . '</a>',
			]
		);
	}
}
