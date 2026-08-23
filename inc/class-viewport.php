<?php
declare(strict_types = 1);

namespace epiphyt\Block_Control;

use WP_HTML_Tag_Processor;
use WP_Theme_JSON;

/**
 * Viewport-related functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Block_Control
 * @since	2.0.0
 */
final class Viewport {
	/**
	 * The option name of the custom media queries.
	 * 
	 * @since	2.0.0
	 */
	public const OPTION_NAME = 'block_control_viewports';
	
	/**
	 * The default viewport breakpoints of WordPress.
	 * 
	 * @since	2.0.0
	 */
	private const DEFAULT_BREAKPOINTS = [
		'mobile' => '480px',
		'tablet' => '782px',
	];
	
	/**
	 * The maximum length of a media condition.
	 * 
	 * @since	2.0.0
	 */
	private const MAX_CONDITION_LENGTH = 200;
	
	/**
	 * The maximum number of stored custom media queries.
	 * 
	 * @since	2.0.0
	 */
	private const MAX_CUSTOM = 100;
	
	/**
	 * @since	2.0.0
	 * @var		?array<string, array{label: string, media_query: string}> The cached viewport presets
	 */
	private static ?array $presets = null;
	
	/**
	 * Get the CSS class name of a viewport entry.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	string	$entry A preset slug or a media condition
	 * @return	string The class name or an empty string, if the entry is invalid
	 */
	public static function get_class_name( string $entry ): string {
		if ( isset( self::get_presets()[ $entry ] ) ) {
			return 'block-control-hidden-' . $entry;
		}
		
		$condition = self::normalize_condition( $entry );
		
		if ( ! self::is_valid_condition( $condition ) ) {
			return '';
		}
		
		return 'block-control-hidden-' . \substr( \md5( $condition ), 0, 10 );
	}
	
	/**
	 * Get the custom media queries.
	 * 
	 * @since	2.0.0
	 * 
	 * @return	string[] The list of custom media conditions
	 */
	public static function get_custom(): array {
		return self::sanitize( \get_option( self::OPTION_NAME, [] ) );
	}
	
	/**
	 * Get the media query of a viewport entry.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	string	$entry A preset slug or a media condition
	 * @return	string The media query or an empty string, if the entry is invalid
	 */
	public static function get_media_query( string $entry ): string {
		$presets = self::get_presets();
		
		if ( isset( $presets[ $entry ] ) ) {
			return $presets[ $entry ]['media_query'];
		}
		
		$condition = self::normalize_condition( $entry );
		
		if ( ! self::is_valid_condition( $condition ) ) {
			return '';
		}
		
		return '@media ' . $condition;
	}
	
	/**
	 * Get all available viewport presets.
	 * 
	 * @since	2.0.0
	 * 
	 * @return	array<string, array{label: string, media_query: string}> The list of presets with their label and media query
	 */
	public static function get_presets(): array {
		if ( self::$presets !== null ) {
			return self::$presets;
		}
		
		// the path parameter of wp_get_global_settings() falls back to all
		// settings, which would be returned as viewport settings before
		// WordPress 7.1, where settings.viewport doesn't exist
		$settings = (array) \wp_get_global_settings();
		$viewport = $settings['viewport'] ?? null;
		$labels = [
			'desktop' => \__( 'Desktop', 'block-control' ),
			'mobile' => \__( 'Mobile', 'block-control' ),
			'tablet' => \__( 'Tablet', 'block-control' ),
		];
		$media_queries = self::get_preset_media_queries( $viewport );
		$presets = [];
		
		foreach ( $media_queries as $slug => $media_query ) {
			if ( empty( $labels[ $slug ] ) ) {
				continue;
			}
			
			$presets[ $slug ] = [
				'label' => $labels[ $slug ],
				'media_query' => $media_query,
			];
		}
		
		/**
		 * Filter the viewport presets.
		 * 
		 * @since	2.0.0
		 * 
		 * @param	array<string, array{label: string, media_query: string}>	$presets The current presets
		 */
		/** @var array<string, array{label: string, media_query: string}> $presets */
		$presets = (array) \apply_filters( 'block_control_viewport_presets', $presets );
		self::$presets = $presets;
		
		return self::$presets;
	}
	
