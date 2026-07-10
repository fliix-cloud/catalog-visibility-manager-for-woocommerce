<?php
/**
 * WooCommerce Product Add-ons compatibility.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Compatibility;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Frontend\CategoryHider;

/**
 * Class ProductAddons
 */
class ProductAddons {

	public function __construct(
		private readonly CategoryHider $category_hider,
	) {
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'get_product_addons_product_terms', $this->ignore_hidden_terms(...), 11, 2 );
	}

	/**
	 * Return real product categories for Product Add-ons (ignore storefront hides).
	 *
	 * @param mixed $terms   Terms (unused — reloaded).
	 * @param int   $post_id Product ID.
	 * @return array<int|string, mixed>
	 */
	public function ignore_hidden_terms( mixed $terms, mixed $post_id ): array {
		$this->category_hider->suspend();

		$result = function_exists( 'wc_get_object_terms' )
			? wc_get_object_terms( (int) $post_id, 'product_cat', 'term_id' )
			: [];

		$this->category_hider->resume();

		return is_array( $result ) ? $result : [];
	}
}
