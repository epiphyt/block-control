<?php
namespace epiphyt\Block_Control;
use Mobile_Detect;
use function add_action;
use function add_filter;
use function dirname;
use function file_exists;
use function load_plugin_textdomain;
use function plugin_basename;
use function wp_enqueue_script;

/**
 * The main Block Control class.
 * 
 * @author	Epiphyt
 * @license	GPL2 <https://www.gnu.org/licenses/gpl-2.0.html>
 * @version	0.1
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
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ] );
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
		if ( $attr === 'hide_desktop' && $value === true && ! $this->mobile_detect->isMobile() && ! $this->mobile_detect->isTablet() ) {
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
		if ( $attr === 'hide_mobile' && $value === true && $this->mobile_detect->isMobile() ) {
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
		if ( $attr === 'hide_tablet' && $value === true && $this->mobile_detect->isTablet() ) {
			return true;
		}
		
		return false;
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
		
		// iterate through all block attributes
		foreach ( $block['attrs'] as $attr => $value ) {
			if ( $this->hide_desktop( $attr, $value ) ) {
				break;
			}
			
			if ( $this->hide_mobile( $attr, $value ) ) {
				break;
			}
			
			if ( $this->hide_tablet( $attr, $value ) ) {
				break;
			}
			
			// get the block content to output it
			$content = $block_content;
		}
		
		return $content;
	}
}