	/**
	 * Check whether a media condition is valid.
	 * 
	 * The condition is part of a generated media query and thus needs to be
	 * limited to characters that cannot break out of it.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	string	$condition The media condition to check
	 * @return	bool Whether the media condition is valid
	 */
	public static function is_valid_condition( string $condition ): bool {
		if ( $condition === '' ) {
			return false;
		}
		
		if ( \strlen( $condition ) > self::MAX_CONDITION_LENGTH ) {
			return false;
		}
		
		// a condition without any feature is either a media type on its own or
		// a broken query, both of which are of no use here
		if ( ! \str_contains( $condition, '(' ) ) {
			return false;
		}
		
		if ( \substr_count( $condition, '(' ) !== \substr_count( $condition, ')' ) ) {
			return false;
		}
		
		return \preg_match( '/^[a-zA-Z0-9\s():,.\/<>=-]+$/', $condition ) === 1;
	}
	
	/**
	 * Add the visibility classes and styles to a block.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	string	$block_content The block content
	 * @param	mixed[]	$entries The list of preset slugs and media conditions
	 * @return	string The updated block content
	 */
	public static function render( string $block_content, array $entries ): string {
		if ( empty( $block_content ) ) {
			return $block_content;
		}
		
		$class_names = [];
		$css_rules = [];
		
		foreach ( $entries as $entry ) {
			if ( ! \is_string( $entry ) ) {
				continue;
			}
			
			$class_name = self::get_class_name( $entry );
			$media_query = self::get_media_query( $entry );
			
			if ( empty( $class_name ) || empty( $media_query ) ) {
				continue;
			}
			
			$class_names[ $class_name ] = $class_name;
			$css_rules[ $class_name ] = [
				'declarations' => [
					'display' => 'none !important',
				],
				'rules_group' => $media_query,
				'selector' => '.' . $class_name,
			];
		}
		
		if ( empty( $class_names ) ) {
			return $block_content;
		}
		
		\wp_style_engine_get_stylesheet_from_css_rules( \array_values( $css_rules ), [
			'context' => 'block-supports',
			'prettify' => false,
		] );
		
		$processor = new WP_HTML_Tag_Processor( $block_content );
		
		// without an outer element, there is nothing the classes could be added
		// to, and adding a wrapper on our own could break the layout
		if ( ! $processor->next_tag() ) {
			return $block_content;
		}
		
		$processor->add_class( \implode( ' ', $class_names ) );
		
		return $processor->get_updated_html();
	}
	
	/**
	 * Set the custom media queries.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	mixed[]	$viewports The list of custom media conditions
	 * @return	string[] The stored list of custom media conditions
	 */
	public static function set_custom( array $viewports ): array {
		$viewports = self::sanitize( $viewports );
		
		\update_option( self::OPTION_NAME, $viewports );
		
		return $viewports;
	}
	
	/**
	 * Delete all stored data.
	 * 
	 * @since	2.0.0
	 */
	public static function uninstall(): void {
		if ( ! \is_multisite() ) {
			\delete_option( self::OPTION_NAME );
			
			return;
		}
		
		$site_ids = \get_sites( [
			'fields' => 'ids',
			'number' => 0,
		] );
		
		foreach ( $site_ids as $site_id ) {
			\switch_to_blog( (int) $site_id );
			\delete_option( self::OPTION_NAME );
			\restore_current_blog();
		}
	}
	
	/**
	 * Get the breakpoint value of a viewport setting in pixels.
	 * 
	 * Only used to compare the breakpoint order, while the generated media
	 * queries keep the original unit.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	mixed	$value The breakpoint value
	 * @return	?float The breakpoint value in pixels, or null if it is invalid
	 */
	private static function get_breakpoint_in_pixels( mixed $value ): ?float {
		if ( ! \is_string( $value ) ) {
			return null;
		}
		
		$value = \trim( $value );
		
		if ( \preg_match( '/^(?:\d+|\d*\.\d+)(?:px|em|rem)$/', $value ) !== 1 ) {
			return null;
		}
		
		if ( \str_ends_with( $value, 'rem' ) ) {
			return (float) \substr( $value, 0, -3 ) * 16;
		}
		
		if ( \str_ends_with( $value, 'em' ) ) {
			return (float) \substr( $value, 0, -2 ) * 16;
		}
		
		return (float) \substr( $value, 0, -2 );
	}
	
