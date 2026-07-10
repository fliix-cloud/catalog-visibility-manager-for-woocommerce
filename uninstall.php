<?php
/**
 * Uninstall cleanup.
 *
 * @package Fliix\HideCategoriesProducts
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

delete_option( 'wchc_hide_product_cats' );
delete_option( 'wchc_hide_products_from_cat' );
