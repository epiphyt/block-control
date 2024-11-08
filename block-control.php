<?php
namespace epiphyt\Block_Control;
use function array_pop;
use function defined;
use function explode;
use function file_exists;
use function spl_autoload_register;
use function str_replace;
use function strtolower;
use function wp_doing_ajax;

/*
Plugin Name:		Block Control
Description:		Control the visibility of your Gutenberg blocks by conditions.
Version:			1.4.0
Author URI:			https://epiph.yt/en/
Author:				Epiphyt
Domain Path:		/languages
License URI:		https://www.gnu.org/licenses/gpl-2.0.html
License:			GPL2
Requires at least:	6.2
Requires PHP:		5.6
Tested up to:		6.7
Text Domain:		block-control

Block Control is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Block Control is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Block Control. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

// exit if ABSPATH is not defined
defined( 'ABSPATH' ) || exit;

if ( wp_doing_ajax() ) return;

/**
 * Autoload all necessary classes.
 * 
 * @param	string		$class The class name of the auto-loaded class
 */
spl_autoload_register( function( $class ) {
	$namespace = strtolower( __NAMESPACE__ . '\\' );
	$path = explode( '\\', $class );
	$filename = str_replace( '_', '-', strtolower( array_pop( $path ) ) );
	$class = str_replace(
		[ $namespace, '\\', '_' ],
		[ '', '/', '-' ],
		strtolower( $class )
	);
	$class = str_replace( $filename, 'class-' . $filename, $class );
	$maybe_file = __DIR__ . '/inc/' . $class . '.php';
	
	if ( file_exists( $maybe_file ) ) {
		require_once $maybe_file;
	}
} );

Block_Control::get_instance()->set_plugin_file( __FILE__ );
Block_Control::get_instance()->init();
