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
		add_filter( 'get_terms', $this->filter_hidden_terms(...), 10, 4 );
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
	 * Remove hidden categories from front-end term query results.
	 *
	 * @param mixed                $terms      Query results.
	 * @param mixed                $taxonomies Taxonomies being queried.
	 * @param array<string, mixed> $args       Query arguments.
	 * @param mixed                $term_query Term query instance.
	 * @return mixed
	 */
	public function filter_hidden_terms( mixed $terms, mixed $taxonomies = [], array $args = [], mixed $term_query = null ): mixed {
		if ( $this->suspended ) {
			return $terms;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $terms;
		}

		if ( ! $this->is_product_cat_query( $args, $taxonomies ) ) {
			return $terms;
		}

		$hidden = $this->options->get_hidden_category_ids();
		if ( [] === $hidden || ! is_array( $terms ) ) {
			return $terms;
		}

		$field = isset( $args['fields'] ) ? (string) $args['fields'] : 'all';
		foreach ( $terms as $key => $term ) {
			$term_id = $this->get_result_term_id( $term, $key, $field );
			if ( $term_id > 0 && in_array( $term_id, $hidden, true ) ) {
				unset( $terms[ $key ] );
			}
		}

		return $terms;
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

	/**
	 * Resolve a term ID from get_terms() result formats that expose term IDs.
	 */
	private function get_result_term_id( mixed $term, int|string $key, string $field ): int {
		if ( $term instanceof WP_Term ) {
			return (int) $term->term_id;
		}

		if ( is_numeric( $term ) && 'ids' === $field ) {
			return absint( $term );
		}

		if ( is_numeric( $key ) && in_array( $field, [ 'id=>name', 'id=>slug', 'id=>parent' ], true ) ) {
			return absint( $key );
		}

		return 0;
	}
}
