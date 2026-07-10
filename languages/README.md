# Translations

This plugin ships language files so communities can improve and add locales via **pull requests**.

| File | Purpose |
|------|---------|
| `fliix-category-product-hide-for-woocommerce.pot` | **Template** – all source strings (English). Start new languages from this file. |
| `fliix-category-product-hide-for-woocommerce-en_US.po` | English (source reference catalog) |
| `fliix-category-product-hide-for-woocommerce-en_US.mo` | Compiled English (optional; site language `en_US` usually uses PHP source strings) |
| `fliix-category-product-hide-for-woocommerce-de_DE.po` | German (editable) |
| `fliix-category-product-hide-for-woocommerce-de_DE.mo` | German (compiled – required at runtime) |

**Text domain:** `fliix-category-product-hide-for-woocommerce`  
**Domain path:** `/languages`

---

## How WordPress picks a language

1. Site language is set under **Settings → General** (e.g. `Deutsch` → `de_DE`).
2. The plugin loads `languages/fliix-category-product-hide-for-woocommerce-{locale}.mo`.
3. If no matching file exists, English strings from the PHP source are shown.

---

## Add a new language (for pull requests)

### 1. Copy the template

```bash
# Example: French (France)
cp languages/fliix-category-product-hide-for-woocommerce.pot \
   languages/fliix-category-product-hide-for-woocommerce-fr_FR.po
```

### 2. Edit the header

In the new `.po` file set at least:

```po
"Language: fr_FR\n"
"Language-Team: French\n"
"Last-Translator: Your Name <you@example.com>\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\n"
```

Use the correct [WordPress locale code](https://make.wordpress.org/polyglots/teams/) (e.g. `de_DE`, `de_CH`, `fr_FR`, `es_ES`, `nl_NL`).

### 3. Translate every `msgstr`

```po
msgid "Hide category"
msgstr "Cacher la catégorie"
```

Keep:

- Placeholders like `%s` in the same order  
- HTML-free plain text (unless the original contains markup)  
- Similar length when possible (admin UI layout)

Editors that help:

- [Poedit](https://poedit.net/) (free)
- [Loco Translate](https://wordpress.org/plugins/loco-translate/) (inside WP)
- VS Code “gettext” extensions

### 4. Compile `.mo`

WordPress loads **`.mo`**, not `.po`.

From the plugin root (PHP only, no gettext install required):

```bash
php bin/compile-mo.php
# or only one file:
php bin/compile-mo.php languages/fliix-category-product-hide-for-woocommerce-fr_FR.po
```

If you have GNU gettext installed:

```bash
msgfmt -o languages/fliix-category-product-hide-for-woocommerce-fr_FR.mo \
       languages/fliix-category-product-hide-for-woocommerce-fr_FR.po
```

### 5. Open a pull request

Include both:

- `languages/fliix-category-product-hide-for-woocommerce-{locale}.po`
- `languages/fliix-category-product-hide-for-woocommerce-{locale}.mo`

Describe the locale and any strings you were unsure about.

---

## Update existing translations

When new English strings are added to the plugin:

1. Maintainers refresh `fliix-category-product-hide-for-woocommerce.pot`.
2. Translators merge new strings into their `.po` (Poedit “Update from POT”, or manually).
3. Recompile `.mo` and open a PR.

---

## Improve German or English

- **German:** edit `fliix-category-product-hide-for-woocommerce-de_DE.po`, run `php bin/compile-mo.php`, PR both files.
- **English source:** change the string in PHP (`__()`, `esc_html__()`, …) **and** update `.pot` + all `.po` files. English UI text lives in code; `en_US.po` is a mirror for translators.

---

## File naming rules

```text
fliix-category-product-hide-for-woocommerce-{locale}.po
fliix-category-product-hide-for-woocommerce-{locale}.mo
```

| Correct | Incorrect |
|---------|-----------|
| `…-de_DE.mo` | `de_DE.mo` (missing text domain) |
| `…-de_DE.mo` | `…-de.mo` (incomplete locale) |
| `…-pt_BR.mo` | `…-pt-br.mo` (wrong separator) |

---

## Tips for quality

- Prefer clear shop / WooCommerce wording over literal calques.
- Match terminology used in the German/English WooCommerce admin when possible (*Kategorie*, *Produkte*, *Shop* …).
- Do not translate the plugin’s technical option keys or CSS class names.
- Test with **Settings → General → Site Language** set to your locale.
