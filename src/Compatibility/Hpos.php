<?php
/**
 * WooCommerce HPOS compatibility declaration.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Compatibility;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Class Hpos
 */
class Hpos {

	public function __construct(
		private readonly string $plugin_file,
	) {
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'before_woocommerce_init', $this->declare_compatibility(...) );
	}

	/**
	 * Declare custom order tables compatibility.
	 */
	public function declare_compatibility(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->plugin_file, true );
		}
	}
}
