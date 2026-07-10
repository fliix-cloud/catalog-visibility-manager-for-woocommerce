<?php
/**
 * Simple PSR-4 autoloader for the plugin.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 */
final class Autoloader {

	/**
	 * Register a PSR-4 namespace prefix to a base directory.
	 *
	 * @param string $prefix   Namespace prefix ending with \\.
	 * @param string $base_dir Absolute path to the base directory.
	 */
	public static function register( string $prefix, string $base_dir ): void {
		$prefix   = rtrim( $prefix, '\\' ) . '\\';
		$base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;

		spl_autoload_register(
			static function ( string $class ) use ( $prefix, $base_dir ): void {
				if ( ! str_starts_with( $class, $prefix ) ) {
					return;
				}

				$relative = substr( $class, strlen( $prefix ) );
				$file     = $base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';

				if ( is_file( $file ) ) {
					require_once $file;
				}
			}
		);
	}
}
