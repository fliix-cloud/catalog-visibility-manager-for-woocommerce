<?php
/**
 * Renders the hierarchical category visibility tree in admin settings.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

namespace Fliix\HideCategoriesProducts\Admin;

defined( 'ABSPATH' ) || exit;

use Fliix\HideCategoriesProducts\Settings\OptionsRepository;
use WP_Term;

/**
 * Class CategoryTreeRenderer
 */
class CategoryTreeRenderer {

	/** @var array<int, WP_Term> */
	private array $terms_by_id = [];

	/** @var array<int, list<WP_Term>> */
	private array $children = [];

	public function __construct(
		private readonly OptionsRepository $options,
	) {
	}

	/**
	 * Output the custom WooCommerce settings field.
	 *
	 * @param array<string, mixed> $field Field definition from WC settings API.
	 */
	public function render( array $field ): void {
		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			$terms = [];
		}

		$this->build_maps( $terms );

		$description = $field['desc'] ?? '';
		?>
		<tr valign="top">
			<td colspan="2" class="forminp fliix-hcp-tree-cell">
				<?php if ( $description ) : ?>
					<p class="fliix-hcp-description"><?php echo wp_kses_post( (string) $description ); ?></p>
				<?php endif; ?>

				<div class="fliix-hcp" id="fliix-hcp-tree-root" data-fliix-hcp-tree>
					<div class="fliix-hcp__toolbar">
						<label class="screen-reader-text" for="fliix-hcp-search">
							<?php esc_html_e( 'Search categories', 'fliix-category-product-hide-for-woocommerce' ); ?>
						</label>
						<input
							type="search"
							id="fliix-hcp-search"
							class="fliix-hcp__search regular-text"
							placeholder="<?php esc_attr_e( 'Search categories…', 'fliix-category-product-hide-for-woocommerce' ); ?>"
							autocomplete="off"
						/>
						<div class="fliix-hcp__toolbar-actions">
							<label class="fliix-hcp__filter-hidden">
								<input type="checkbox" class="fliix-hcp__show-hidden-only" />
								<?php esc_html_e( 'Show only hidden', 'fliix-category-product-hide-for-woocommerce' ); ?>
							</label>
						</div>
					</div>

					<div class="fliix-hcp__body">
						<div class="fliix-hcp__header" aria-hidden="true">
							<span class="fliix-hcp__col fliix-hcp__col--name">
								<?php esc_html_e( 'Category', 'fliix-category-product-hide-for-woocommerce' ); ?>
							</span>
							<span class="fliix-hcp__col fliix-hcp__col--toggle" title="<?php esc_attr_e( 'Hide this category from the store', 'fliix-category-product-hide-for-woocommerce' ); ?>">
								<?php esc_html_e( 'Hide category', 'fliix-category-product-hide-for-woocommerce' ); ?>
							</span>
							<span class="fliix-hcp__col fliix-hcp__col--toggle" title="<?php esc_attr_e( 'Hide products in this category from the store', 'fliix-category-product-hide-for-woocommerce' ); ?>">
								<?php esc_html_e( 'Hide products', 'fliix-category-product-hide-for-woocommerce' ); ?>
							</span>
						</div>

						<?php if ( [] === $this->terms_by_id ) : ?>
							<p class="fliix-hcp__empty">
								<?php esc_html_e( 'No product categories found.', 'fliix-category-product-hide-for-woocommerce' ); ?>
							</p>
						<?php else : ?>
							<ul class="fliix-hcp__tree">
								<?php $this->render_level( 0, 0 ); ?>
							</ul>
						<?php endif; ?>

						<p class="fliix-hcp__empty fliix-hcp__no-results" hidden>
							<?php esc_html_e( 'No categories match your search.', 'fliix-category-product-hide-for-woocommerce' ); ?>
						</p>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Index terms and parent→children map.
	 *
	 * @param list<WP_Term|mixed> $terms Terms.
	 */
	private function build_maps( array $terms ): void {
		$this->terms_by_id = [];
		$this->children    = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$id                       = (int) $term->term_id;
			$this->terms_by_id[ $id ] = $term;
			$parent                   = (int) $term->parent;
			$this->children[ $parent ] ??= [];
			$this->children[ $parent ][] = $term;
		}

