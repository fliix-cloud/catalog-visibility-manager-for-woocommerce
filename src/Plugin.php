<?php
/**
 * Main plugin orchestrator.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Admin\SettingsSection;
use Fliix\HideCategoriesProducts\Admin\TaxonomyColumns;
use Fliix\HideCategoriesProducts\Compatibility\Hpos;
use Fliix\HideCategoriesProducts\Compatibility\ProductAddons;
use Fliix\HideCategoriesProducts\Compatibility\Storefront;
use Fliix\HideCategoriesProducts\Frontend\CategoryHider;
use Fliix\HideCategoriesProducts\Frontend\ProductHider;
use Fliix\HideCategoriesProducts\Settings\OptionsRepository;

/**
 * Class Plugin
 */
class Plugin {

	private ?OptionsRepository $options = null;

	/**
	 * @param string $plugin_file Main plugin file path (__FILE__).
	 */
	public function __construct(
		private readonly string $plugin_file,
	) {
	}

	/**
	 * Register early hooks and boot services when WooCommerce is available.
	 */
	public function boot(): void {
		( new Hpos( $this->plugin_file ) )->register();

		add_action( 'plugins_loaded', $this->init(...), 20 );
	}

	/**
	 * Initialize plugin services after plugins are loaded.
	 */
	public function init(): void {
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', $this->missing_woocommerce_notice(...) );
			return;
		}

		$this->options = new OptionsRepository();

		$category_hider = new CategoryHider( $this->options );
		$category_hider->register();

		( new ProductHider( $this->options ) )->register();
		( new Storefront( $this->options ) )->register();
		( new ProductAddons( $category_hider ) )->register();

		if ( is_admin() ) {
			( new SettingsSection( $this->options, $this->plugin_file ) )->register();
			( new TaxonomyColumns( $this->options ) )->register();
		}
	}

	/**
	 * Whether WooCommerce is active.
	 */
	private function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' ) || function_exists( 'WC' );
	}

	/**
	 * Admin notice when WooCommerce is missing.
	 */
	public function missing_woocommerce_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__(
			'Catalog Visibility Manager for WooCommerce requires WooCommerce to be installed and active.',
			'fliix-catalog-visibility-manager-for-woocommerce'
		);
		echo '</p></div>';
	}

	/**
	 * Plugin file path.
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Shared options repository (available after init).
	 */
	public function get_options(): ?OptionsRepository {
		return $this->options;
	}
}
