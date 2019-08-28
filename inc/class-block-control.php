<?php
namespace epiphyt\Block_Control;
use Mobile_Detect;
use function add_action;
use function add_filter;
use function dirname;
use function file_exists;
use function is_user_logged_in;
use function load_plugin_textdomain;
use function plugin_basename;
use function plugin_dir_path;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_set_script_translations;

/**
 * The main Block Control class.
 * 
 * @author	Epiphyt
 * @license	GPL2 <https://www.gnu.org/licenses/gpl-2.0.html>
 * @version	1.0.1
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
		add_action( 'init', [ $this, 'editor_assets' ] );
		add_action( 'init', [ $this, 'load_script_translations' ] );
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
		wp_enqueue_style( 'block-control-editor-style', plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), [ 'wp-edit-blocks' ], filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) );
		wp_enqueue_script( 'block-control-editor', plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ), [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ], filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), true );
	}
	
	/**
	 * Test if the content should be hidden by its attributes.
	 * 
	 * @param	string		$attr The attribute name
	 * @param	bool		$value The attribute value
	 * @return	bool True if the content should be hidden, false otherwise
	 */
	private function hide_desktop( $attr, $value ) {
		if ( $attr === 'hideDesktop' && $value === true && ! $this->mobile_detect->isMobile() && ! $this->mobile_detect->isTablet() ) {
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
	private function hide_logged_in( $attr, $value ) {
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
	private function hide_logged_out( $attr, $value ) {
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
	private function hide_mobile( $attr, $value ) {
		if ( $attr === 'hideMobile' && $value === true && $this->mobile_detect->isMobile() && ! $this->mobile_detect->isTablet() ) {
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
	private function hide_tablet( $attr, $value ) {
		if ( $attr === 'hideTablet' && $value === true && $this->mobile_detect->isTablet() ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Load translations for scripts.
	 * 
	 * @since	1.0.1
	 */
	public function load_script_translations() {
		wp_set_script_translations( 'block-control-editor', 'block-control', plugin_dir_path( $this->plugin_file ) . 'languages' );
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
		
		// if there are no attributes, the block should be displayed
		if ( empty( $block['attrs'] ) ) {
			$content = $block_content;
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
			
			if ( $this->hide_tablet( $attr, $value ) ) {
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
		}
		
		if ( ! $is_hidden ) {
			// get the block content to output it
			$content = $block_content;
		}
		
		return $content;
	}
}
