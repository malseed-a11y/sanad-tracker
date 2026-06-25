## Phase: Refactor Material Prices Admin Tab to Matrix View with Inline Editing

### Objective
Refactor the Material Prices admin tab so the "Recent Entries" table becomes a spreadsheet-like matrix. Instead of one row per material, entries must be grouped by **Date** for the selected region. Each material gets its own column (dynamic, based on active materials in `wp_sanad_tracker_materials`). Every row in this matrix must be fully inline-editable in JS (click Edit -> all cells become `<input>`, click Save -> AJAX batch update).

### Strict Rules (from `AGENTS.md` and `plan.md`)
1. **Do NOT change the database schema.** Keep `wp_sanad_tracker_material_prices` exactly as-is. Handle the matrix transformation entirely in PHP and JS.
2. **Security is mandatory:**
   - **Sanity check:** Verify `$_POST` nonces using `check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce')` at the start of each AJAX handler.
   - **Capability check:** must confirm `current_user_can('sanad_tracker_access')`.
   - **`$wpdb->prepare()` is non-negotiable.** Never use raw SQL. Avoid WHERE IN if possible; if necessary, loop with placeholders or use a prepared IN clause.
   - **Sanitize inputs** using `sanitize_text_field()`, `intval()`, `floatval()`.
   - **Escape outputs** with `esc_html()`, `esc_attr()`, or `wp_json_encode()`.
   - Prefix tables with `$wpdb->prefix`.
   - Return `wp_send_json_error()` if validation fails.

---

### Step 1: Update the PHP Admin Tab Rendering

**File:** `includes/Admin/Tabs/MaterialPricesTab.php`

**Required Changes:**
* Fetch all `wp_sanad_tracker_materials` using `$wpdb` at the top of `render()`.
* Render the `<thead>` dynamically in PHP:
  ```html
  <thead>
      <tr>
          <th class="col-date">Date</th>
          <!-- Loop materials -->
          <th class="col-material">Material Name</th>
          <th class="col-actions">Actions</th>
      </tr>
  </thead>
  ```
* Keep the original `<form>` and input rows above the table completely intact for entering new data.
* The table body must use ID: `<tbody id="sanad-material-prices-recent-tbody">`.
* Wrap the table in `<div class="sanad-matrix-scroll">` for horizontal scroll on small screens.
* Calculate `$colspan = count($materials) + 2` for the no-data placeholder row.

---

### Step 2: Refactor the Admin List AJAX Endpoint

**File:** `includes/Ajax/MaterialPricesAjax.php` (Method: `adminList`)

**Required Changes:**
* Also fetch all materials: `SELECT id, name FROM {$wpdb->prefix}sanad_tracker_materials ORDER BY name ASC`.
* Modify the prices query to select `material_id, price, date` for the selected `region_id`.
* **Pivot data in PHP:** Use nested arrays to group flat SQL results by `date`.
* Structure example:
  ```php
  $matrix = [
      '2026-06-24' => [
          'date'   => '2026-06-24',
          'prices' => [
              1 => '50.00',  // material_id => price
              2 => '200.00',
              3 => '155.00'
          ]
      ]
  ];
  ```
* Ensure missing `material_id`s for a date simply don't appear in the prices sub-array (the JS will handle empty cells when it iterates all materials).
* Return: `wp_send_json_success(['matrix' => array_values($matrix), 'materials' => $materials])`.

---

### Step 3: Refactor Frontend JS for Rendering and Inline Editing

**File:** `assets/js/admin/material-prices-ajax.js`

**Required Changes:**
* **Render Logic:**
  - On region change or after a save, empty the table body.
  - Loop through the matrix rows. For each date row, create a `<tr data-date="...">`.
  - Inside the row:
    - First `<td class="cell-date">`: the date text.
    - Loop through `materials` array and create `<td class="mp-price-cell" data-material-id="X" data-original="Y">` for each. Display the price or empty string.
    - Last `<td class="cell-actions">`: "Edit" and "Delete" buttons.
  - Also update the `<thead>` dynamically when materials change.

