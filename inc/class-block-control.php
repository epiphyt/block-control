<?php
declare(strict_types = 1);

namespace epiphyt\Block_Control;

use Detection\MobileDetect;
use epiphyt\Block_Control\REST_API\Viewports;

/**
 * The main Block Control class.
 * 
 * @author	Epiphyt
 * @license	GPL2 <https://www.gnu.org/licenses/gpl-2.0.html>
 */
final class Block_Control {
	/**
	 * @since	1.1.0
	 * @var		string[] List of ignored custom post types
	 */
	private array $ignored_post_types = [
		'attachment',
		'custom_css',
		'customize_changeset',
		'epi_embed',
		'nav_menu_item',
		'oembed_cache',
		'revision',
		'user_request',
		'wp_block',
		'wp_font_face',
		'wp_font_family',
		'wp_global_styles',
		'wp_navigation',
		'wp_template',
		'wp_template_part',
	];
	
	/**
	 * @var		?static Class instance
	 */
	public static $instance = null;
	
	/**
	 * @var		\Detection\MobileDetect The Mobile Detect instance
	 */
	public \Detection\MobileDetect $mobile_detect;
	
	/**
	 * Block_Control constructor.
	 */
	public function __construct() {
		$this->mobile_detect = new MobileDetect();
	}
	
