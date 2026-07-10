<?php
/**
 * Plugin Name:       Catalog Visibility Manager for WooCommerce
 * Description:       Control which product categories and products appear on your WooCommerce storefront. Searchable category tree with separate hide controls.
 * Version:           2.2.0
 * Requires at least: 5.8
 * Requires PHP:      8.1
 * Author:            fliix - Marc Werner
 * Author URI:        https://www.fliix.cloud
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       catalog-visibility-manager-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 5.0
 * WC tested up to:   9.3
 *
 * Independent third-party extension for WooCommerce. Not affiliated with Automattic or WooCommerce.
 *
 * Inspired by the GPL plugin "Hide Categories and Products for WooCommerce"
 * by N.O.U.S. Open Useful and Simple.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: required PHP version, 2: current PHP version */
						__( 'Catalog Visibility Manager for WooCommerce requires PHP %1$s or higher. You are running PHP %2$s.', 'catalog-visibility-manager-for-woocommerce' ),
						'8.1',
						PHP_VERSION
					)
				)
			);
		}
	);
	return;
}

define( 'FLIIX_HCP_VERSION', '2.2.0' );
define( 'FLIIX_HCP_FILE', __FILE__ );
define( 'FLIIX_HCP_PATH', plugin_dir_path( __FILE__ ) );
define( 'FLIIX_HCP_URL', plugin_dir_url( __FILE__ ) );

require_once FLIIX_HCP_PATH . 'src/Autoloader.php';

\Fliix\HideCategoriesProducts\Autoloader::register(
	prefix: 'Fliix\\HideCategoriesProducts\\',
	base_dir: FLIIX_HCP_PATH . 'src/'
);

( new \Fliix\HideCategoriesProducts\Plugin( __FILE__ ) )->boot();