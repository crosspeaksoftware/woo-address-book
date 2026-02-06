# WooCommerce Address Book - Translation Feasibility Analysis

## Executive Summary

The plugin **WooCommerce Address Book v3.0.3** is **already well-prepared for multilingual translation**. The codebase follows WordPress i18n best practices with 87+ translatable strings properly wrapped in WordPress translation functions. A few minor issues were identified that should be addressed for full translation readiness.

**Feasibility verdict: HIGH** - The plugin can be translated with minimal effort.

---

## 1. Current i18n Infrastructure

### Text Domain
- **Text Domain:** `woo-address-book` (declared in plugin header at `woocommerce-address-book.php:10`)
- **Text Domain Loading:** Properly configured in `includes/general.php:22-25` via `load_plugin_textdomain()`
- **Languages Path:** `woo-address-book/languages/` (directory does not yet exist)

### Translation Functions Used

| Function | Count | Purpose |
|----------|-------|---------|
| `__()` | ~82 | Return translated string |
| `_e()` | ~9 | Echo translated string |
| `esc_html__()` | ~15 | Return escaped + translated string |
| `esc_html_e()` | ~7 | Echo escaped + translated string |
| `esc_attr__()` | 2 | Return attribute-safe translated string |
| `_n()` | 2 | Plural forms |
| `wp_localize_script()` | 1 | JavaScript localization |
| **Total** | **~118** | |

### Files with i18n Functions (12 of 17 files)

| File | i18n Calls | Notes |
|------|-----------|-------|
| `includes/settings.php` | ~18 | Admin settings labels and descriptions |
| `includes/api.php` | ~13 | REST API error messages |
| `includes/import.php` | ~10 | Import feedback messages |
| `templates/myaccount/my-address-book.php` | ~21 | Main template (headings, buttons, instructions) |
| `includes/ajax.php` | ~6 | AJAX response messages |
| `includes/address-book.php` | ~6 | Checkout labels, success notices |
| `includes/validation.php` | ~5 | Validation error messages |
| `includes/nickname.php` | ~5 | Address nickname field labels |
| `templates/myaccount/add-address-button.php` | ~4 | Button labels |
| `includes/general.php` | ~4 | JS localized strings (delete confirm, default label) |
| `includes/export.php` | ~2 | Export button labels |
| `woocommerce-address-book.php` | ~1 | WooCommerce dependency notice |

---

## 2. Issues Found

### 2.1 CRITICAL - Missing `languages/` Directory

The `load_plugin_textdomain()` call references `languages/` but the directory does not exist. A `.pot` template file must be generated and placed there.

**Impact:** No translation can work without this directory and the `.pot` file.

### 2.2 MODERATE - `desc_tip` Strings Not Translated (4 occurrences)

In `includes/settings.php`, four `desc_tip` values contain hardcoded English strings without `__()` wrapping:

- **Line 69:** `'desc_tip' => 'When checked, the billing address book will default to "Add New Address" during checkout instead of the default address...'`
- **Line 78:** `'desc_tip' => 'When checked, you can set a nickname for the billing address at checkout...'`
- **Line 107:** `'desc_tip' => 'When checked, the shipping address book will default to "Add New Address" during checkout instead of the default address...'`
- **Line 116:** `'desc_tip' => 'When checked, you can set a nickname for the shipping address at checkout...'`

These tooltips are visible to admin users and should be wrapped in `__( '...', 'woo-address-book' )`.

### 2.3 MINOR - Wrong Text Domain on Some Strings (12 occurrences)

Several strings use the `'woocommerce'` text domain instead of `'woo-address-book'`. While this is intentional (reusing WooCommerce core translations), it creates a dependency on WooCommerce's translation being available for the user's locale:

- `includes/settings.php:86` - Billing Address Book Limit description
- `includes/settings.php:124` - Shipping Address Book Limit description
- `includes/api.php:308,326,343,361` - REST API permission messages
- `includes/address-book.php:535` - "Address changed successfully."
- `includes/validation.php:49,64,67,75,83` - Validation messages

**Note:** This is a common WordPress pattern and not necessarily a bug, but translators should be aware that these strings are managed by WooCommerce core.

### 2.4 INFO - No `.pot` File Available

No `.pot` (Portable Object Template) file exists in the repository. This file is the starting point for translators and must be generated.

---

## 3. JavaScript Localization

**Status: Properly implemented.**

All user-facing JavaScript strings are passed through `wp_localize_script()` in `includes/general.php:37-49`:

```php
wp_localize_script( 'woo-address-book', 'woo_address_book', array(
    'delete_confirmation' => __( 'Are you sure you want to delete this address?', 'woo-address-book' ),
    'default_text'        => __( 'Default', 'woo-address-book' ),
    // ... nonces and settings (non-translatable)
));
```

The JavaScript file (`assets/js/scripts.js`) does not contain any hardcoded user-facing strings - all text comes from the localized PHP object.

---

## 4. Template Overridability

Templates are loaded via `wc_get_template()`, which means themes can override them:

- `templates/myaccount/my-address-book.php`
- `templates/myaccount/add-address-button.php`

**Translation impact:** Theme overrides could introduce untranslated strings. This is a known WooCommerce ecosystem pattern and not specific to this plugin.

---

## 5. Steps Required for Full Translation Support

### Step 1: Create the `languages/` directory
```
mkdir languages/
```

### Step 2: Fix untranslated `desc_tip` strings in `includes/settings.php`
Wrap 4 tooltip strings in `__()` with the `'woo-address-book'` text domain.

### Step 3: Generate the `.pot` file
Using WP-CLI or a tool like `makepot`:
```
wp i18n make-pot . languages/woo-address-book.pot
```

### Step 4: Create `.po`/`.mo` files for target languages
For each target language (e.g., Italian):
```
cp languages/woo-address-book.pot languages/woo-address-book-it_IT.po
# Translate strings in the .po file
msgfmt languages/woo-address-book-it_IT.po -o languages/woo-address-book-it_IT.mo
```

### Step 5 (Optional): Submit translations to translate.wordpress.org
If the plugin is hosted on wordpress.org, translations can be crowdsourced through the official GlotPress system.

---

## 6. Estimated Translation Effort

| Metric | Value |
|--------|-------|
| Total unique translatable strings (estimated) | ~75-85 |
| Strings with the `woo-address-book` domain | ~75 |
| Strings borrowed from `woocommerce` domain | ~12 |
| Strings missing translation wrappers | 4 (`desc_tip` tooltips) |
| JavaScript strings requiring translation | 2 |
| Plural forms (`_n()`) | 2 |
| Strings with placeholders (`sprintf`) | ~6 |

---

## 7. Conclusion

The plugin has a **solid internationalization foundation**. The development team has consistently applied WordPress i18n best practices across all PHP and JavaScript files. Only 4 admin tooltip strings need to be wrapped in translation functions.

To enable multilingual support:
1. Fix the 4 `desc_tip` strings (minimal code change)
2. Create the `languages/` directory
3. Generate the `.pot` file
4. Translate and compile `.po`/`.mo` files for target languages

The plugin is ready for translation with minimal remediation work.