	/**
	 * Get the media queries of all viewport presets.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	mixed	$viewport The viewport settings of theme.json
	 * @return	string[] The media queries by their preset slug
	 */
	private static function get_preset_media_queries( mixed $viewport ): array {
		// since WordPress 7.1, the media queries are generated by WordPress itself
		if ( \method_exists( WP_Theme_JSON::class, 'get_viewport_media_queries' ) ) {
			$media_queries = [];
			$states = WP_Theme_JSON::get_viewport_media_queries( $viewport, [ 'include_desktop' => true ] );
			
			foreach ( $states as $state => $media_query ) {
				$media_queries[ \ltrim( $state, '@' ) ] = $media_query;
			}
			
			return $media_queries;
		}
		
		$breakpoints = self::sanitize_breakpoints( $viewport );
		$media_queries = [];
		
		if ( isset( $breakpoints['mobile'] ) ) {
			$media_queries['mobile'] = '@media (width <= ' . $breakpoints['mobile'] . ')';
		}
		
		if ( isset( $breakpoints['tablet'] ) ) {
			if ( isset( $breakpoints['mobile'] ) ) {
				$media_queries['tablet'] = \sprintf( '@media (%s < width <= %s)', $breakpoints['mobile'], $breakpoints['tablet'] );
			}
			else {
				$media_queries['tablet'] = '@media (width <= ' . $breakpoints['tablet'] . ')';
			}
		}
		
		$media_queries['desktop'] = '@media (width > ' . ( $breakpoints['tablet'] ?? $breakpoints['mobile'] ) . ')';
		
		return $media_queries;
	}
	
	/**
	 * Normalize a media condition.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	mixed	$condition The media condition to normalize
	 * @return	string The normalized media condition
	 */
	private static function normalize_condition( mixed $condition ): string {
		if ( ! \is_string( $condition ) ) {
			return '';
		}
		
		$condition = (string) \preg_replace( '/\s+/', ' ', \trim( $condition ) );
		
		// allow pasting a complete media query
		if ( \stripos( $condition, '@media' ) === 0 ) {
			$condition = \trim( \substr( $condition, 6 ) );
		}
		
		return $condition;
	}
	
	/**
	 * Sanitize a list of custom media conditions.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	mixed	$viewports The list of media conditions to sanitize
	 * @return	string[] The sanitized list of media conditions
	 */
	private static function sanitize( mixed $viewports ): array {
		if ( ! \is_array( $viewports ) ) {
			return [];
		}
		
		$sanitized = [];
		
		foreach ( $viewports as $viewport ) {
			$condition = self::normalize_condition( $viewport );
			
			if ( ! self::is_valid_condition( $condition ) ) {
				continue;
			}
			
			$sanitized[ $condition ] = $condition;
			
			if ( \count( $sanitized ) >= self::MAX_CUSTOM ) {
				break;
			}
		}
		
		return \array_values( $sanitized );
	}
	
	/**
	 * Sanitize the viewport breakpoints of theme.json.
	 * 
	 * Reproduces the behavior of WordPress before version 7.1, where the
	 * viewport settings don't exist yet.
	 * 
	 * @since	2.0.0
	 * 
	 * @param	mixed	$viewport The viewport settings of theme.json
	 * @return	string[] The sanitized breakpoints
	 */
	private static function sanitize_breakpoints( mixed $viewport ): array {
		if ( ! \is_array( $viewport ) ) {
			return self::DEFAULT_BREAKPOINTS;
		}
		
		$breakpoints = [];
		
		foreach ( \array_keys( self::DEFAULT_BREAKPOINTS ) as $breakpoint ) {
			$value = $viewport[ $breakpoint ] ?? null;
			
			if ( ! \is_string( $value ) ) {
				continue;
			}
			
			$pixels = self::get_breakpoint_in_pixels( $value );
			
			if ( $pixels === null ) {
				continue;
			}
			
			$breakpoints[ $breakpoint ] = [
				'pixels' => $pixels,
				'value' => \trim( $value ),
			];
		}
		
		$mobile = $breakpoints['mobile'] ?? null;
		$tablet = $breakpoints['tablet'] ?? null;
		
		if ( $mobile === null && $tablet === null ) {
			return self::DEFAULT_BREAKPOINTS;
		}
		
		if ( $mobile === null ) {
			return [ 'tablet' => $tablet['value'] ];
		}
		
		if ( $tablet === null ) {
			return [ 'mobile' => $mobile['value'] ];
		}
		
		$sanitized = [
			'mobile' => $mobile['value'],
		];
		
		// a tablet breakpoint that is not larger than the mobile one is of no use
		if ( $mobile['pixels'] < $tablet['pixels'] ) {
			$sanitized['tablet'] = $tablet['value'];
		}
		
		return $sanitized;
	}
}
