# Catalog Visibility Manager for WooCommerce

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![Requires PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-8892BF)](https://www.php.net/)
[![Requires WordPress](https://img.shields.io/badge/WordPress-%3E%3D5.8-21759B)](https://wordpress.org/)
[![Requires WooCommerce](https://img.shields.io/badge/WooCommerce-%3E%3D5.0-96588A)](https://woocommerce.com/)
[![PSR-4](https://img.shields.io/badge/PSR--4-compliant-green)](https://www.php-fig.org/psr/psr-4/)

**Hide product categories and/or the products inside them from your WooCommerce storefront** — without deleting data, and with independent controls for each.

Ideal for seasonal catalogs, B2B-only ranges, draft collections, or any shop that needs certain categories or products out of public view while staying in the admin.

**Maintainer:** [fliix - Marc Werner](https://www.fliix.cloud) — independent third-party extension **for** WooCommerce (not affiliated with Automattic or WooCommerce).

> **Credit:** GPL refactor inspired by [Hide Categories and Products for WooCommerce](https://wordpress.org/plugins/hide-categories-products-woocommerce/) by **N.O.U.S. Open Useful and Simple**.

---

## Features

| Feature | Description |
|--------|-------------|
| **Hide category** | Remove a product category from storefront term lists and single-product category display |
| **Hide products** | Exclude products in selected categories from shop loops, product queries, and search |
| **Independent toggles** | Hide the category, its products, or both — separately per category |
| **Hierarchical tree UI** | One clear list under **WooCommerce → Settings → Products → Hide from categories** |
| **Search** | Filter categories by name, slug, or parent path |
| **Parent paths** | Disambiguate categories that share the same name under different parents |
| **Quick toggles** | Visibility columns on **Products → Categories** |
| **Storefront compatible** | Works with Storefront product shortcodes |
| **Product Add-ons aware** | Does not break WooCommerce Product Add-ons term lookup |
| **HPOS compatible** | Declares compatibility with WooCommerce High-Performance Order Storage |
| **Clean architecture** | PSR-4 namespace `Fliix\HideCategoriesProducts`, request-level option caching |

---

## Requirements

- WordPress **5.8+**
- PHP **8.1+**
- [WooCommerce](https://woocommerce.com/) **5.0+**

---

## Installation

### From a release ZIP

1. Download the latest release ZIP from GitHub.
2. In WordPress go to **Plugins → Add New → Upload Plugin**.
3. Activate **Catalog Visibility Manager for WooCommerce**.
4. Confirm WooCommerce is active.

### From source (development)

```bash
# Clone into your plugins directory
cd wp-content/plugins
git clone https://github.com/YOUR_ORG/hide-categories-products-woocommerce.git

# Optional: Composer autoload (plugin ships its own PSR-4 autoloader)
cd hide-categories-products-woocommerce
composer install --no-dev
```

Activate the plugin in **Plugins**. The main bootstrap file is:

```text
catalog-visibility-manager-for-woocommerce.php
```

---

## How to use

1. Open **WooCommerce → Settings → Products**.
2. Select the **Hide from categories** subsection.
3. In the category tree:
   - **Hide category** — hides the category on the storefront  
   - **Hide products** — hides products belonging to that category  
4. Use search or “Show only hidden” as needed.
5. Click **Save changes**.

**Quick access:** on **Products → Categories**, use the **Category** and **Products** columns to toggle visibility without opening settings.

### Behaviour notes

- Hiding a **category** does **not** automatically hide its products.
- Hiding **products** does **not** automatically hide the category (the category page may appear empty).
- Existing settings from the original 1.x plugin are preserved (same option keys).

---

## Screenshots

> Add images under `.wordpress-org/` or `assets/` and reference them when publishing to WordPress.org.

1. Hierarchical category tree with search and dual toggles  
2. Parent path under category names for disambiguation  
3. Quick visibility toggles on the product categories list table  

---

## Configuration (developers)

Settings are stored in WordPress options (unchanged from the original plugin for upgrade safety):

| Option key | Purpose |
|------------|---------|
| `wchc_hide_product_cats` | Map of term ID → `yes` / `no` for hidden categories |
| `wchc_hide_products_from_cat` | Map of term ID → `yes` / `no` for product exclusion |

### Plugin structure

```text
catalog-visibility-manager-for-woocommerce.php   # Bootstrap
src/
  Plugin.php                         # Orchestrator
  Autoloader.php                     # PSR-4 autoloader
  Settings/OptionsRepository.php     # Cached read/write
  Admin/                             # Settings UI + taxonomy columns
  Frontend/                          # Storefront hiding
  Compatibility/                     # HPOS, Storefront, Product Add-ons
assets/css|js/                       # Admin tree UI
uninstall.php                        # Removes options on uninstall
```

**Namespace:** `Fliix\HideCategoriesProducts\`  
**Text domain / WordPress.org slug:** `fliix-category-product-hide-for-woocommerce`

---

## Translations (i18n)

Source strings are **English**. Bundled locales:

| Locale | Files |
|--------|--------|
| Template | `languages/fliix-category-product-hide-for-woocommerce.pot` |
| English (US) | `…-en_US.po` / `.mo` (reference catalog) |
| German | `…-de_DE.po` / `.mo` |

WordPress loads `languages/fliix-category-product-hide-for-woocommerce-{locale}.mo` based on **Settings → General → Site Language**.

### Contribute a language via Pull Request

1. Copy the `.pot` to `languages/fliix-category-product-hide-for-woocommerce-{locale}.po` (e.g. `fr_FR`).
2. Translate all `msgstr` values (Poedit recommended).
3. Compile binary catalogs:

   ```bash
   php bin/compile-mo.php
   ```

4. Open a PR with **both** the `.po` and `.mo` files.

Full contributor guide: [`languages/README.md`](languages/README.md).

---

## Privacy & security

- No external HTTP calls, tracking, or telemetry.
- No personal data is collected.
- Admin actions require `manage_woocommerce` and nonces.
- State-changing toggles use POST.
- SQL exclusions use sanitized integer IDs and correct `term_taxonomy_id` mapping.

If you find a vulnerability, please open a private security advisory on GitHub (or contact the maintainer) instead of a public issue.

---

## Contributing

Contributions are welcome.

1. Fork the repository  
2. Create a feature branch (`git checkout -b feature/my-improvement`)  
3. Commit with a clear message  
4. Open a pull request describing the change and how to test it  

Please keep PRs focused, follow existing code style (PSR-4, PHP 8.1+, WordPress escaping/sanitizing), and avoid unrelated refactors.

### Local checks

```bash
# PHP syntax on all sources
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

---

## Roadmap ideas

- Bulk “hide all children” actions  
- REST/AJAX tree for very large catalogs (2k+ categories)  
- WPML / Polylang term mapping  
- Automated integration tests with WooCommerce  

---

## Credits

| Role | Credit |
|------|--------|
| **Author & maintainer** | [fliix – Marc Werner](https://www.fliix.cloud) |
| **Original GPL inspiration** | [N.O.U.S. Open Useful and Simple](https://apps.avecnous.eu/) — bastho, leroysabrina, agencenous, enzomangiante |

---

## License

This plugin is free software, licensed under the **GNU General Public License v2 or later**.

See [LICENSE](LICENSE) and <https://www.gnu.org/licenses/gpl-2.0.html>.

**WooCommerce** is a trademark of Automattic Inc. This project is **not** affiliated with or endorsed by Automattic or WooCommerce.  
**N.O.U.S.** and the original plugin name remain the property of their respective owners; they are mentioned only for GPL attribution.

---

## Support

- **Issues & feature requests:** GitHub Issues  
- **WordPress.org support forum:** (link after directory submission)  

If this plugin helps your shop, a star on GitHub is appreciated.
