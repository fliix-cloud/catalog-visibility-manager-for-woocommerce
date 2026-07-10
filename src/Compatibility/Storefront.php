<?php
/**
 * Storefront theme shortcode compatibility.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Compatibility;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Settings\OptionsRepository;

/**
 * Class Storefront
 */
class Storefront {

	/**
	 * Shortcode arg filters to hook.
	 *
	 * @var list<string>
	 */
	private readonly array $filters;

	public function __construct(
		private readonly OptionsRepository $options,
	) {
		$this->filters = [
			'storefront_featured_products_shortcode_args',
			'storefront_popular_products_shortcode_args',
			'storefront_recent_products_shortcode_args',
			'storefront_best_selling_products_shortcode_args',
			'storefront_on_sale_products_shortcode_args',
		];
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		foreach ( $this->filters as $filter ) {
			add_filter( $filter, $this->filter_shortcode_args(...) );
		}
	}

	/**
	 * Exclude products from hidden product categories in Storefront shortcodes.
	 *
	 * @param array<string, mixed> $params Shortcode parameters.
	 * @return array<string, mixed>
	 */
	public function filter_shortcode_args( array $params ): array {
		$excluded = $this->options->get_product_excluded_category_ids();
		if ( [] === $excluded ) {
			return $params;
		}

		$params['category']     = implode( ',', array_map( strval(...), $excluded ) );
		$params['cat_operator'] = 'NOT IN';

		return $params;
	}
}
