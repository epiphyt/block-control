<?php
/**
 * PHPUnit bootstrap file.
 *
 * Sets up the Composer autoloader, the plugin constants that the classes under
 * test rely on and a minimal WP_Post stub, then loads the classes from inc/.
 *
 * @package epiphyt\Block_Control
 */
declare( strict_types = 1 );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

define( 'EPI_BLOCK_CONTROL_BASE', dirname( __DIR__ ) . '/' );
define( 'EPI_BLOCK_CONTROL_FILE', EPI_BLOCK_CONTROL_BASE . 'block-control.php' );
define( 'EPI_BLOCK_CONTROL_VERSION', '1.6.0' );

// minimal WP_Post stub so `instanceof WP_Post` works without WordPress loaded
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		/**
		 * @var int
		 */
		public $ID = 0;

		/**
		 * @var string
		 */
		public $post_type = 'post';

		/**
		 * @param array<string, mixed> $properties Properties to assign to the post.
		 */
		public function __construct( array $properties = [] ) {
			foreach ( $properties as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

require_once EPI_BLOCK_CONTROL_BASE . 'inc/class-admin.php';
require_once EPI_BLOCK_CONTROL_BASE . 'inc/class-viewport.php';
require_once EPI_BLOCK_CONTROL_BASE . 'inc/class-block-control.php';
require_once __DIR__ . '/class-test-case.php';
