<?php
/**
 * WooCommerce Products settings subsection for category visibility.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Admin;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Settings\OptionsRepository;

/**
 * Class SettingsSection
 */
class SettingsSection {

	public const SECTION_ID = 'hide-from-categories';
	public const FIELD_TYPE = 'fliix_hcp_category_tree';

	private readonly CategoryTreeRenderer $renderer;

	/**
	 * @param OptionsRepository $options     Options repository.
	 * @param string            $plugin_file Main plugin file path.
	 */
	public function __construct(
		private readonly OptionsRepository $options,
		private readonly string $plugin_file,
	) {
		$this->renderer = new CategoryTreeRenderer( $options );
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_get_sections_products', $this->add_section(...) );
		add_filter( 'woocommerce_get_settings_products', $this->get_settings(...), 10, 2 );
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, $this->render_field(...) );
		add_action(
			'woocommerce_update_options_products_' . self::SECTION_ID,
			$this->save_settings(...)
		);
	}

	/**
	 * Add subsection under Products settings.
	 *
	 * @param array<string, string> $sections Existing sections.
	 * @return array<string, string>
	 */
	public function add_section( array $sections ): array {
		$sections[ self::SECTION_ID ] = __( 'Hide from categories', 'fliix-catalog-visibility-manager-for-woocommerce' );
		return $sections;
	}

	/**
	 * Settings fields for the section.
	 *
	 * @param array<int, array<string, mixed>> $settings        Existing settings.
	 * @param string                           $current_section Current section ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_settings( array $settings, string $current_section ): array {
		if ( self::SECTION_ID !== $current_section ) {
			return $settings;
		}

		$this->enqueue_assets();

		return [
			[
				'title' => __( 'Hide from categories', 'fliix-catalog-visibility-manager-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __(
					'Use one list for all product categories. Choose whether to hide the category itself, hide products in that category, or both. Categories with the same name are distinguished by their parent path.',
					'fliix-catalog-visibility-manager-for-woocommerce'
				),
				'id'    => 'fliix_hcp_options',
			],
			[
				'type' => self::FIELD_TYPE,
				'id'   => 'fliix_hcp_category_tree',
				'desc' => '',
			],
			[
				'type' => 'sectionend',
				'id'   => 'fliix_hcp_options',
			],
		];
	}

	/**
	 * Render custom field type.
	 *
	 * @param array<string, mixed> $field Field config.
	 */
	public function render_field( array $field ): void {
		$this->renderer->render( $field );
	}

	/**
	 * Persist tree checkboxes.
	 */
	public function save_settings(): void {
		// Capability is enforced by WooCommerce settings pages (manage_woocommerce).
		$all_terms     = $this->get_posted_term_ids( 'fliix_hcp_all_terms' );
		$hide_cats     = $this->get_posted_term_ids( 'fliix_hcp_hide_category' );
		$hide_products = $this->get_posted_term_ids( 'fliix_hcp_hide_products' );

		$this->options->save_bulk( $all_terms, $hide_cats, $hide_products );
	}

	/**
	 * Enqueue admin CSS/JS for the tree UI.
	 */
	private function enqueue_assets(): void {
		$base_url = plugin_dir_url( $this->plugin_file );
		$base_dir = plugin_dir_path( $this->plugin_file );

		$css_rel  = 'assets/css/admin-tree.css';
		$js_rel   = 'assets/js/admin-tree.js';
		$css_path = $base_dir . $css_rel;
		$js_path  = $base_dir . $js_rel;
		$version  = defined( 'FLIIX_HCP_VERSION' ) ? FLIIX_HCP_VERSION : '2.1.0';

		wp_enqueue_style(
			'fliix-hcp-admin-tree',
			$base_url . $css_rel,
			[ 'dashicons' ],
			is_file( $css_path ) ? (string) filemtime( $css_path ) : $version
		);

		wp_enqueue_script(
			'fliix-hcp-admin-tree',
			$base_url . $js_rel,
			[],
			is_file( $js_path ) ? (string) filemtime( $js_path ) : $version,
			true
		);
	}

	/**
	 * Read and sanitize a posted list of product category term IDs.
	 *
	 * @return list<int>
	 */
	private function get_posted_term_ids( string $field_name ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC settings form nonce already verified before this save hook runs.
		if ( ! isset( $_POST[ $field_name ] ) || ! is_array( $_POST[ $field_name ] ) ) {
			return [];
		}

		$posted = array_map( 'absint', wp_unslash( $_POST[ $field_name ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return array_values( array_unique( array_filter( $posted ) ) );
	}
}
