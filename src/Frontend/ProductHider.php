<?php
/**
 * Hide products that belong to selected categories on the storefront.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Frontend;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Settings\OptionsRepository;
use WP_Query;

/**
 * Class ProductHider
 */
class ProductHider {

	public function __construct(
		private readonly OptionsRepository $options,
	) {
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_product_query', $this->filter_product_query(...) );
		add_filter( 'posts_clauses', $this->filter_posts_clauses(...), 10, 2 );
		add_filter( 'posts_search', $this->append_search_exclusion(...), 500, 2 );
	}

	/**
	 * Exclude products via tax_query on WooCommerce product queries.
	 *
	 * @param WP_Query $query Query instance (WC may pass WC_Query).
	 */
	public function filter_product_query( object $query ): void {
		if ( ! $query instanceof WP_Query ) {
			return;
		}

		$excluded = $this->options->get_product_excluded_category_ids();
		if ( [] === $excluded ) {
			return;
		}

		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = [
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $excluded,
			'operator' => 'NOT IN',
		];
		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Extra SQL exclusion for product queries (product collections, etc.).
	 *
	 * @param array<string, string> $clauses Query clauses.
	 * @param WP_Query              $query   Query instance.
	 * @return array<string, string>
	 */
	public function filter_posts_clauses( array $clauses, object $query ): array {
		if ( ! $query instanceof WP_Query ) {
			return $clauses;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $clauses;
		}

		if ( ! $this->is_product_query( $query ) ) {
			return $clauses;
		}

		$tt_ids = $this->options->get_excluded_term_taxonomy_ids();
		if ( [] === $tt_ids ) {
			return $clauses;
		}

		global $wpdb;

		$ids_sql           = implode( ',', array_map( absint(...), $tt_ids ) );
		$clauses['where'] .= " AND ({$wpdb->posts}.ID NOT IN (
			SELECT object_id FROM {$wpdb->term_relationships}
			WHERE term_taxonomy_id IN ({$ids_sql})
		)) ";

		return $clauses;
	}

	/**
	 * Append product exclusion to front-end search without rewriting search logic.
	 *
	 * @param string   $search Search SQL.
	 * @param WP_Query $query  Query instance.
	 */
	public function append_search_exclusion( string $search, object $query ): string {
		if ( ! $query instanceof WP_Query ) {
			return $search;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $search;
		}

		if ( '' === $search ) {
			return $search;
		}

		if ( ! $this->is_product_query( $query ) && ! $this->is_search_including_products( $query ) ) {
			return $search;
		}

		$tt_ids = $this->options->get_excluded_term_taxonomy_ids();
		if ( [] === $tt_ids ) {
			return $search;
		}

		global $wpdb;

		$ids_sql = implode( ',', array_map( absint(...), $tt_ids ) );
		$search .= " AND {$wpdb->posts}.ID NOT IN (
			SELECT object_id FROM {$wpdb->term_relationships}
			WHERE term_taxonomy_id IN ({$ids_sql})
		)";

		return $search;
	}

	/**
	 * Whether the query targets products.
	 */
	private function is_product_query( WP_Query $query ): bool {
		$post_type = $query->get( 'post_type' );

		if ( empty( $post_type ) && $query->is_search() ) {
			return true;
		}

		return match ( true ) {
			is_string( $post_type ) => 'product' === $post_type,
			is_array( $post_type )  => in_array( 'product', $post_type, true ),
			default                 => false,
		};
	}

	/**
	 * Front-end search that may include products.
	 */
	private function is_search_including_products( WP_Query $query ): bool {
		return $query->is_search() && ! is_admin();
	}
}