		foreach ( $this->children as $parent => $list ) {
			usort(
				$list,
				static fn ( WP_Term $a, WP_Term $b ): int => strcasecmp( $a->name, $b->name )
			);
			$this->children[ $parent ] = $list;
		}
	}

	/**
	 * Render one tree level.
	 */
	private function render_level( int $parent_id, int $depth ): void {
		if ( empty( $this->children[ $parent_id ] ) ) {
			return;
		}

		foreach ( $this->children[ $parent_id ] as $term ) {
			$this->render_node( $term, $depth );
		}
	}

	/**
	 * Render a single category row and its children.
	 */
	private function render_node( WP_Term $term, int $depth ): void {
		$term_id       = (int) $term->term_id;
		$has_children  = ! empty( $this->children[ $term_id ] );
		$cat_hidden    = $this->options->is_category_hidden( $term_id );
		$prod_hidden   = $this->options->are_products_hidden( $term_id );
		$is_hidden_any = $cat_hidden || $prod_hidden;
		$breadcrumb    = $this->build_breadcrumb( $term );
		$search_blob   = strtolower( $term->name . ' ' . $breadcrumb . ' ' . $term->slug );

		$li_classes = [ 'fliix-hcp__node' ];
		if ( $is_hidden_any ) {
			$li_classes[] = 'fliix-hcp__node--has-hidden';
		}
		?>
		<li
			class="<?php echo esc_attr( implode( ' ', $li_classes ) ); ?>"
			data-term-id="<?php echo esc_attr( (string) $term_id ); ?>"
			data-search="<?php echo esc_attr( $search_blob ); ?>"
			data-hidden="<?php echo $is_hidden_any ? '1' : '0'; ?>"
			style="--fliix-depth: <?php echo esc_attr( (string) $depth ); ?>"
		>
			<div class="fliix-hcp__row">
				<div class="fliix-hcp__col fliix-hcp__col--name">
					<span class="fliix-hcp__label" title="<?php echo esc_attr( $term->slug ); ?>">
						<span class="fliix-hcp__name"><?php echo esc_html( $term->name ); ?></span>
						<?php if ( $breadcrumb ) : ?>
							<span class="fliix-hcp__path"><?php echo esc_html( $breadcrumb ); ?></span>
						<?php endif; ?>
					</span>
				</div>

				<label class="fliix-hcp__col fliix-hcp__col--toggle">
					<span class="screen-reader-text">
						<?php
						printf(
							/* translators: %s: category name */
							esc_html__( 'Hide category: %s', 'fliix-category-product-hide-for-woocommerce' ),
							esc_html( $term->name )
						);
						?>
					</span>
					<input
						type="checkbox"
						name="fliix_hcp_hide_category[]"
						value="<?php echo esc_attr( (string) $term_id ); ?>"
						<?php checked( $cat_hidden ); ?>
					/>
				</label>

				<label class="fliix-hcp__col fliix-hcp__col--toggle">
					<span class="screen-reader-text">
						<?php
						printf(
							/* translators: %s: category name */
							esc_html__( 'Hide products in: %s', 'fliix-category-product-hide-for-woocommerce' ),
							esc_html( $term->name )
						);
						?>
					</span>
					<input
						type="checkbox"
						name="fliix_hcp_hide_products[]"
						value="<?php echo esc_attr( (string) $term_id ); ?>"
						<?php checked( $prod_hidden ); ?>
					/>
				</label>
			</div>

			<?php /* Track all known term IDs for bulk save (unchecked boxes are omitted from POST). */ ?>
			<input type="hidden" name="fliix_hcp_all_terms[]" value="<?php echo esc_attr( (string) $term_id ); ?>" />

			<?php if ( $has_children ) : ?>
				<ul class="fliix-hcp__children">
					<?php $this->render_level( $term_id, $depth + 1 ); ?>
				</ul>
			<?php endif; ?>
		</li>
		<?php
	}

	/**
	 * Build parent path excluding the term itself, e.g. "Parent › Child".
	 */
	private function build_breadcrumb( WP_Term $term ): string {
		$parts  = [];
		$parent = (int) $term->parent;
		$guard  = 0;

		while ( $parent > 0 && isset( $this->terms_by_id[ $parent ] ) && $guard < 50 ) {
			array_unshift( $parts, $this->terms_by_id[ $parent ]->name );
			$parent = (int) $this->terms_by_id[ $parent ]->parent;
			++$guard;
		}

		return [] === $parts ? '' : implode( ' › ', $parts );
	}
}
