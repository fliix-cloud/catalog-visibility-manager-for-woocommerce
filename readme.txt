=== Catalog Visibility Manager for WooCommerce ===
Contributors: fliix
Tags: woocommerce, product categories, catalog visibility, hide products, storefront
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage catalog visibility for WooCommerce: hide categories and/or products on the storefront using a searchable category tree.

== Description ==

**Catalog Visibility Manager for WooCommerce** lets you control what shoppers see in your shop without deleting products or categories.

Use it for seasonal ranges, wholesale-only categories, work-in-progress catalogs, or any case where admin data should stay but the storefront should not show certain categories or products.

= Visibility controls =

* **Hide category** — remove a product category from storefront term lists and from category display on single product pages
* **Hide products** — exclude products in selected categories from shop loops, collections, and front-end search
* **Independent toggles** — per category, enable either option or both

= Admin =

* One hierarchical category tree under **WooCommerce → Settings → Products**
* Search by name, slug, or parent path
* Parent path labels for duplicate category names
* Filter to show only hidden categories
* Quick toggles on **Products → Categories**

= Compatibility =

* WooCommerce **HPOS** (custom order tables)
* **Storefront** theme product shortcodes
* **WooCommerce Product Add-ons**
* Does not force title-only product search

= Third-party extension =

This is an independent plugin for the WooCommerce platform. It is **not** an official WooCommerce or Automattic product.

= Credits =

GPL refactor inspired by **Hide Categories and Products for WooCommerce** by **N.O.U.S. Open Useful and Simple** (contributors: bastho, leroysabrina, agencenous, enzomangiante). Existing hide settings from that plugin family remain compatible (`wchc_hide_product_cats`, `wchc_hide_products_from_cat`).

= Translations =

English (source) and German (`de_DE`) are included. See `languages/README.md` in the GitHub repository to contribute locales.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install from the WordPress.org plugin directory.
2. Activate **Catalog Visibility Manager for WooCommerce**.
3. Install and activate **WooCommerce**.
4. Open **WooCommerce → Settings → Products** and use the visibility tree section.

== Frequently Asked Questions ==

= Does this require WooCommerce? =

Yes. WooCommerce must be installed and active.

= Does hiding a category also hide its products? =

No. **Hide category** and **Hide products** are separate options.

= Where are settings stored? =

WordPress options `wchc_hide_product_cats` and `wchc_hide_products_from_cat`.

= Does it collect personal data? =

No. Only visibility settings are stored locally in the options table.

= How do I report a security issue? =

Contact the maintainer privately (see Author URI) rather than posting exploit details in public forums.

== Screenshots ==

1. Category tree with search and visibility toggles
2. Parent path under category names
3. Quick toggles on the product categories screen

== Changelog ==

= 2.2.0 =

* Rename to **Catalog Visibility Manager for WooCommerce** (distinctive name; slug `catalog-visibility-manager-for-woocommerce`)
* Text domain updated to match the new slug
* Removed `load_plugin_textdomain()` (WordPress.org language packs)
* Valid Plugin URI; direct-access guards on plugin PHP files
* Development `bin/` excluded from WordPress.org builds via `.distignore`

= 2.1.1 =

* Text domain aligned with prior slug; Tested up to WordPress 7.0; simplified category tree UI

= 2.1.0 =

* Require PHP 8.1+

= 2.0.0 =

* PSR-4 refactor, hierarchical admin tree, security and performance improvements

== Upgrade Notice ==

= 2.2.0 =

New plugin name and slug for WordPress.org. Request slug reservation before upload if prompted. Visibility settings are unchanged.