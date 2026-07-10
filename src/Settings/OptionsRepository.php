<?php
/**
 * Options repository for category / product visibility settings.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes hide settings with request-level caching.
 *
 * Option keys are kept from the original plugin for data compatibility:
 * - wchc_hide_product_cats
 * - wchc_hide_products_from_cat
 */
class OptionsRepository {

	public const OPTION_HIDDEN_CATEGORIES = 'wchc_hide_product_cats';
	public const OPTION_HIDDEN_PRODUCTS   = 'wchc_hide_products_from_cat';

	/** @var list<int>|null */
	private ?array $hidden_category_ids = null;

	/** @var list<int>|null */
	private ?array $product_excluded_ids = null;

	/** @var list<int>|null */
	private ?array $excluded_term_taxonomy_ids = null;

	/**
	 * Get term IDs of categories hidden from the storefront.
	 *
	 * @return list<int>
	 */
	public function get_hidden_category_ids(): array {
		return $this->hidden_category_ids ??= $this->parse_active_ids(
			get_option( self::OPTION_HIDDEN_CATEGORIES, [] )
		);
	}

	/**
	 * Get term IDs of categories whose products are hidden on the storefront.
	 *
	 * @return list<int>
	 */
	public function get_product_excluded_category_ids(): array {
		return $this->product_excluded_ids ??= $this->parse_active_ids(
			get_option( self::OPTION_HIDDEN_PRODUCTS, [] )
		);
	}

	/**
	 * Whether a category itself is hidden.
	 */
	public function is_category_hidden( int $term_id ): bool {
		return in_array( $term_id, $this->get_hidden_category_ids(), true );
	}

	/**
	 * Whether products in a category are hidden.
	 */
	public function are_products_hidden( int $term_id ): bool {
		return in_array( $term_id, $this->get_product_excluded_category_ids(), true );
	}

	/**
	 * Set category visibility.
	 */
	public function set_category_hidden( int $term_id, bool $hidden ): void {
		if ( ! $this->is_valid_product_cat( $term_id ) ) {
			return;
		}

		$map             = $this->get_raw_map( self::OPTION_HIDDEN_CATEGORIES );
		$map[ $term_id ] = $hidden ? 'yes' : 'no';
		update_option( self::OPTION_HIDDEN_CATEGORIES, $map );
		$this->flush_cache();
	}

	/**
	 * Set product visibility for a category.
	 */
	public function set_products_hidden( int $term_id, bool $hidden ): void {
		if ( ! $this->is_valid_product_cat( $term_id ) ) {
			return;
		}

		$map             = $this->get_raw_map( self::OPTION_HIDDEN_PRODUCTS );
		$map[ $term_id ] = $hidden ? 'yes' : 'no';
		update_option( self::OPTION_HIDDEN_PRODUCTS, $map );
		$this->flush_cache();
	}

	/**
	 * Bulk save from settings form.
	 *
	 * Only IDs present in $all_term_ids are written so unchecked boxes clear correctly.
	 *
	 * @param list<int> $all_term_ids      All product_cat term IDs known to the form.
	 * @param list<int> $hide_category_ids IDs checked for "hide category".
	 * @param list<int> $hide_product_ids  IDs checked for "hide products".
	 */
	public function save_bulk( array $all_term_ids, array $hide_category_ids, array $hide_product_ids ): void {
		$all_term_ids      = array_values( array_unique( array_map( absint(...), $all_term_ids ) ) );
		$hide_category_ids = array_fill_keys( array_map( absint(...), $hide_category_ids ), true );
		$hide_product_ids  = array_fill_keys( array_map( absint(...), $hide_product_ids ), true );

		$cat_map     = [];
		$product_map = [];

		foreach ( $all_term_ids as $term_id ) {
			if ( $term_id <= 0 || ! $this->is_valid_product_cat( $term_id ) ) {
				continue;
			}
			$cat_map[ $term_id ]     = isset( $hide_category_ids[ $term_id ] ) ? 'yes' : 'no';
			$product_map[ $term_id ] = isset( $hide_product_ids[ $term_id ] ) ? 'yes' : 'no';
		}

		update_option( self::OPTION_HIDDEN_CATEGORIES, $cat_map );
		update_option( self::OPTION_HIDDEN_PRODUCTS, $product_map );
		$this->flush_cache();
	}

	/**
	 * Map product-exclusion term IDs to term_taxonomy_ids for SQL.
	 *
	 * @return list<int>
	 */
	public function get_excluded_term_taxonomy_ids(): array {
		if ( null !== $this->excluded_term_taxonomy_ids ) {
			return $this->excluded_term_taxonomy_ids;
		}

		$term_ids = $this->get_product_excluded_category_ids();
		if ( [] === $term_ids ) {
			return $this->excluded_term_taxonomy_ids = [];
		}

		$tt_ids = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'include'    => $term_ids,
				'hide_empty' => false,
				'fields'     => 'tt_ids',
			]
		);

		if ( is_wp_error( $tt_ids ) || ! is_array( $tt_ids ) ) {
			return $this->excluded_term_taxonomy_ids = [];
		}

		return $this->excluded_term_taxonomy_ids = array_values(
			array_unique( array_map( absint(...), $tt_ids ) )
		);
	}

	/**
	 * Clear request-level caches after writes.
	 */
	public function flush_cache(): void {
		$this->hidden_category_ids        = null;
		$this->product_excluded_ids       = null;
		$this->excluded_term_taxonomy_ids = null;
	}

	/**
	 * Parse option map into a list of active (hidden) term IDs.
	 *
	 * @return list<int>
	 */
	private function parse_active_ids( mixed $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$ids = [];
		foreach ( $raw as $key => $value ) {
			if ( ! is_numeric( $key ) ) {
				// Legacy slug keys from pre-1.3.0 — ignore quietly.
				continue;
			}
			if ( ! $this->is_truthy( $value ) ) {
				continue;
			}
			$ids[] = absint( $key );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Whether a stored value means "hidden".
	 */
	private function is_truthy( mixed $value ): bool {
		return match ( true ) {
			true === $value, 1 === $value, '1' === $value, 'yes' === $value => true,
			default => false,
		};
	}

	/**
	 * Get raw option map as array.
	 *
	 * @return array<int|string, mixed>
	 */
	private function get_raw_map( string $option_name ): array {
		$raw = get_option( $option_name, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/**
	 * Validate term is a product_cat.
	 */
	private function is_valid_product_cat( int $term_id ): bool {
		if ( $term_id <= 0 ) {
			return false;
		}

		$term = get_term( $term_id, 'product_cat' );
		return $term instanceof \WP_Term;
	}
}
