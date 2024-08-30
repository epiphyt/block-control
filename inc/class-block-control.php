<?php
namespace epiphyt\Block_Control;
use DateTime;
use DateTimeZone;
use Mobile_Detect;
use stdClass;
use WP_Post;
use function add_action;
use function add_filter;
use function apply_filters;
use function dirname;
use function file_exists;
use function get_option;
use function get_post;
use function get_post_type_object;
use function get_post_types;
use function get_posts;
use function in_array;
use function is_404;
use function is_archive;
use function is_attachment;
use function is_category;
use function is_front_page;
use function is_home;
use function is_page;
use function is_paged;
use function is_search;
use function is_single;
use function is_singular;
use function is_sticky;
use function is_tag;
use function is_tax;
use function is_user_logged_in;
use function load_plugin_textdomain;
use function plugin_basename;
use function plugin_dir_path;
use function plugins_url;
use function substr;
use function time;
use function translate_user_role;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_get_current_user;
use function wp_localize_script;
use function wp_set_script_translations;

/**
 * The main Block Control class.
 * 
 * @author	Epiphyt
 * @license	GPL2 <https://www.gnu.org/licenses/gpl-2.0.html>
 */
class Block_Control {
	/**
	 * @since	1.1.0
	 * @var		string[] List of ignored custom post types
	 */
	private $ignored_post_types = [
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
	 * @var		\epiphyt\Block_Control\Block_Control
	 */
	public static $instance;
	
	/**
	 * @var		\Mobile_Detect The Mobile Detect instance
	 */
	public $mobile_detect;
	
	/**
	 * @var		string The plugin filename
	 */
	public $plugin_file = '';
	
	/**
	 * Block_Control constructor.
	 */
	public function __construct() {
		require_once 'lib/class-mobile-detect.php';
		
		self::$instance = $this;
		$this->mobile_detect = new Mobile_Detect();
	}
	
	/**
	 * Initialize functions.
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ], 100 );
		add_action( 'init', [ $this, 'load_textdomain' ], 0 );
		add_filter( 'register_block_type_args', [ $this, 'register_attributes' ] );
		add_filter( 'render_block', [ $this, 'toggle_blocks' ], 10, 2 );
	}
	
	/**
	 * Get a list of ignored post types.
	 * 
	 * @since	1.1.0
	 * 
	 * @return	array The list of ignored post types
	 */
	public function get_ignored_post_types() {
		/**
		 * Filter the ignored post type list.
		 * 
		 * @param	array	$ignored_post_types The current ignored post type list
		 */
		$post_types = apply_filters( 'block_control_ignored_post_types', $this->ignored_post_types );
		
		return $post_types;
	}
	
	/**
	 * Get a unique instance of the class.
	 * 
	 * @return	\epiphyt\Block_Control\Block_Control
	 */
	public static function get_instance() {
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
	 * @return	array A list of posts within a list of post types
	 */
	public function get_posts() {
		$posts = [];
		
		foreach ( get_post_types() as $post_type ) {
			if ( in_array( $post_type, $this->get_ignored_post_types(), true ) ) {
				continue;
			}
			
			$post_type_object = get_post_type_object( $post_type );
			
			// ignore post types that are not available in the block editor
			if ( empty( $post_type_object->show_in_rest ) ) {
				continue;
			}
			
			$posts[ $post_type ] = [
				'items' => get_posts( [
					'numberposts' => -1,
					'post_type' => $post_type,
				] ),
				'title' => $post_type_object->labels->name,
			];
		}
		
		return $posts;
	}
	
	/**
	 * Get all user roles.
	 * 
	 * @since	1.1.0
	 * 
	 * @return	array A list of all roles
	 */
	public function get_roles() {
		global $wp_roles;
		$roles = [];
		
		foreach ( $wp_roles->roles as $key => $role ) {
			$roles[ $key ] = translate_user_role( $role['name'] );
		}
		
		return $roles;
	}
	
	/**
	 * Add the editor assets.
	 */
	public function editor_assets() {
		// automatically load dependencies and version
		$asset_file = include plugin_dir_path( $this->plugin_file ) . 'build/index.asset.php';
		wp_enqueue_style( 'block-control-editor-style', plugins_url( 'build/index.css', __DIR__ ), [], $asset_file['version'] );
		wp_enqueue_script( 'block-control-editor', plugins_url( '/build/index.js', __DIR__ ), $asset_file['dependencies'], $asset_file['version'] );
		wp_set_script_translations( 'block-control-editor', 'block-control', plugin_dir_path( __FILE__ ) . 'languages' );
		wp_localize_script( 'block-control-editor', 'blockControlStore', [
			'posts' => $this->get_posts(),
			'roles' => $this->get_roles(),
		] );
	}
	
	/**
	 * Check if the content should be hidden by a conditional tag.
	 * 
	 * @since	1.1.0
	 * 
	 * @param	array	$value The attribute value
	 * @return	bool Whether the content should be hidden
	 */
	public function hide_conditional_tags( array $value ) {
		$hidden = false;
		
		foreach ( $value as $tag => $is_hidden ) {
			switch ( $tag ) {
				case 'is_home':
					if ( $is_hidden && is_home() ) {
						$hidden = true;
					}
					break;
				case 'is_front_page':
					if ( $is_hidden && is_front_page() ) {
						$hidden = true;
					}
					break;
				case 'is_single':
					if ( $is_hidden && is_single() ) {
						$hidden = true;
					}
					break;
				case 'is_sticky':
					if ( $is_hidden && is_sticky() ) {
						$hidden = true;
					}
					break;
				case 'is_page':
					if ( $is_hidden && is_page() ) {
						$hidden = true;
					}
					break;
				case 'is_category':
					if ( $is_hidden && is_category() ) {
						$hidden = true;
					}
					break;
				case 'is_tag':
					if ( $is_hidden && is_tag() ) {
						$hidden = true;
					}
					break;
				case 'is_tax':
					if ( $is_hidden && is_tax() ) {
						$hidden = true;
					}
					break;
				case 'is_archive':
					if ( $is_hidden && is_archive() ) {
						$hidden = true;
					}
					break;
				case 'is_search':
					if ( $is_hidden && is_search() ) {
						$hidden = true;
					}
					break;
				case 'is_404':
					if ( $is_hidden && is_404() ) {
						$hidden = true;
					}
					break;
				case 'is_paged':
					if ( $is_hidden && is_paged() ) {
						$hidden = true;
					}
					break;
				case 'is_attachment':
					if ( $is_hidden && is_attachment() ) {
						$hidden = true;
					}
					break;
				case 'is_singular':
					if ( $is_hidden && is_singular() ) {
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
	 * @param	string		$attr The attribute name
	 * @param	bool		$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_desktop( $attr, $value ) {
		if ( $attr === 'hideDesktop' && $value === true && ( ! $this->mobile_detect->isMobile() || $this->mobile_detect->isTablet() ) ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string		$attr The attribute name
	 * @param	bool		$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_logged_in( $attr, $value ) {
		if ( $attr === 'loginStatus' && $value === 'logged-out' && is_user_logged_in() ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string		$attr The attribute name
	 * @param	bool		$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_logged_out( $attr, $value ) {
		if ( $attr === 'loginStatus' && $value === 'logged-in' && ! is_user_logged_in() ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string		$attr The attribute name
	 * @param	bool		$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_mobile( $attr, $value ) {
		if ( $attr === 'hideMobile' && $value === true && $this->mobile_detect->isMobile() && ! $this->mobile_detect->isTablet() ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Test if the content should be hidden by the post.
	 * 
	 * @since	1.1.0
	 * 
	 * @param	array	$value The attribute value
	 * @return	bool Whether the content should be hidden
	 */
	public function hide_post( $value ) {
		$post = get_post();
		
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
		
		if ( empty( $value[ $post->post_type ] ) ) {
			return false;
		}
		
		if ( isset( $value[ $post->post_type ][ $post->ID ] ) ) {
			return (bool) $value[ $post->post_type ][ $post->ID ];
		}
		
		return false;
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @since	1.1.0
	 * 
	 * @param	array	$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	public function hide_roles( $value ) {
		// logged-out users don't have any role
		// check them via login status
		if ( ! is_user_logged_in() || empty( $value ) ) {
			return false;
		}
		
		// get the user object
		$user = wp_get_current_user();
		
		foreach ( $value as $role => $is_hidden ) {
			// check if the user has a role that should be hidden
			if ( ! $is_hidden && in_array( $role, $user->roles, true ) ) {
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
	 * @param	array	$attributes Block attributes
	 * @return	bool Whether the content should be hidden
	 */
	public static function hide_screen_reader( array $attributes ) {
		return ! empty( $attributes['hideScreenReader'] );
	}
	
	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'block-control', false, dirname( plugin_basename( $this->plugin_file ) ) . '/languages' );
	}
	
	/**
	 * Register block attributes.
	 * 
	 * @since	1.1.7
	 * 
	 * @param	array	$args List of block arguments
	 * @return	array Updated list of block arguments
	 */
	public function register_attributes( $args ) {
		$args['attributes'] = \array_merge( $args['attributes'], [
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
				'default' => new stdClass(),
				'type' => 'object',
			],
			'hideDesktop' => [
				'default' => false,
				'type' => 'boolean',
			],
			'hideMobile' => [
				'default' => false,
				'type' => 'boolean',
			],
			'hidePosts' => [
				'default' => new stdClass(),
				'type' => 'object',
			],
			'hideRoles' => [
				'default' => new stdClass(),
				'type' => 'object',
			],
			'hideScreenReader' => [
				'default' => false,
				'type' => 'boolean',
			],
			'loginStatus' => [
				'default' => 'none',
				'type' => 'string',
			],
		] );
		
		return $args;
	}
	
	/**
	 * Set the plugin file.
	 * 
	 * @param	string	$file The path to the file
	 */
	public function set_plugin_file( $file ) {
		if ( file_exists( $file ) ) {
			$this->plugin_file = $file;
		}
	}
	
	/**
	 * A custom strtotime() function that takes the WordPress timezone settings
	 * into account.
	 * 
	 * @see		https://mediarealm.com.au/articles/wordpress-timezones-strtotime-date-functions/
	 * 
	 * @param	string		$str The string to pass
	 * @return	int A timestamp
	 * @throws	\Exception
	 */
	public function strtotime( $str ) {
		$tz_string = get_option( 'timezone_string' );
		$tz_offset = get_option( 'gmt_offset', 0 );
		
		if ( ! empty( $tz_string ) ) {
			// if site timezone option string exists, use it
			$timezone = $tz_string;
		}
		else if ( (int) $tz_offset === 0 ) {
			// get UTC offset, if it isn’t set then return UTC
			$timezone = 'UTC';
		}
		else {
			$timezone = $tz_offset;
			
			if ( substr( $tz_offset, 0, 1 ) !== '-' && substr( $tz_offset, 0, 1 ) !== '+' && substr( $tz_offset, 0, 1 ) !== 'U' ) {
				$timezone = '+' . $tz_offset;
			}
		}
		
		$datetime = new DateTime( $str, new DateTimeZone( $timezone ) );
		
		return (int) $datetime->format( 'U' );
	}
	
	/**
	 * Display or hide a block.
	 * 
	 * @param	string		$block_content The block content about to be appended
	 * @param	array		$block The full block, including name and attributes
	 * @return	string The updated block content
	 */
	public function toggle_blocks( $block_content, $block ) {
		// set default content
		$content = '';
		// set default visibility
		$is_hidden = false;
		$hide_by_date = false;
		
		// if there are no attributes, the block should be displayed
		if ( empty( $block['attrs'] ) ) {
			return $block_content;
		}
		
		// iterate through all block attributes
		foreach ( $block['attrs'] as $attr => $value ) {
			if ( $this->hide_desktop( $attr, $value ) ) {
				$is_hidden = true;
				break;
			}
			
			if ( $this->hide_mobile( $attr, $value ) ) {
				$is_hidden = true;
				break;
			}
			
			if ( $this->hide_logged_in( $attr, $value ) ) {
				$is_hidden = true;
				break;
			}
			
			if ( $this->hide_logged_out( $attr, $value ) ) {
				$is_hidden = true;
				break;
			}
			
			if ( $attr === 'hideByDate' && $value === true ) {
				$hide_by_date = true;
			}
			
			if ( $hide_by_date && $attr === 'hideByDateStart' ) {
				if ( time() > $this->strtotime( $value ) ) {
					$is_hidden = true;
					
					// check end date, too
					if (
						! isset( $block['attrs']['hideByDateEnd'] )
						|| (
							$this->strtotime( $value ) > $this->strtotime( $block['attrs']['hideByDateEnd'] )
							&& time() > $this->strtotime( $block['attrs']['hideByDateEnd'] )
						)
						|| (
							$this->strtotime( $value ) <= $this->strtotime( $block['attrs']['hideByDateEnd'] )
							&& time() < $this->strtotime( $block['attrs']['hideByDateEnd'] )
						)
					) {
						break;
					}
					else {
						$is_hidden = false;
					}
				}
			}
			
			if ( $hide_by_date && $attr === 'hideByDateEnd' ) {
				if ( time() <= $this->strtotime( $value ) ) {
					$is_hidden = true;
					
					// check start date, too
					if (
						! isset( $block['attrs']['hideByDateStart'] )
						|| (
							$this->strtotime( $value ) > $this->strtotime( $block['attrs']['hideByDateStart'] )
							&& time() > $this->strtotime( $block['attrs']['hideByDateStart'] )
						)
						|| (
							$this->strtotime( $value ) <= $this->strtotime( $block['attrs']['hideByDateStart'] )
							&& time() > $this->strtotime( $block['attrs']['hideByDateStart'] )
						)
					) {
						break;
					}
					else {
						$is_hidden = false;
					}
				}
			}
			
			if ( $attr === 'hideRoles' && $this->hide_roles( $value ) ) {
				$is_hidden = true;
				break;
			}
			
			if ( $attr === 'hideConditionalTags' && $this->hide_conditional_tags( $value ) ) {
				$is_hidden = true;
				break;
			}
			
			if ( $attr === 'hidePosts' && $this->hide_post( $value ) ) {
				$is_hidden = true;
				break;
			}
		}
		
		if ( ! $is_hidden ) {
			// get the block content to output it
			$content = $block_content;
		}
		
		if ( self::hide_screen_reader( $block['attrs'] ) ) {
			$content = \preg_replace( '/^<([^\s>]+)/m', '<$1 aria-hidden="true"', $content );
		}
		
		return $content;
	}
}
