# Agent Rules — Region & Land Price Tracker

> This file governs every agent (main and sub) working on this plugin.
> Read this file completely before writing a single line of code.
> These rules are not suggestions. They are hard constraints.

---

## WHO DOES WHAT

### Main Agent

- Reads `plan.md` and `agent.md` at the start of every session
- Creates sub-agents and assigns them exactly one phase from `plan.md`
- Passes each sub-agent: the phase description, the reference folder path, and this `agent.md`
- **Never writes plugin code directly**
- Reviews sub-agent output before marking a phase done
- Keeps a simple log: phase number, status (done / failed / in-progress), any documented decisions

### Sub-Agents

- Each sub-agent owns exactly **one phase**
- Reads the relevant old plugin files listed in their phase **before writing anything**
- Writes code, tests it, fixes it — does not hand back broken work
- Documents any ambiguity decision at the bottom of this file under [Documented Decisions](#documented-decisions)

---

## LANGUAGE

- All code, comments, variable names, function names, class names: **English only**
- No Arabic anywhere in source files
- User-facing strings (labels, error messages) go through `__()` with text domain `rl-price-tracker` — translation is a separate concern

---

## DO

### Architecture

- ✅ Follow the file and folder structure in `plan.md §3` exactly
- ✅ One class per file, filename matches class name
- ✅ Every PHP file starts with `if (!defined('ABSPATH')) exit;`
- ✅ Use PSR-4 namespace `RLPTNamespace\` matching `composer.json`
- ✅ Use `$wpdb->prepare()` for every single database query, no exceptions
- ✅ Use `wp_send_json_success()` / `wp_send_json_error()` for all AJAX responses
- ✅ Verify nonce with `check_ajax_referer()` at the top of every AJAX method
- ✅ Check capability with `current_user_can('rlpt_access')` on every admin AJAX method
- ✅ Escape all output: `esc_html()`, `esc_attr()`, `wp_json_encode()`
- ✅ Sanitize all input: `sanitize_text_field()`, `sanitize_title()`, `intval()`, `floatval()`

### CSS & JS

- ✅ Plain `.css` files only — no SCSS, no build tools, no Gulp
- ✅ Plain `.js` files — no TypeScript, no bundlers
- ✅ Enqueue via `wp_enqueue_script()` / `wp_enqueue_style()` — never inline except Chart.js CDN
- ✅ Localize JS data with `wp_localize_script()` — never hardcode ajax_url or nonces in JS files
- ✅ Chart.js loaded from CDN: `https://cdn.jsdelivr.net/npm/chart.js`
- ✅ Select2 loaded from CDN (admin only)
- ✅ SweetAlert2 loaded from CDN (admin only)

### Code style

- ✅ Functions and methods do one thing
- ✅ If a function is longer than 40 lines, split it
- ✅ Name things clearly: `save_material_prices()` not `save()` alone inside a global scope
- ✅ Keep JS event handlers small — extract logic into named functions
- ✅ Use `const` and `let` in JS, never `var`

### Testing (per phase)

- ✅ Manually verify every AJAX action works before marking phase done
- ✅ Check browser console for JS errors after every phase
- ✅ Check PHP error log after every phase
- ✅ If tests fail → fix before moving to the next phase, no exceptions

### Ambiguity

- ✅ If something in `plan.md` is unclear → make the **simplest YAGNI choice**
- ✅ Document that decision immediately under [Documented Decisions](#documented-decisions) at the bottom of this file
- ✅ Format: `Phase N | Decision: what was unclear | Choice made | Reason`

---

## DO NOT

### Scope

- ❌ Do not build anything not described in `plan.md`
- ❌ Do not add extra fields, columns, or options "just in case"
- ❌ Do not add export/import CSV (not in plan)
- ❌ Do not add a General settings tab (not in plan)
- ❌ Do not add pagination to admin lists unless plan says so
- ❌ Do not add search/filter to admin lists unless plan says so
- ❌ Do not add user roles settings UI (capability is fixed: administrator + editor)

### Code

- ❌ Do not use `echo` directly in class methods that render HTML — use `ob_start()` / `ob_get_clean()` and return
- ❌ Do not use `extract()` anywhere
- ❌ Do not use `$_REQUEST` — use `$_POST` or `$_GET` explicitly
- ❌ Do not write raw SQL without `$wpdb->prepare()`
- ❌ Do not use `die()` — use `wp_die()` or `wp_send_json_error()` then `wp_die()`
- ❌ Do not register the same hook twice
- ❌ Do not enqueue assets on every page — only on the plugin's admin page or frontend when shortcode is present
- ❌ Do not hardcode table names — always use `$wpdb->prefix . 'rlpt_...'`
- ❌ Do not store prices as strings — always `DECIMAL(15,2)` in DB and `floatval()` in PHP

### CSS & JS

- ❌ No SCSS
- ❌ No Gulp
- ❌ No webpack, vite, rollup, or any bundler
- ❌ No inline `<style>` blocks in PHP templates
- ❌ No inline `<script>` blocks in PHP templates (except the shortcode wrapper HTML — follow old plugin pattern exactly)
- ❌ No `console.log` left in production code

### Agents

- ❌ Main agent must not write plugin PHP or JS code directly
- ❌ Sub-agents must not start coding before reading their reference files from the old plugin
- ❌ Sub-agents must not skip phases or reorder them
- ❌ Sub-agents must not modify files owned by a previous phase without flagging it to the main agent

### plan.md

- ❌ change or update on plan.md file

---

## BEST PRACTICES

### Reading the old plugin

Before writing any file, read the equivalent file in the old plugin.
The mapping is:

| New file                  | Read from old plugin                                      |
| ------------------------- | --------------------------------------------------------- |
| `RegionsTab.php`          | `settings/tabs/taxonomy.php`                              |
| `MaterialsTab.php`        | `settings/tabs/category.php`                              |
| `MaterialPricesTab.php`   | `settings/tabs/items.php`                                 |
| `LandPricesTab.php`       | `settings/tabs/items.php`                                 |
| `RegionsAjax.php`         | `classes/AjaxHandler.php` (taxonomy methods)              |
| `MaterialsAjax.php`       | `classes/AjaxHandler.php` (category methods)              |
| `MaterialPricesAjax.php`  | `classes/AjaxHandler.php` (items methods)                 |
| `AdminPage.php`           | `settings/SettingsPage.php`                               |
| `MaterialsShortcode.php`  | `classes/ShortcodeManager.php`                            |
| `regions-ajax.js`         | `assets/js/admin/taxonomies-ajax-actions.js`              |
| `materials-ajax.js`       | `assets/js/admin/categories-ajax-actions.js`              |
| `material-prices-ajax.js` | `assets/js/admin/items-ajax-actions.js` + `items-page.js` |
| `rlpt-materials.js`       | `assets/js/frontend/price-tracker-table.js`               |
| `rlpt-land.js`            | `assets/js/frontend/price-tracker-insight.js`             |
| `shortcode-gen.js`        | `assets/js/admin/shortcode-maker.js`                      |

**Understand the pattern, then write your own version.** Do not copy-paste old code and rename variables. The old plugin uses different domain concepts (taxonomy/category/buy/sell) — rewrite clean for regions/materials/price.

---

### Phase handoff checklist

Before a sub-agent hands back a phase, it must confirm:

- [ ] All files listed in the phase exist and are non-empty
- [ ] No PHP fatal errors (check error log)
- [ ] No JS console errors (check browser dev tools)
- [ ] AJAX actions return correct JSON structure as described in `plan.md §8`
- [ ] Nonces verified, capabilities checked
- [ ] All inputs sanitized, all outputs escaped
- [ ] No hardcoded table names, URLs, or nonces in JS
- [ ] Any ambiguity decisions documented below

---

### Chart rules (enforce in every frontend phase)

- Always 6 data points — monthly averages — last 6 months with data
- If fewer than 6 months have data, show what exists — no gap filling
- No date range inputs on charts — fully automatic
- Chart type: line, tension 0.4, no point radius
- Month label format: `"Nov 24"` (short month + 2-digit year)
- Mini charts (materials): one canvas per row, always visible, no toggle
- Land chart: one canvas, two lines (Shell & Core + Fully Finished)

### Indicator rules (enforce in every frontend phase)

- Compare current calendar month avg vs previous calendar month avg
- If no previous month data → show `—` (neutral dash, no class)
- If current > previous → `↑` with class `up` (green)
- If current < previous → `↓` with class `down` (red)
- If equal → `—` with class `neutral`

---

### SQL patterns to use

**Monthly average for chart (6 months):**

```sql
SELECT
    DATE_FORMAT(date, '%Y-%m') AS month,
    AVG(price) AS avg_price
FROM {$wpdb->prefix}rlpt_material_prices
WHERE region_id = %d
  AND material_id = %d
  AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(date, '%Y-%m')
ORDER BY month ASC
LIMIT 6;
```

**Indicator (current vs previous month avg):**

```sql
SELECT
    DATE_FORMAT(date, '%Y-%m') AS month,
    AVG(price) AS avg_price
FROM {$wpdb->prefix}rlpt_material_prices
WHERE region_id = %d
  AND material_id = %d
  AND date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
  AND date <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
GROUP BY month
ORDER BY month ASC;
```

Adapt table name and price column for land prices queries.

---

### AJAX JS pattern to use

```javascript
const formData = new FormData();
formData.append("action", "rlpt_action_name");
formData.append("nonce", RLPTAjax.nonce);
formData.append("region_id", regionId);

const response = await fetch(RLPTAjax.ajax_url, {
  method: "POST",
  body: formData,
});
const json = await response.json();

if (json.success) {
  // handle data
} else {
  // show error: json.data.message
}
```

Never use jQuery `$.ajax` for frontend shortcode JS. Use native `fetch()`.
Admin JS may use jQuery (it is always available in WP admin).

---

### PHP AJAX method pattern to use

```php
public function save(): void {
    check_ajax_referer('rlpt_material_prices_nonce', 'nonce');

    if (!current_user_can('rlpt_access')) {
        wp_send_json_error(['message' => __('No permission.', 'rl-price-tracker')]);
        wp_die();
    }

    $region_id = intval($_POST['region_id'] ?? 0);
    if (!$region_id) {
        wp_send_json_error(['message' => __('Invalid region.', 'rl-price-tracker')]);
        wp_die();
    }

    global $wpdb;
    // ... query with $wpdb->prepare() ...

    wp_send_json_success(['message' => __('Saved.', 'rl-price-tracker')]);
    wp_die();
}
```

### Reading the old plugin

> The old plugin code is available at: `\price-tracker`
> Read the relevant files from there before writing any new file.
> Understand the pattern, then write your own clean version.

---

## DOCUMENTED DECISIONS

> Sub-agents: append decisions here as they happen.
> Format: `Phase N | Ambiguity | Choice | Reason`

| Phase | Ambiguity | Choice | Reason |
|---|---|---|---|---|
| Rebuild | Plugin main file was `sanad-calck.php`, plan dir was `sanad-calck/` | Renamed to `sanad-tracker.php` as main file, deleted `sanad-calck.php` | Folder was renamed to `sanad-tracker`, main file should match |
| Rebuild | Shortcode Generator tab allowed custom attributes for unlimited shortcode variants | Removed Shortcode Generator entirely | User wants just 2 fixed shortcodes (`[sanad_materials]`, `[sanad_land]`), not a generator |
| Rebuild | Land table header had empty `<th></th>` causing alignment issues | Added "Type" header label via i18n | Empty header breaks fixed table layout alignment; plan.md specifies `<th>Type</th>` |
| Rebuild | Frontend JS used `querySelector` — only first shortcode instance worked | Changed to `querySelectorAll` with per-instance scope | User wants unlimited tables on the same page |
| Rebuild | Shortcode Generator tab was deleted | Restored as "General" tab, made first tab | User wants a simple place to copy the 2 shortcodes |
