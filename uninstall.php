<?php
/**
 * Uninstall Block Control.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Block_Control
 */
declare(strict_types = 1);

namespace epiphyt\Block_Control;

\defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/inc/class-viewport.php';

Viewport::uninstall();