* **Inline Edit State:**
  - "Edit" click: store original values in `data-original` on `<td>`.
  - Replace text in material price cells with `<input type="number" step="0.01" class="mp-inline-price">`.
  - Change "Edit" button to "Save" (class `save-row-btn`).
  - Change "Delete" button to "Cancel" (class `cancel-row-btn`).

* **Save Logic:**
  - On "Save" click: gather values from all `<input>`s in that row, map to `material_id`s, send via AJAX (`action=sanad_tracker_update_material_prices`).
  - On success: reload the list to reflect updated data.
  - Show SweetAlert2 loading during the save.

* **Cancel Logic:**
  - On "Cancel" click: revert cells to `data-original` values and revert buttons to "Edit"/"Delete".

* **Delete Date Row Logic:**
  - On "Delete" click: confirm with SweetAlert2, then send `action=sanad_tracker_update_material_prices` with an empty `prices` array. The PHP handler will delete all entries for that region_id + date.

---

### Step 4: Create the Batch Update AJAX Endpoint

**File:** `includes/Ajax/MaterialPricesAjax.php`

**New Method:** `updateMaterialPrices()`

**Hook:** `wp_ajax_sanad_tracker_update_material_prices`

**Required Changes:**
* Accept `region_id` (int), `date` (string), `prices` (array of objects: `{material_id, price}`).
* If `prices` is empty or not an array: **DELETE ALL** entries for that `region_id` + `date`.
* For each material in the payload:
  - If `price` is provided and not empty:
    - Check if a row exists for `region_id` + `date` + `material_id`.
    - **If yes**, UPDATE it.
    - **If no**, INSERT it.
  - If `price` is cleared/empty: **DELETE** that specific row.
* Return `wp_send_json_success()` with a summary message.
* **Security:** Validate nonce, capability, and `region_id` before any DB operations.

---

### Step 5: Update CSS

**File:** `assets/css/admin/prices-tab.css`

Add styles for:
* `.sanad-matrix-scroll` — `overflow-x: auto` for responsive tables.
* `.sanad-matrix-table` — minimum width, column sizing.
* `.mp-inline-price` — inline input width and alignment.
* `.save-row-btn`, `.cancel-row-btn` — button color states.
* `.cell-date` — bold, no-wrap.
* `.cell-actions` — no-wrap.
* `.mp-price-cell` — right-aligned text for prices.

---

### Step 6: Update AdminPage.php Localization

**File:** `includes/Admin/AdminPage.php`

Add new i18n keys to `SanadTrackerMaterialPricesAjax`:
* `edit` — "Edit"
* `save` — "Save"
* `confirm_delete_date_title` — "Delete all entries?"
* `confirm_delete_date_text` — "All prices for this date will be permanently deleted."
* `deleting` — "Deleting..."
* `saving` — "Saving..."

---

### Verification Checklist

- [ ] Selecting a region populates the table where each row represents a unique date.
- [ ] If a region has entries for Wood and Iron on 2026-06-24 but missing Cement, the Cement column is blank.
- [ ] Clicking "Edit" on a row makes all material prices in that row input fields.
- [ ] Clicking "Save" persists the correct prices to the database (upserts existing, inserts new).
- [ ] Clearing a field and saving removes that specific material's record from the database for that date.
- [ ] Clicking "Cancel" reverts all inputs to their previous values without touching the database.
- [ ] Clicking "Delete" on a date row (not in edit mode) removes all entries for that date+region.
- [ ] All original form functionality (adding new prices) continues to work independently.
- [ ] All security checks (nonces, `sanad_tracker_access` capability, `$wpdb->prepare()`) are preserved.
- [ ] Horizontal scroll works on narrow screens.
- [ ] No JS console errors.
- [ ] No PHP error log entries.
