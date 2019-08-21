<?php
namespace epiphyt\Block_Control;
use function add_action;
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
	 * @var		\epiphyt\Block_Control\Block_Control;
	 */
	public static $instance;
	
	/**
	 * @var		string The plugin filename
	 */
	public $plugin_file = '';
	
	/**
	 * Block_Control constructor.
	 */
	public function __construct() {
		self::$instance = $this;
	}
	
	/**
	 * Initialize functions.
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ] );
		add_action( 'init', [ $this, 'load_textdomain' ], 0 );
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
}
