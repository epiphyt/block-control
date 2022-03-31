<?php
namespace epiphyt\Block_Control;
use DateTime;
use DateTimeZone;
use Mobile_Detect;
use function add_action;
use function add_filter;
use function dirname;
use function file_exists;
use function get_option;
use function in_array;
use function filemtime;
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
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ], 0 );
		add_action( 'init', [ $this, 'load_textdomain' ], 0 );
		add_filter( 'render_block', [ $this, 'toggle_blocks' ], 10, 2 );
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
	 * Add the editor assets.
	 */
	public function editor_assets() {
		// automatically load dependencies and version
		/** @noinspection PhpIncludeInspection */
		$asset_file = include( plugin_dir_path( $this->plugin_file ) . 'build/index.asset.php' );
		wp_enqueue_style( 'block-control-editor-style', plugins_url( 'build/index.css', dirname( __FILE__ ) ), [], $asset_file['version'] );
		wp_enqueue_script( 'block-control-editor', plugins_url( '/build/index.js', dirname( __FILE__ ) ), $asset_file['dependencies'], $asset_file['version'], false );
		wp_set_script_translations( 'block-control-editor', 'block-control', plugin_dir_path( __FILE__ ) . 'languages' );
		wp_localize_script( 'block-control-editor', 'blockControlStore', [
			'roles' => $this->get_roles(),
		] );
	}
	
	/**
	 * Get all user roles.
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
	 * Test if the content should be hidden by its attributes.
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
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'block-control', false, dirname( plugin_basename( $this->plugin_file ) ) . '/languages' );
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
					break;
				}
			}
			
			if ( $hide_by_date && $attr === 'hideByDateEnd' ) {
				if ( time() <= $this->strtotime( $value ) ) {
					$is_hidden = true;
					break;
				}
			}
			
			if ( $attr === 'hideRoles' && $this->hide_roles( $value ) ) {
				$is_hidden = true;
				break;
			}
		}
		
		if ( ! $is_hidden ) {
			// get the block content to output it
			$content = $block_content;
		}
		
		return $content;
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
		else if ( $tz_offset == 0 ) {
			// get UTC offset, if it isn’t set then return UTC
			$timezone = 'UTC';
		}
		else {
			$timezone = $tz_offset;
			
			if ( substr( $tz_offset, 0, 1 ) !== '-' && substr( $tz_offset, 0, 1 ) !== '+' && substr( $tz_offset, 0, 1 ) !== 'U' ) {
				$timezone = "+" . $tz_offset;
			}
		}
		
		$datetime = new DateTime( $str, new DateTimeZone( $timezone ) );
		
		return (int) $datetime->format( 'U' );
	}
}
