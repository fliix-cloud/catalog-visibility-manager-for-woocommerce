<?php
/**
 * Product category list table visibility columns and secure toggles.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Admin;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Settings\OptionsRepository;
use WP_Term;

/**
 * Class TaxonomyColumns
 */
class TaxonomyColumns {

	public const ACTION = 'fliix_hcp_toggle';

	public function __construct(
		private readonly OptionsRepository $options,
	) {
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'manage_edit-product_cat_columns', $this->add_columns(...) );
		add_filter( 'manage_product_cat_custom_column', $this->render_column(...), 10, 3 );
		add_action( 'admin_post_' . self::ACTION, $this->handle_toggle(...) );
	}

	/**
	 * Insert visibility columns after Name.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_columns( array $columns ): array {
		$new = [];

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'name' === $key ) {
				$new['fliix_hcp_visibility']         = __( 'Category', 'fliix-category-product-hide-for-woocommerce' );
				$new['fliix_hcp_product_visibility'] = __( 'Products', 'fliix-category-product-hide-for-woocommerce' );
			}
		}

		if ( ! isset( $new['fliix_hcp_visibility'] ) ) {
			$new['fliix_hcp_visibility']         = __( 'Category', 'fliix-category-product-hide-for-woocommerce' );
			$new['fliix_hcp_product_visibility'] = __( 'Products', 'fliix-category-product-hide-for-woocommerce' );
		}

		return $new;
	}

	/**
	 * Render toggle control for a column.
	 *
	 * @param string     $content     Column content.
	 * @param string     $column_name Column key.
	 * @param int|string $term_id     Term ID.
	 */
	public function render_column( string $content, string $column_name, int|string $term_id ): string {
		$term_id = absint( $term_id );

		return match ( $column_name ) {
			'fliix_hcp_visibility'         => $this->render_toggle( $term_id, 'term', $this->options->is_category_hidden( $term_id ) ),
			'fliix_hcp_product_visibility' => $this->render_toggle( $term_id, 'products', $this->options->are_products_hidden( $term_id ) ),
			default                        => $content,
		};
	}

	/**
	 * Build a POST form toggle (state-changing action).
	 *
	 * @param int    $term_id   Term ID.
	 * @param string $target    term|products.
	 * @param bool   $is_hidden Currently hidden.
	 */
	private function render_toggle( int $term_id, string $target, bool $is_hidden ): string {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			$icon  = $is_hidden ? 'dashicons-hidden' : 'dashicons-visibility';
			$label = $is_hidden
				? __( 'Hidden', 'fliix-category-product-hide-for-woocommerce' )
				: __( 'Shown', 'fliix-category-product-hide-for-woocommerce' );

			return sprintf(
				'<span class="dashicons %1$s" title="%2$s"><span class="screen-reader-text">%2$s</span></span>',
				esc_attr( $icon ),
				esc_attr( $label )
			);
		}

		$action_type = $is_hidden ? 'show' : 'hide';
		$icon        = $is_hidden ? 'dashicons-hidden' : 'dashicons-visibility';
		$label       = $is_hidden
			? __( 'Hidden — click to show', 'fliix-category-product-hide-for-woocommerce' )
			: __( 'Shown — click to hide', 'fliix-category-product-hide-for-woocommerce' );

		ob_start();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fliix-hcp-toggle-form" style="display:inline;">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
			<input type="hidden" name="term_id" value="<?php echo esc_attr( (string) $term_id ); ?>" />
			<input type="hidden" name="action_target" value="<?php echo esc_attr( $target ); ?>" />
			<input type="hidden" name="action_type" value="<?php echo esc_attr( $action_type ); ?>" />
			<?php wp_nonce_field( self::ACTION . '_' . $term_id, '_wpnonce', false ); ?>
			<button type="submit" class="button-link fliix-hcp-toggle-btn" title="<?php echo esc_attr( $label ); ?>">
				<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
			</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Handle secure toggle POST.
	 */
	public function handle_toggle(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to change these settings.', 'fliix-category-product-hide-for-woocommerce' ),
				esc_html__( 'Forbidden', 'fliix-category-product-hide-for-woocommerce' ),
				[ 'response' => 403 ]
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified below.
		$term_id = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;
		$nonce   = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$type    = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';
		$target  = isset( $_POST['action_target'] ) ? sanitize_text_field( wp_unslash( $_POST['action_target'] ) ) : 'term';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if (
			$term_id <= 0
			|| ! in_array( $type, [ 'hide', 'show' ], true )
			|| ! in_array( $target, [ 'term', 'products' ], true )
			|| ! wp_verify_nonce( $nonce, self::ACTION . '_' . $term_id )
		) {
			wp_die(
				esc_html__( 'Invalid request.', 'fliix-category-product-hide-for-woocommerce' ),
				esc_html__( 'Error', 'fliix-category-product-hide-for-woocommerce' ),
				[ 'response' => 400 ]
			);
		}

		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term instanceof WP_Term ) {
			wp_die(
				esc_html__( 'Category not found.', 'fliix-category-product-hide-for-woocommerce' ),
				esc_html__( 'Error', 'fliix-category-product-hide-for-woocommerce' ),
				[ 'response' => 404 ]
			);
		}

		$hidden = 'hide' === $type;

		if ( 'term' === $target ) {
			$this->options->set_category_hidden( $term_id, $hidden );
		} else {
			$this->options->set_products_hidden( $term_id, $hidden );
		}

		$redirect = wp_get_referer()
			?: admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' );

		wp_safe_redirect( $redirect );
		exit;
	}
}
