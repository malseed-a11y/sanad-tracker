# Region & Land Price Tracker — Plugin Plan

> **YAGNI strictly enforced.** Build only what is described here, nothing more.
> **No Gulp. No SCSS. Plain CSS files only.**
> **AI must never code directly — always delegate to sub-agents.**
> **Build horizontally: each feature complete (front + back) before moving to the next.**

---

## TABLE OF CONTENTS

1. [Plugin Overview](#1-plugin-overview)
2. [Database Schema](#2-database-schema)
3. [Directory & File Structure](#3-directory--file-structure)
4. [Admin Tabs — What Each Does](#4-admin-tabs--what-each-does)
5. [Frontend Shortcodes](#5-frontend-shortcodes)
6. [Smart Chart Logic](#6-smart-chart-logic)
7. [Indicator Logic](#7-indicator-logic)
8. [AJAX Endpoints Reference](#8-ajax-endpoints-reference)
9. [Security Rules](#9-security-rules)
10. [AI Agent Execution Plan](#10-ai-agent-execution-plan)

---

## 1. PLUGIN OVERVIEW

**Plugin Name:** Sanad Tracker
**Namespace:** `SanadTracker`
**Capability:** `sanad_tracker_access`
**Text Domain:** `sanad-tracker`
**PHP:** 7.4+
**WordPress:** 6.0+
**No build tools.** CSS is plain `.css` files. JS is plain `.js` files.

### What it tracks

Two independent data types:

| Type                 | What is stored                                            |
| -------------------- | --------------------------------------------------------- |
| **Material Prices**  | Region + Material + single price + date                   |
| **Land (m²) Prices** | Region + Shell & Core price + Fully Finished price + date |

### Admin tabs (6)

1. **Regions** — CRUD region names
2. **Materials** — CRUD material names
3. **Material Prices** — select region → enter price per material for that date
4. **Land Prices** — select region → enter Shell & Core + Fully Finished prices + date
5. **Not Assigned** — _(removed, not needed)_
6. **Shortcode Generator** — visual builder to copy shortcodes

### Frontend shortcodes (2)

| Shortcode          | Shows                                                        |
| ------------------ | ------------------------------------------------------------ |
| `[sanad_materials]` | Region selector + material price table + mini charts         |
| `[sanad_land]`      | Region selector + land price table (2 rows) + combined chart |

---

## 2. DATABASE SCHEMA

Four tables, created in a transaction on activation. No pivot table needed — relations are direct.

---

### `wp_sanad_tracker_regions`

Stores region names.

| Column | Type                  | Notes              |
| ------ | --------------------- | ------------------ |
| `id`   | `BIGINT(20) UNSIGNED` | PK, AUTO_INCREMENT |
| `name` | `VARCHAR(255)`        | NOT NULL           |
| `slug` | `VARCHAR(255)`        | NOT NULL, UNIQUE   |

```sql
CREATE TABLE wp_sanad_tracker_regions (
    id   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug)
) DEFAULT CHARSET=utf8mb4;
```

---

### `wp_sanad_tracker_materials`

Stores material names.

| Column | Type                  | Notes              |
| ------ | --------------------- | ------------------ |
| `id`   | `BIGINT(20) UNSIGNED` | PK, AUTO_INCREMENT |
| `name` | `VARCHAR(255)`        | NOT NULL           |
| `slug` | `VARCHAR(255)`        | NOT NULL, UNIQUE   |

```sql
CREATE TABLE wp_sanad_tracker_materials (
    id   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug)
) DEFAULT CHARSET=utf8mb4;
```

---

### `wp_sanad_tracker_material_prices`

One row = one material's price in one region on one date.

| Column        | Type                  | Notes                                                    |
| ------------- | --------------------- | -------------------------------------------------------- |
| `id`          | `BIGINT(20) UNSIGNED` | PK, AUTO_INCREMENT                                       |
| `region_id`   | `BIGINT(20) UNSIGNED` | FK → `wp_sanad_tracker_regions(id)` ON DELETE CASCADE    |
| `material_id` | `BIGINT(20) UNSIGNED` | FK → `wp_sanad_tracker_materials(id)` ON DELETE CASCADE  |
| `price`       | `DECIMAL(15,2)`       | NOT NULL                                                 |
| `date`        | `DATE`                | NOT NULL                                                 |

```sql
CREATE TABLE wp_sanad_tracker_material_prices (
    id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    region_id   BIGINT(20) UNSIGNED NOT NULL,
    material_id BIGINT(20) UNSIGNED NOT NULL,
    price       DECIMAL(15,2) NOT NULL,
    date        DATE NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (region_id)   REFERENCES wp_sanad_tracker_regions(id)   ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES wp_sanad_tracker_materials(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;
```

---

### `wp_sanad_tracker_land_prices`

One row = Shell & Core + Fully Finished prices for a region on a date.

| Column                 | Type                  | Notes                                                  |
| ---------------------- | --------------------- | ------------------------------------------------------ |
| `id`                   | `BIGINT(20) UNSIGNED` | PK, AUTO_INCREMENT                                     |
| `region_id`            | `BIGINT(20) UNSIGNED` | FK → `wp_sanad_tracker_regions(id)` ON DELETE CASCADE  |
| `shell_core_price`     | `DECIMAL(15,2)`       | NOT NULL                                               |
| `fully_finished_price` | `DECIMAL(15,2)`       | NOT NULL                                               |
| `date`                 | `DATE`                | NOT NULL                                               |

```sql
CREATE TABLE wp_sanad_tracker_land_prices (
    id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    region_id            BIGINT(20) UNSIGNED NOT NULL,
    shell_core_price     DECIMAL(15,2) NOT NULL,
    fully_finished_price DECIMAL(15,2) NOT NULL,
    date                 DATE NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (region_id) REFERENCES wp_sanad_tracker_regions(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;
```

---

### Entity Relationship

```
wp_sanad_tracker_regions ──┬──> wp_sanad_tracker_material_prices <──── wp_sanad_tracker_materials
                           │
                           └──> wp_sanad_tracker_land_prices
```

---

## 3. DIRECTORY & FILE STRUCTURE

```
sanad-calck/
│
├── sanad-calck.php                  # Plugin header (WordPress entry point)
├── sanad-tracker.php                # Main plugin class, constants, activation
├── uninstall.php                    # Drop tables on uninstall (if option set)
├── index.php                        # Silence (security)
├── composer.json                    # PSR-4 autoload only
│
├── includes/                        # All PHP classes (PSR-4: SanadTracker\)
│   │
│   ├── Admin/
│   │   ├── AdminPage.php            # Menu registration, tab router, asset enqueue
│   │   └── Tabs/
│   │       ├── RegionsTab.php       # CRUD UI for regions
│   │       ├── MaterialsTab.php     # CRUD UI for materials
│   │       ├── MaterialPricesTab.php# Region select → material price rows entry
│   │       ├── LandPricesTab.php    # Region select → shell/fully finished entry
│   │       └── ShortcodeGeneratorTab.php
│   │
│   ├── Ajax/
│   │   ├── RegionsAjax.php          # add / edit / delete / list regions
│   │   ├── MaterialsAjax.php        # add / edit / delete / list materials
│   │   ├── MaterialPricesAjax.php   # add / delete entries + frontend data fetch
│   │   └── LandPricesAjax.php       # add / delete entries + frontend data fetch
│   │
│   └── Shortcodes/
│       ├── MaterialsShortcode.php   # [sanad_materials] render + asset enqueue
│       └── LandShortcode.php        # [sanad_land] render + asset enqueue
│
├── assets/
│   ├── css/
│   │   ├── admin/
│   │   │   ├── admin-global.css     # Tab nav, shared admin styles
│   │   │   ├── prices-tab.css       # Material prices & land prices tab styles
│   │   │   └── shortcode-gen.css    # Shortcode generator tab styles
│   │   └── frontend/
│   │       ├── sanad-table.css      # Shared table styles (both shortcodes)
│   │       └── sanad-chart.css      # Chart canvas + loader styles
│   │
│   └── js/
│       ├── admin/
│       │   ├── regions-ajax.js      # add / edit / delete regions (SweetAlert2)
│       │   ├── materials-ajax.js    # add / edit / delete materials (SweetAlert2)
│       │   ├── material-prices-ajax.js  # region select → dynamic rows → submit
│       │   ├── land-prices-ajax.js  # region select → 2-row form → submit
│       │   └── shortcode-gen.js     # live shortcode builder + copy
│       └── frontend/
│           ├── sanad-materials.js   # [sanad_materials] region select, table, mini charts
│           └── sanad-land.js        # [sanad_land] region select, table, combined chart
│
└── vendor/                          # Composer autoload
```

### Design pattern rationale vs old plugin

| Old plugin                               | This plugin                                    | Why                                |
| ---------------------------------------- | ---------------------------------------------- | ---------------------------------- |
| Flat `classes/` dump                     | `includes/Admin/`, `includes/Ajax/`, `includes/Shortcodes/` | Separation of concerns |
| One giant `AjaxHandler.php`              | One Ajax class per domain                      | Easier to read, edit, test         |
| One `SettingsPage.php` + loose tab files | `AdminPage.php` + `Tabs/` classes              | Tab logic stays with its own class |
| `settings/tabs/*.php` raw includes       | Each tab is a class with a `render()` method   | No global variable leakage         |

---

## 4. ADMIN TABS — WHAT EACH DOES

---

### Tab 1 — Regions

**File:** `includes/Admin/Tabs/RegionsTab.php`
**JS:** `assets/js/admin/regions-ajax.js`

**UI:**

- Add form: name input → slug auto-generated from name
- List table: Name | Slug | Edit | Delete
- Inline edit: click Edit → row becomes editable inline
- Delete: SweetAlert2 confirmation

**AJAX actions:**
| Action | Handler | Notes |
|--------|---------|-------|
| `sanad_tracker_add_region` | `RegionsAjax::add()` | Sanitize name, auto-slug, insert |
| `sanad_tracker_edit_region` | `RegionsAjax::edit()` | Sanitize, update name + slug |
| `sanad_tracker_delete_region` | `RegionsAjax::delete()` | Cascades to prices |
| `sanad_tracker_get_regions` | `RegionsAjax::list()` | Returns all regions (used by other tabs too) |

---

### Tab 2 — Materials

**File:** `includes/Admin/Tabs/MaterialsTab.php`
**JS:** `assets/js/admin/materials-ajax.js`

Identical pattern to Regions tab.

**AJAX actions:**
| Action | Handler |
|--------|---------|
| `sanad_tracker_add_material` | `MaterialsAjax::add()` |
| `sanad_tracker_edit_material` | `MaterialsAjax::edit()` |
| `sanad_tracker_delete_material` | `MaterialsAjax::delete()` |
| `sanad_tracker_get_materials` | `MaterialsAjax::list()` |

---

### Tab 3 — Material Prices

**File:** `includes/Admin/Tabs/MaterialPricesTab.php`
**JS:** `assets/js/admin/material-prices-ajax.js`

**UI flow:**

1. Region dropdown (Select2, loads via `sanad_tracker_get_regions`)
2. Date input (defaults to today)
3. On region select → AJAX fetch all materials → render one row per material:
   - Material name (read-only label) | Price input
4. Submit button → saves all filled rows in one AJAX call
5. Below the form: a list of recent entries for the selected region (last 20 rows), with Delete per row

**AJAX actions:**
| Action | Handler | Notes |
|--------|---------|-------|
| `sanad_tracker_save_material_prices` | `MaterialPricesAjax::save()` | Batch insert: region_id + date + array of {material_id, price} — skips empty price inputs |
| `sanad_tracker_delete_material_price` | `MaterialPricesAjax::delete()` | Delete single entry by id |
| `sanad_tracker_get_material_prices_admin_list` | `MaterialPricesAjax::adminList()` | Returns last 20 entries for a region (for admin table) |

---

### Tab 4 — Land Prices

**File:** `includes/Admin/Tabs/LandPricesTab.php`
**JS:** `assets/js/admin/land-prices-ajax.js`

**UI flow:**

1. Region dropdown (Select2)
2. Date input (defaults to today)
3. On region select → always show exactly 2 rows:
   - Row 1: **Shell & Core** | price input
   - Row 2: **Fully Finished** | price input
4. Submit → one AJAX call saves both prices + date for that region
5. Below: list of recent entries for selected region (last 20), with Delete per row

**AJAX actions:**
| Action | Handler | Notes |
|--------|---------|-------|
| `sanad_tracker_save_land_prices` | `LandPricesAjax::save()` | Insert one row: region_id, date, shell_core_price, fully_finished_price |
| `sanad_tracker_delete_land_price` | `LandPricesAjax::delete()` | Delete by id |
| `sanad_tracker_get_land_prices_admin_list` | `LandPricesAjax::adminList()` | Last 20 entries for a region |

---

### Tab 5 — Shortcode Generator

**File:** `includes/Admin/Tabs/ShortcodeGeneratorTab.php`
**JS:** `assets/js/admin/shortcode-gen.js`

**UI:**

- Dropdown: shortcode type → `[sanad_materials]` or `[sanad_land]`
- Dropdown: region (optional — leave blank = user picks on frontend)
- Title text input (optional)
- Live preview textarea updates as user changes fields
- Copy to clipboard button

**Output examples:**

```
[sanad_materials region="cairo" title="Cairo Material Prices"]
[sanad_land region="new-capital" title="New Capital Land Prices"]
```

---

## 5. FRONTEND SHORTCODES

---

### Shortcode 1 — `[sanad_materials]`

**File:** `includes/Shortcodes/MaterialsShortcode.php`
**JS:** `assets/js/frontend/sanad-materials.js`
**CSS:** `assets/css/frontend/sanad-table.css` + `sanad-chart.css`

**Attributes:**
| Attribute | Default | Notes |
|-----------|---------|-------|
| `region` | `''` | Region slug. If empty, user must select |
| `title` | `''` | Optional heading |

**Rendered HTML structure:**

```
<div class="sanad-materials-wrapper" data-region="{slug}">
  <h3>{title}</h3>
  <div class="sanad-region-selector">      ← only shown if region attr is empty
    <select class="sanad-region-select">...</select>
  </div>
  <div class="sanad-loader"></div>
  <div class="sanad-materials-table-container">
    <!-- injected by JS -->
    <table>
      <thead>
        <tr>
          <th>Material</th>
          <th>Price</th>
          <th>Chart</th>
          <th>Change</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{material name}</td>
          <td>{latest price}</td>
          <td><canvas class="sanad-mini-chart" ...></canvas></td>
          <td class="sanad-indicator up|down|neutral">↑ / ↓ / —</td>
        </tr>
        ...
      </tbody>
    </table>
  </div>
</div>
```

**JS behavior:**

1. On load: if `data-region` is set → auto-fetch. If not → wait for select change.
2. On region select change → fetch table data via AJAX.
3. Inject HTML table into container.
4. For each row: render mini Chart.js line chart on the `<canvas>` for that material (6-month avg data — see §6).
5. Render indicator (see §7).

**AJAX actions (frontend):**
| Action | Returns |
|--------|---------|
| `sanad_tracker_get_materials_table` | `{ rows: [ { material_id, material_name, latest_price, indicator, chart_data: [{month, avg}x6] } ] }` |
| `sanad_tracker_get_regions_list` | `{ regions: [ {id, name, slug} ] }` — used to populate the region selector |

---

### Shortcode 2 — `[sanad_land]`

**File:** `includes/Shortcodes/LandShortcode.php`
**JS:** `assets/js/frontend/sanad-land.js`
**CSS:** `assets/css/frontend/sanad-table.css` + `sanad-chart.css`

**Attributes:** same as `[sanad_materials]` — `region`, `title`.

**Rendered HTML structure:**

```
<div class="sanad-land-wrapper" data-region="{slug}">
  <h3>{title}</h3>
  <div class="sanad-region-selector">      ← only if region attr empty
    <select class="sanad-region-select">...</select>
  </div>
  <div class="sanad-loader"></div>
  <div class="sanad-land-table-container">
    <!-- injected by JS -->
    <table>
      <thead>
        <tr><th>Type</th><th>Price (m²)</th><th>Change</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>Shell &amp; Core</td>
          <td>{price}</td>
          <td class="sanad-indicator up|down|neutral">↑ / ↓ / —</td>
        </tr>
        <tr>
          <td>Fully Finished</td>
          <td>{price}</td>
          <td class="sanad-indicator up|down|neutral">↑ / ↓ / —</td>
        </tr>
      </tbody>
    </table>
    <canvas class="sanad-land-chart"></canvas>   ← combined chart, both lines
  </div>
</div>
```

**JS behavior:**

1. Same region-select auto-load pattern as above.
2. Fetch land data → inject table rows.
3. Render one Chart.js line chart with **two lines**: Shell & Core and Fully Finished (6-month avg — see §6).
4. Render indicators per row.

**AJAX actions (frontend):**
| Action | Returns |
|--------|---------|
| `sanad_tracker_get_land_table` | `{ shell_core: { latest_price, indicator, chart_data: [{month,avg}x6] }, fully_finished: { latest_price, indicator, chart_data: [{month,avg}x6] } }` |

---

## 6. SMART CHART LOGIC

**Rule:** Always show **6 data points**, each being the **average price for that calendar month**, for the **last 6 months** with any data.

**PHP query logic (same for both material and land charts):**

```sql
SELECT
    DATE_FORMAT(date, '%Y-%m') AS month,
    AVG(price)                 AS avg_price
FROM wp_sanad_tracker_material_prices
WHERE region_id = %d
  AND material_id = %d
  AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(date, '%Y-%m')
ORDER BY month ASC
LIMIT 6;
```

- If a month has no data → it simply won't appear (no gap-filling needed — YAGNI).
- The JS receives an array of up to 6 `{ month: "2024-11", avg: 1250.00 }` objects.
- Month label displayed as short format: `"Nov 24"`.
- Chart type: **line**, no point radius, smooth curve (`tension: 0.4`).
- No date range inputs — chart is always automatic, no user interaction.

**For land chart:** same logic but run twice (once for `shell_core_price`, once for `fully_finished_price`), both returned in the same AJAX response.

---

## 7. INDICATOR LOGIC

**Rule:** Compare the **current month's average** vs the **previous month's average**.

```
current_avg  = avg of current calendar month entries
previous_avg = avg of previous calendar month entries

if previous_avg is NULL or no data → show  —  (neutral dash)
if current_avg > previous_avg       → show  ↑  (class: up,   color: green)
if current_avg < previous_avg       → show  ↓  (class: down, color: red)
if current_avg == previous_avg      → show  —  (class: neutral)
```

**PHP query:**

```sql
SELECT
    DATE_FORMAT(date, '%Y-%m') AS month,
    AVG(price)                 AS avg_price
FROM wp_sanad_tracker_material_prices
WHERE region_id   = %d
  AND material_id = %d
  AND date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
  AND date <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
GROUP BY month
ORDER BY month ASC;
```

Returns max 2 rows (last month + current month). Comparison done in PHP.

---

## 8. AJAX ENDPOINTS REFERENCE

All endpoints follow the same patterns from the old plugin (nonce verify → capability check → sanitize → query → `wp_send_json_success/error`).

### Admin endpoints (require `sanad_tracker_access` capability)

| AJAX Action                     | Class::Method                   | Auth  |
| ------------------------------- | ------------------------------- | ----- |
| `sanad_tracker_add_region`               | `RegionsAjax::add`              | admin |
| `sanad_tracker_edit_region`              | `RegionsAjax::edit`             | admin |
| `sanad_tracker_delete_region`            | `RegionsAjax::delete`           | admin |
| `sanad_tracker_get_regions`              | `RegionsAjax::list`             | admin |
| `sanad_tracker_add_material`             | `MaterialsAjax::add`            | admin |
| `sanad_tracker_edit_material`            | `MaterialsAjax::edit`           | admin |
| `sanad_tracker_delete_material`          | `MaterialsAjax::delete`         | admin |
| `sanad_tracker_get_materials`            | `MaterialsAjax::list`           | admin |
| `sanad_tracker_save_material_prices`     | `MaterialPricesAjax::save`      | admin |
| `sanad_tracker_delete_material_price`    | `MaterialPricesAjax::delete`    | admin |
| `sanad_tracker_get_material_prices_admin_list` | `MaterialPricesAjax::adminList` | admin |
| `sanad_tracker_save_land_prices`         | `LandPricesAjax::save`          | admin |
| `sanad_tracker_delete_land_price`        | `LandPricesAjax::delete`        | admin |
| `sanad_tracker_get_land_prices_admin_list`     | `LandPricesAjax::adminList`     | admin |

### Public endpoints (no login required — `nopriv`)

| AJAX Action                | Class::Method                       | Returns                              |
| -------------------------- | ----------------------------------- | ------------------------------------ |
| `sanad_tracker_get_regions_list`    | `RegionsAjax::publicList`           | Region list for frontend selectors   |
| `sanad_tracker_get_materials_table` | `MaterialPricesAjax::frontendTable` | Table rows + chart data + indicators |
| `sanad_tracker_get_land_table`      | `LandPricesAjax::frontendTable`     | Land rows + chart data + indicators  |

### Nonce names

| Purpose            | Nonce name                      |
| ------------------ | ------------------------------- |
| Regions CRUD       | `sanad_tracker_regions_nonce`            |
| Materials CRUD     | `sanad_tracker_materials_nonce`          |
| Material Prices    | `sanad_tracker_material_prices_nonce`    |
| Land Prices        | `sanad_tracker_land_prices_nonce`        |
| Frontend Materials | `sanad_tracker_frontend_materials_nonce` |
| Frontend Land      | `sanad_tracker_frontend_land_nonce`      |

---

## 9. SECURITY RULES

Same model as the old plugin, applied consistently:

| Layer         | Rule                                                                                |
| ------------- | ----------------------------------------------------------------------------------- |
| Nonces        | Every AJAX call checks nonce via `check_ajax_referer()`                             |
| Capability    | All admin actions check `current_user_can('sanad_tracker_access')`                           |
| Sanitization  | `sanitize_text_field()`, `sanitize_title()`, `intval()`, `floatval()` on all inputs |
| SQL           | Always `$wpdb->prepare()` with `%s %d %f` — never string interpolation              |
| Output        | `esc_html()`, `esc_attr()`, `wp_json_encode()` on all output                        |
| Direct access | Every PHP file starts with `if (!defined('ABSPATH')) exit;`                         |

---

## 10. AI AGENT EXECUTION PLAN

### Principles

- **Main agent never writes code directly.** It reads this plan, sets up sub-agents, and delegates.
- **YAGNI:** if it is not in this plan, it does not get built.
- **Horizontal delivery:** each feature is built front-to-back before the next starts.
- **Sub-agents read the old plugin** (provided as reference folder) to understand patterns before writing any equivalent file.

---

### Phase 0 — Scaffold (Main agent does this once)

Sub-agent: **Scaffolder**

Tasks:

1. Create all folders and empty files from the directory tree in §3.
2. Write `composer.json` with PSR-4 mapping: `SanadTracker\\` → `includes/`.
3. Write `sanad-tracker.php` — plugin class, constants (`SANAD_TRACKER_VERSION`, `SANAD_TRACKER_DIR`, `SANAD_TRACKER_URL`), require autoload, instantiate `AdminPage`, `RegionsAjax`, `MaterialsAjax`, `MaterialPricesAjax`, `LandPricesAjax`, `MaterialsShortcode`, `LandShortcode`.
4. Write `uninstall.php` — drop all 4 tables in reverse FK order if option `sanad_tracker_delete_on_uninstall` is set.
5. Write activation method inside main class — create 4 tables via `dbDelta()`, add `sanad_tracker_access` cap to administrator + editor.
6. Write `sanad-calck.php` — plugin header (WordPress entry point), load `sanad-tracker.php`, register activation hook.
7. Write `index.php` (silence file).

---

### Phase 1 — Regions (complete front + back)

Sub-agent: **Regions Agent**

Tasks (in order):

1. Read `settings/tabs/taxonomy.php` and `assets/js/admin/taxonomies-ajax-actions.js` from old plugin.
2. Write `includes/Ajax/RegionsAjax.php` — all 4 methods (add, edit, delete, list + publicList), nonces, capability checks, `$wpdb->prepare()`.
3. Write `includes/Admin/Tabs/RegionsTab.php` — `render()` method returns HTML for the tab (add form + list table).
4. Write `assets/js/admin/regions-ajax.js` — add, inline edit, delete with SweetAlert2.
5. Write `assets/css/admin/admin-global.css` — tab nav and shared admin table styles.
6. Register nonce `sanad_tracker_regions_nonce` in `AdminPage::enqueue_assets()`.
7. Test: add a region, edit it, delete it.

---

### Phase 2 — Materials (complete front + back)

Sub-agent: **Materials Agent**

Tasks (in order):

1. Read `settings/tabs/category.php` and `assets/js/admin/categories-ajax-actions.js` from old plugin.
2. Write `includes/Ajax/MaterialsAjax.php` — same pattern as RegionsAjax.
3. Write `includes/Admin/Tabs/MaterialsTab.php`.
4. Write `assets/js/admin/materials-ajax.js`.
5. No new CSS needed — `admin-global.css` already covers list tables.
6. Test: add a material, edit it, delete it.

---

### Phase 3 — Material Prices Admin Tab

Sub-agent: **Material Prices Admin Agent**

Tasks (in order):

1. Read `settings/tabs/items.php` and `assets/js/admin/items-ajax-actions.js` and `items-page.js` from old plugin.
2. Write `includes/Ajax/MaterialPricesAjax.php` — `save()` (batch insert), `delete()`, `adminList()`.
3. Write `includes/Admin/Tabs/MaterialPricesTab.php` — region select + date + dynamic material rows + recent entries table.
4. Write `assets/js/admin/material-prices-ajax.js` — on region change: fetch materials via `sanad_tracker_get_materials`, render price rows, submit batch, refresh list, delete row.
5. Write `assets/css/admin/prices-tab.css` — dynamic rows layout.
6. Test: select region, see all material rows, enter prices, save, see in list, delete one.

---

### Phase 4 — Land Prices Admin Tab

Sub-agent: **Land Prices Admin Agent**

Tasks (in order):

1. Reference Phase 3 code (same pattern, simpler — always 2 fixed rows).
2. Write `includes/Ajax/LandPricesAjax.php` — `save()`, `delete()`, `adminList()`.
3. Write `includes/Admin/Tabs/LandPricesTab.php` — region select + date + 2 fixed rows.
4. Write `assets/js/admin/land-prices-ajax.js`.
5. Test: select region, enter shell/fully finished prices, save, delete.

---

### Phase 5 — Frontend: Materials Shortcode

Sub-agent: **Frontend Materials Agent**

Tasks (in order):

1. Read `classes/ShortcodeManager.php` and `assets/js/frontend/price-tracker-table.js` from old plugin.
2. Write `MaterialPricesAjax::frontendTable()` — SQL for latest price per material in region, indicator logic (§7), chart data query (§6).
3. Write `includes/Shortcodes/MaterialsShortcode.php` — `render()` with correct HTML structure (§5), enqueue JS + CSS + Chart.js CDN, localize nonce + ajax_url.
4. Write `assets/js/frontend/sanad-materials.js` — region select change → AJAX fetch → inject table → render 6 mini Chart.js charts → render indicators.
5. Write `assets/css/frontend/sanad-table.css` and `sanad-chart.css`.
6. Register shortcode `sanad_materials` on `init`.
7. Test: place shortcode, select region, verify table rows, verify charts render, verify indicators.

---

### Phase 6 — Frontend: Land Shortcode

Sub-agent: **Frontend Land Agent**

Tasks (in order):

1. Reference Phase 5 code.
2. Write `LandPricesAjax::frontendTable()` — latest shell+fully prices, both indicators, both chart data arrays.
3. Write `includes/Shortcodes/LandShortcode.php`.
4. Write `assets/js/frontend/sanad-land.js` — fetch → 2-row table → combined Chart.js (2 lines) → indicators.
5. Register shortcode `sanad_land` on `init`.
6. Test: place shortcode, select region, verify both rows, verify combined chart, verify indicators.

---

### Phase 7 — Shortcode Generator Tab

Sub-agent: **Shortcode Generator Agent**

Tasks (in order):

1. Read `settings/tabs/shortcode-maker.php` and `assets/js/admin/shortcode-maker.js` from old plugin.
2. Write `includes/Admin/Tabs/ShortcodeGeneratorTab.php` — type select, region select, title input, live preview textarea, copy button.
3. Write `assets/js/admin/shortcode-gen.js` — live builder logic.
4. Write `assets/css/admin/shortcode-gen.css`.
5. Test: pick type + region → shortcode updates live → copy works.

---

### Phase 8 — AdminPage Wiring

Sub-agent: **Admin Wiring Agent**

Tasks (in order):

1. Write `includes/Admin/AdminPage.php`:
   - `add_setting_page()` → menu slug `sanad-tracker`, icon `dashicons-location`, cap `sanad_tracker_access`
   - `admin_tabs()` → 5 tabs: Regions, Materials, Material Prices, Land Prices, Shortcode Generator
   - `settings_page_render()` → includes correct Tab class `render()` based on `$_GET['tab']`
   - `enqueue_assets($hook)` → only on `toplevel_page_sanad-tracker`; enqueues Select2 (CDN), SweetAlert2 (CDN), all admin JS with localized nonces, admin CSS
2. Register `sanad_tracker_delete_on_uninstall` option in `admin_init`.
3. Test: all 5 tabs load, no JS errors.

---

### Phase 9 — Final QA Checklist

Sub-agent: **QA Agent**

- All 4 tables created on activation
- Cascade deletes work (delete region → prices gone)
- All nonces verified on every AJAX call
- All inputs sanitized, all outputs escaped
- `$wpdb->prepare()` used on every query
- Frontend works with no region pre-selected (selector shows)
- Frontend works with region in shortcode attribute (selector hidden, auto-loads)
- Chart renders with fewer than 6 months of data (shows what exists)
- Indicator shows `—` when no previous month data
- No JS console errors on frontend or admin
- Plugin deactivates cleanly (no table drop)
- Plugin uninstalls cleanly (drops tables if option set)

---

## QUICK REFERENCE

### Shortcodes

| Shortcode           | Attributes        | JS file              | AJAX action                       |
| ------------------- | ----------------- | -------------------- | --------------------------------- |
| `[sanad_materials]` | `region`, `title` | `sanad-materials.js` | `sanad_tracker_get_materials_table` |
| `[sanad_land]`      | `region`, `title` | `sanad-land.js`      | `sanad_tracker_get_land_table`      |

### Admin URL Structure

```
wp-admin/admin.php?page=sanad-tracker                        → Regions (default)
wp-admin/admin.php?page=sanad-tracker&tab=materials          → Materials
wp-admin/admin.php?page=sanad-tracker&tab=material_prices    → Material Prices
wp-admin/admin.php?page=sanad-tracker&tab=land_prices        → Land Prices
wp-admin/admin.php?page=sanad-tracker&tab=shortcode_generator→ Shortcode Generator
```

### CSS Class Conventions

| Class                          | Used for                            |
| ------------------------------ | ----------------------------------- |
| `.sanad-materials-wrapper`     | Materials shortcode outer container |
| `.sanad-land-wrapper`          | Land shortcode outer container      |
| `.sanad-region-selector`       | Region dropdown wrapper (frontend)  |
| `.sanad-loader`                | CSS spinner shown during AJAX       |
| `.sanad-mini-chart`            | Canvas for per-row mini chart       |
| `.sanad-land-chart`            | Canvas for combined land chart      |
| `.sanad-indicator.up`          | Green ↑ indicator                   |
| `.sanad-indicator.down`        | Red ↓ indicator                     |
| `.sanad-indicator.neutral`     | Grey — indicator                    |
