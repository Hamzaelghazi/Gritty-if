<?php
/**
 * PSR-4 style autoloader for the Nasm\OrderTracking namespace.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Core;

defined( 'ABSPATH' ) || exit;

class Autoloader {

	/**
	 * Root namespace mapped to the includes/ directory.
	 */
	const PREFIX = 'Nasm\\OrderTracking\\';

	/**
	 * Register the autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Resolve a fully-qualified class name to a file path and load it.
	 *
	 * @param string $class Fully qualified class name.
	 */
	public static function load( $class ) {
		if ( strpos( $class, self::PREFIX ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$file     = NASM_OT_PATH . 'includes' . DIRECTORY_SEPARATOR . $relative . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