	/**
	 * Initialize functions.
	 */
	public function init(): void {
		\add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ], 100 );
		\add_action( 'init', [ self::class, 'register_blocks' ] );
		\add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
		\add_filter( 'register_block_type_args', [ $this, 'register_attributes' ] );
		\add_filter( 'render_block', [ $this, 'toggle_blocks' ], 10, 2 );
		
		Admin::init();
	}
	
	/**
	 * Add the screen reader text class to a class attribute.
	 * 
	 * @since	1.6.1
	 * 
	 * @param	string[]	$matches The matches of the class attribute
	 * @return	string The updated class attribute
	 */
	public static function add_screen_reader_class( array $matches ): string {
		$classes = \preg_split( '/\s+/', \trim( $matches[2] ) );
		
		// leave the class attribute untouched if it cannot be split
		if ( $classes === false ) {
			return $matches[0];
		}
		
		// nested blocks are filtered again as part of their parent content
		if ( ! \in_array( 'screen-reader-text', $classes, true ) ) {
			$classes[] = 'screen-reader-text';
		}
		
		return 'class=' . $matches[1] . \implode( ' ', $classes ) . $matches[1];
	}
	
	/**
	 * Get a list of ignored post types.
	 * 
	 * @since	1.1.0
	 * 
	 * @return	string[] The list of ignored post types
	 */
	public function get_ignored_post_types(): array {
		/**
		 * Filter the ignored post type list.
		 * 
		 * @since	1.1.0
		 * 
		 * @param	string[]	$ignored_post_types The current ignored post type list
		 */
		/** @var string[] $ignored_post_types */
		$ignored_post_types = (array) \apply_filters( 'block_control_ignored_post_types', $this->ignored_post_types );
		
		return $ignored_post_types;
	}
	
	/**
	 * Get a unique instance of the class.
	 * 
	 * @return	\epiphyt\Block_Control\Block_Control Class instance
	 */
	public static function get_instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Get all posts of all post types that are available for the block editor.
	 * 
	 * @since	1.1.0
	 * 
	 * @return	array<string, array{items: list<array{ID: int, post_title: string}>, title: string}> A list of posts within a list of post types
	 */
	public function get_posts(): array {
		$posts = [];
		
		foreach ( \get_post_types() as $post_type ) {
			if ( \in_array( $post_type, $this->get_ignored_post_types(), true ) ) {
				continue;
			}
			
			$post_type_object = \get_post_type_object( $post_type );
			
			// ignore post types that are not available in the block editor
			if ( empty( $post_type_object->show_in_rest ) ) {
				continue;
			}
			
			$post_ids = \get_posts( [
				'fields' => 'ids',
				'numberposts' => -1,
				'post_type' => $post_type,
			] );
			$post_map = [];
			
			foreach ( $post_ids as $post_id ) {
				$post_title = \get_post_field( 'post_title', $post_id );
				$post_map[] = [
					'ID' => $post_id,
					'post_title' => \is_string( $post_title ) ? $post_title : '',
				];
			}
			
			$posts[ $post_type ] = [
				'items' => $post_map,
				'title' => (string) $post_type_object->labels->name,
			];
		}
		
		return $posts;
	}
	
	/**
	 * Get all user roles.
	 * 
	 * @since	1.1.0
	 * 
	 * @return	array<string, string> A list of all roles
	 */
	public function get_roles(): array {
		global $wp_roles;
		$roles = [];
		
		foreach ( $wp_roles->roles as $key => $role ) {
			$roles[ $key ] = \translate_user_role( $role['name'] );
		}
		
		return $roles;
	}
	
	/**
	 * Add the editor assets.
	 */
	public function editor_assets(): void {
		\wp_localize_script(
			'block-control-settings-editor-script',
			'blockControlStore',
			[
				'posts' => $this->get_posts(),
				'roles' => $this->get_roles(),
				'viewports' => [
					'custom' => Viewport::get_custom(),
					'presets' => Viewport::get_presets(),
				],
			]
		);
	}
	
	/**
	 * Check if the content should be hidden by a conditional tag.
	 * 
	 * @since	1.1.0
	 * 
	 * @param	mixed	$value The attribute value
	 * @return	bool Whether the content should be hidden
	 */
	public function hide_conditional_tags( mixed $value ): bool {
		if ( ! \is_array( $value ) ) {
			return false;
		}
		
		$hidden = false;
		
		foreach ( $value as $tag => $is_hidden ) {
			switch ( $tag ) {
				case 'is_home':
					if ( $is_hidden && \is_home() ) {
						$hidden = true;
					}
					break;
				case 'is_front_page':
					if ( $is_hidden && \is_front_page() ) {
						$hidden = true;
					}
					break;
				case 'is_single':
					if ( $is_hidden && \is_single() ) {
						$hidden = true;
					}
					break;
				case 'is_sticky':
					if ( $is_hidden && \is_sticky() ) {
						$hidden = true;
					}
					break;
				case 'is_page':
					if ( $is_hidden && \is_page() ) {
						$hidden = true;
					}
					break;
				case 'is_category':
					if ( $is_hidden && \is_category() ) {
						$hidden = true;
					}
					break;
				case 'is_tag':
					if ( $is_hidden && \is_tag() ) {
						$hidden = true;
					}
					break;
				case 'is_tax':
					if ( $is_hidden && \is_tax() ) {
						$hidden = true;
					}
					break;
				case 'is_archive':
					if ( $is_hidden && \is_archive() ) {
						$hidden = true;
					}
					break;
				case 'is_search':
					if ( $is_hidden && \is_search() ) {
						$hidden = true;
					}
					break;
				case 'is_404':
					if ( $is_hidden && \is_404() ) {
						$hidden = true;
					}
					break;
				case 'is_paged':
					if ( $is_hidden && \is_paged() ) {
						$hidden = true;
					}
					break;
				case 'is_attachment':
					if ( $is_hidden && \is_attachment() ) {
						$hidden = true;
					}
					break;
				case 'is_singular':
					if ( $is_hidden && \is_singular() ) {
						$hidden = true;
					}
					break;
			}
			
			// return early if at least one tag is true
			if ( $hidden ) {
				return $hidden;
			}
		}
		
		return $hidden;
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string	$attr The attribute name
	 * @param	mixed	$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_desktop( string $attr, mixed $value ): bool {
		return $attr === 'hideDesktop'
			&& $value === true
			&& (
				! $this->mobile_detect->isMobile()
				|| $this->mobile_detect->isTablet()
			);
	}
	
	/**
	 * Check, whether the content should be hidden in feeds.
	 * 
	 * @param	mixed	$value Block attribute value
	 * @return	bool Whether the content should be hidden in feeds
	 */
	public static function hide_feed( mixed $value ): bool {
		return $value === true && \is_feed();
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string	$attr The attribute name
	 * @param	mixed	$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_logged_in( string $attr, mixed $value ): bool {
		return $attr === 'loginStatus' && $value === 'logged-out' && \is_user_logged_in();
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string	$attr The attribute name
	 * @param	mixed	$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_logged_out( string $attr, mixed $value ): bool {
		return $attr === 'loginStatus' && $value === 'logged-in' && ! \is_user_logged_in();
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string	$attr The attribute name
	 * @param	mixed	$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_mobile( string $attr, mixed $value ): bool {
		return $attr === 'hideMobile'
			&& $value === true
			&& $this->mobile_detect->isMobile()
			&& ! $this->mobile_detect->isTablet();
	}
	
	/**
	 * Check, whether the content should be hidden by its attributes.
	 * 
	 * @param	array{first?: bool, last?: bool, odd?: bool, even?: bool, custom?: string[]}|mixed	$value Attribute value
	 * @return	bool Whether the content should be hidden
	 */
	public static function hide_numbered_pages( mixed $value ): bool {
		if (
			! \is_home()
			&& ! \is_archive()
			&& ! \is_category()
			&& ! \is_tag()
			&& ! \is_tax()
			&& ! \is_search()
			|| ! \is_array( $value )
		) {
			return false;
		}
		
		/** @var \WP_Query $wp_query */
		global $wp_query;
		
		$current_page = ! empty( $wp_query->query_vars['paged'] ) ? $wp_query->query_vars['paged'] : 1;
		$last_page = ! empty( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
		
		if ( ! empty( $value['first'] ) && $current_page === 1 ) {
			return true;
		}
		
		if ( ! empty( $value['last'] ) && $current_page === $last_page ) {
			return true;
		}
		
		if ( ! empty( $value['odd'] ) && $current_page % 2 !== 0 ) {
			return true;
		}
		
		if ( ! empty( $value['even'] ) && $current_page % 2 === 0 ) {
			return true;
		}
		
		if ( ! empty( $value['custom'] ) && \is_array( $value['custom'] ) ) {
			return \in_array( (string) $current_page, $value['custom'], true );
		}
		
		return false;
	}
	
	/**
	 * Test if the content should be hidden by the post.
	 * 
	 * @since	1.1.0
	 * 
	 * @param	mixed	$value The attribute value
	 * @return	bool Whether the content should be hidden
	 */
	public function hide_post( mixed $value ): bool {
		if ( ! \is_array( $value ) ) {
			return false;
		}
		
		$post = \get_post();
		
		/**
		 * Filter the post object that is used to determine whether it's hidden.
		 * 
		 * @since	1.6.0
		 * 
		 * @param	?\WP_Post	$post Post object
		 * @param	array		$value The attribute value
		 */
		$post = \apply_filters( 'block_control_hide_post_object', $post, $value );
		
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		
		$hidden_posts = $value[ $post->post_type ] ?? null;
		
		if ( empty( $hidden_posts ) || ! \is_array( $hidden_posts ) ) {
			return false;
		}
		
		if ( isset( $hidden_posts[ $post->ID ] ) ) {
			return (bool) $hidden_posts[ $post->ID ];
		}
		
		return false;
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @since	1.1.0
	 * 
	 * @param	mixed	$value The attribute value
	 * @return	bool Whether the content should be hidden
	 */
	public function hide_roles( mixed $value ): bool {
		// logged-out users don't have any role
		// check them via login status
		if ( ! \is_user_logged_in() || ! \is_array( $value ) || empty( $value ) ) {
			return false;
		}
		
		// get the user object
		$user = \wp_get_current_user();
		
		foreach ( $value as $role => $is_hidden ) {
			// check if the user has a role that should be hidden
			if ( ! $is_hidden && \in_array( $role, $user->roles, true ) ) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Test if the content should be hidden for screen readers.
	 * 
	 * @since	1.2.0
	 * 
	 * @param	array<string, mixed>	$attributes Block attributes
	 * @return	bool Whether the content should be hidden
	 */
	public static function hide_screen_reader( array $attributes ): bool {
		return ! empty( $attributes['hideScreenReader'] );
	}
	
	/**
	 * Register block attributes.
	 * 
	 * @since	1.1.7
	 * 
	 * @param	array<string, mixed>	$args List of block arguments
	 * @return	array<string, mixed> Updated list of block arguments
	 */
	public function register_attributes( array $args ): array {
		$args['attributes'] = \array_merge( (array) ( $args['attributes'] ?? [] ), [
			'hideByDate' => [
				'default' => false,
				'type' => 'boolean',
			],
			'hideByDateEnd' => [
				'default' => '',
				'type' => 'string',
			],
			'hideByDateStart' => [
				'default' => '',
				'type' => 'string',
			],
			'hideConditionalTags' => [
				'default' => new \stdClass(),
				'type' => 'object',
			],
			'hideDesktop' => [
				'default' => false,
				'type' => 'boolean',
			],
			'hideFeed' => [
				'default' => false,
				'type' => 'boolean',
			],
			'hideMobile' => [
				'default' => false,
				'type' => 'boolean',
			],
			'hideNumberedPages' => [
				'default' => new \stdClass(),
				'type' => 'object',
			],
			'hidePosts' => [
				'default' => new \stdClass(),
				'type' => 'object',
			],
			'hideRoles' => [
				'default' => new \stdClass(),
				'type' => 'object',
			],
			'hideScreenReader' => [
				'default' => false,
				'type' => 'boolean',
			],
			'hideViewports' => [
				'default' => [],
				'items' => [
					'type' => 'string',
				],
				'type' => 'array',
			],
			'loginStatus' => [
				'default' => 'none',
				'type' => 'string',
			],
		] );
		
		return $args;
	}
	
	/**
	 * Register blocks.
	 */
	public static function register_blocks(): void {
		\wp_register_block_types_from_metadata_collection(
			\EPI_BLOCK_CONTROL_BASE . 'build',
			\EPI_BLOCK_CONTROL_BASE . 'build/blocks-manifest.php'
		);
	}
	
	/**
	 * Register REST API routes.
	 * 
	 * @since	2.0.0
	 */
	public static function register_rest_routes(): void {
		( new Viewports() )->register_routes();
	}
	
	/**
	 * A custom strtotime() function that takes the WordPress timezone settings
	 * into account.
	 * 
	 * @see		https://mediarealm.com.au/articles/wordpress-timezones-strtotime-date-functions/
	 * 
	 * @param	string	$str The string to pass
	 * @return	int A timestamp
	 * @throws	\Exception
	 */
	public function strtotime( string $str ): int {
		$tz_string = \get_option( 'timezone_string' );
		$tz_offset = \get_option( 'gmt_offset', 0 );
		$tz_offset = \is_scalar( $tz_offset ) ? (string) $tz_offset : '0';
		
		if ( ! empty( $tz_string ) && \is_string( $tz_string ) ) {
			// if site timezone option string exists, use it
			$timezone = $tz_string;
		}
		else if ( (int) $tz_offset === 0 ) {
			// get UTC offset, if it isn’t set then return UTC
			$timezone = 'UTC';
		}
		else {
			$timezone = $tz_offset;
			
			if (
				\substr( $tz_offset, 0, 1 ) !== '-'
				&& \substr( $tz_offset, 0, 1 ) !== '+'
				&& \substr( $tz_offset, 0, 1 ) !== 'U'
			) {
				$timezone = '+' . $tz_offset;
			}
		}
		
		$datetime = new \DateTimeImmutable( $str, new \DateTimeZone( $timezone ) );
		
		return (int) $datetime->format( 'U' );
	}
	
	/**
	 * Display or hide a block.
	 * 
	 * @param	string					$block_content The block content about to be appended
	 * @param	array<string, mixed>	$block The full block, including name and attributes
	 * @return	string The updated block content
	 */
	public function toggle_blocks( string $block_content, array $block ): string {
		/** @var array<string, mixed> $attributes */
		$attributes = (array) ( $block['attrs'] ?? [] );
		// set default content
		$content = '';
		// set default visibility
		$is_hidden = false;
		$hide_by_date = false;
		
		if ( \str_contains( $block_content, 'block-control__screen-reader-text' ) ) {
			$block_content = (string) \preg_replace_callback(
				'/class=(["\'])([^"\']*\bblock-control__screen-reader-text\b[^"\']*)\1/',
				[ self::class, 'add_screen_reader_class' ],
				$block_content
			);
		}
		
		// if there are no attributes, the block should be displayed
		if ( empty( $attributes ) ) {
			return $block_content;
		}
		
		// iterate through all block attributes
		foreach ( $attributes as $attr => $value ) {
			if (
				$this->hide_desktop( $attr, $value )
				|| $this->hide_mobile( $attr, $value )
				|| $this->hide_logged_in( $attr, $value )
				|| $this->hide_logged_out( $attr, $value )
				|| $attr === 'hideFeed' && self::hide_feed( $value )
				|| $attr === 'hideRoles' && $this->hide_roles( $value )
				|| $attr === 'hideConditionalTags' && $this->hide_conditional_tags( $value )
				|| $attr === 'hideNumberedPages' && self::hide_numbered_pages( $value )
				|| $attr === 'hidePosts' && $this->hide_post( $value )
			) {
				$is_hidden = true;
				break;
			}
			
			if ( $attr === 'hideByDate' && $value === true ) {
				$hide_by_date = true;
			}
			
			if ( $hide_by_date && $attr === 'hideByDateStart' && \is_string( $value ) ) {
				$end_date = $attributes['hideByDateEnd'] ?? null;
				
				if ( \time() > $this->strtotime( $value ) ) {
					$is_hidden = true;
					
					// check end date, too
					if (
						! \is_string( $end_date )
						|| (
							$this->strtotime( $value ) > $this->strtotime( $end_date )
							&& \time() > $this->strtotime( $end_date )
						)
						|| (
							$this->strtotime( $value ) <= $this->strtotime( $end_date )
							&& \time() < $this->strtotime( $end_date )
						)
					) {
						break;
					}
					else {
						$is_hidden = false;
					}
				}
			}
			
			if ( $hide_by_date && $attr === 'hideByDateEnd' && \is_string( $value ) ) {
				$start_date = $attributes['hideByDateStart'] ?? null;
				
				if ( \time() <= $this->strtotime( $value ) ) {
					$is_hidden = true;
					
					// check start date, too
					if (
						! \is_string( $start_date )
						|| (
							$this->strtotime( $value ) > $this->strtotime( $start_date )
							&& \time() > $this->strtotime( $start_date )
						)
						|| (
							$this->strtotime( $value ) <= $this->strtotime( $start_date )
							&& \time() < $this->strtotime( $start_date )
						)
					) {
						break;
					}
					else {
						$is_hidden = false;
					}
				}
			}
		}
		
		if ( ! $is_hidden ) {
			// get the block content to output it
			$content = $block_content;
		}
		
		// viewports are hidden via CSS and thus only relevant if the block is
		// not already hidden server-side
		if ( ! empty( $content ) && ! empty( $attributes['hideViewports'] ) ) {
			$content = Viewport::render( $content, (array) $attributes['hideViewports'] );
		}
		
		if ( self::hide_screen_reader( $attributes ) ) {
			// hiding the outer element hides its content as well, while matching
			// every element would also match closing tags and break the markup
			$content = (string) \preg_replace( '/<([a-zA-Z][^\s\/>]*)/', '<$1 aria-hidden="true"', $content, 1 );
		}
		
		return $content;
	}
}
