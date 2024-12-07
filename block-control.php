<?php
namespace epiphyt\Block_Control;

/*
Plugin Name:		Block Control
Description:		Control the visibility of your Gutenberg blocks by conditions.
Version:			1.4.1
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
\defined( 'ABSPATH' ) || exit;

/**
 * Autoload all necessary classes.
 * 
 * @param	string	$class_name The class name of the auto-loaded class
 */
\spl_autoload_register( static function( $class_name ) {
	$namespace = \strtolower( __NAMESPACE__ . '\\' );
	$path = \explode( '\\', $class_name );
	$filename = \str_replace( '_', '-', \strtolower( \array_pop( $path ) ) );
	$class_name = \str_replace(
		[ $namespace, '\\', '_' ],
		[ '', '/', '-' ],
		\strtolower( $class_name )
	);
	$class_name = \str_replace( $filename, 'class-' . $filename, $class_name );
	$maybe_file = __DIR__ . '/inc/' . $class_name . '.php';
	
	if ( \file_exists( $maybe_file ) ) {
		require_once $maybe_file;
	}
} );

Block_Control::get_instance()->set_plugin_file( __FILE__ );
Block_Control::get_instance()->init();
