<?php
/**
 * Hide product categories on the storefront.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Frontend;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Settings\OptionsRepository;
use WP_Term;

/**
 * Class CategoryHider
 */
class CategoryHider {

	private bool $suspended = false;

	public function __construct(
		private readonly OptionsRepository $options,
	) {
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'get_terms_args', $this->exclude_hidden_terms(...), 10, 2 );
		add_filter( 'get_the_terms', $this->hide_on_single_product(...), 11, 3 );
	}

	/**
	 * Temporarily disable category hiding.
	 */
	public function suspend(): void {
		$this->suspended = true;
	}

	/**
	 * Re-enable category hiding.
	 */
	public function resume(): void {
		$this->suspended = false;
	}

	/**
	 * Exclude hidden categories from front-end term queries.
	 *
	 * @param array<string, mixed> $args       Query arguments.
	 * @param mixed                $taxonomies Taxonomies being queried (WP may pass string|array).
	 * @return array<string, mixed>
	 */
	public function exclude_hidden_terms( array $args, mixed $taxonomies = [] ): array {
		if ( $this->suspended ) {
			return $args;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $args;
		}

		if ( ! $this->is_product_cat_query( $args, $taxonomies ) ) {
			return $args;
		}

		$hidden = $this->options->get_hidden_category_ids();
		if ( [] === $hidden ) {
			return $args;
		}

		$existing = [];
		if ( ! empty( $args['exclude'] ) ) {
			$existing = is_array( $args['exclude'] )
				? array_map( absint(...), $args['exclude'] )
				: array_map( absint(...), explode( ',', (string) $args['exclude'] ) );
		}

		$args['exclude'] = array_values( array_unique( [ ...$existing, ...$hidden ] ) );

		return $args;
	}

	/**
	 * Remove hidden categories from single product term lists.
	 *
	 * @param mixed $terms    Terms (WP: WP_Term[]|false).
	 * @param mixed $post_id  Post ID.
	 * @param mixed $taxonomy Taxonomy.
	 * @return mixed
	 */
	public function hide_on_single_product( mixed $terms, mixed $post_id, mixed $taxonomy ): mixed {
		if ( $this->suspended || ! is_array( $terms ) ) {
			return $terms;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $terms;
		}

		if ( 'product_cat' !== $taxonomy ) {
			return $terms;
		}

		// Only on product pages when the template function is available.
		if ( function_exists( 'is_product' ) && ! is_product() ) {
			return $terms;
		}

		$hidden = $this->options->get_hidden_category_ids();
		if ( [] === $hidden ) {
			return $terms;
		}

		foreach ( $terms as $key => $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			if ( in_array( (int) $term->term_id, $hidden, true ) ) {
				unset( $terms[ $key ] );
			}
		}

		return $terms;
	}

	/**
	 * Whether the query targets product_cat.
	 *
	 * @param array<string, mixed> $args       Query args.
	 * @param mixed                $taxonomies Taxonomies from filter (WP 4.5+).
	 */
	private function is_product_cat_query( array $args, mixed $taxonomies ): bool {
		$tax = $taxonomies;

		if ( ( [] === $tax || '' === $tax || null === $tax ) && isset( $args['taxonomy'] ) ) {
			$tax = $args['taxonomy'];
		}

		return match ( true ) {
			is_string( $tax ) => 'product_cat' === $tax,
			is_array( $tax )  => in_array( 'product_cat', $tax, true ),
			default           => false,
		};
	}
}
