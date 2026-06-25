<?php
namespace SanadTracker\Admin\Tabs;

if (!defined('ABSPATH')) {
    exit;
}

class MaterialPricesTab
{
    public function render(): string
    {
        global $wpdb;
        $regions   = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}sanad_tracker_regions ORDER BY name ASC");
        $materials = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}sanad_tracker_materials ORDER BY name ASC");

        ob_start();
        ?>
        <div id="material-prices-tab" class="sanad-tab-content">
            <h3><?php esc_html_e('Manage Material Prices', 'sanad-tracker'); ?></h3>

            <form method="post" class="sanad-price-form" id="material-prices-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mp_region_id"><?php esc_html_e('Region', 'sanad-tracker'); ?></label>
                        </th>
                        <td>
                            <select name="region_id" id="mp_region_id" class="support-select2" style="width:350px;" required>
                                <option value=""><?php esc_html_e('-- Select Region --', 'sanad-tracker'); ?></option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo esc_attr($region->id); ?>">
                                        <?php echo esc_html($region->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mp_date"><?php esc_html_e('Date', 'sanad-tracker'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="date" id="mp_date" value="<?php echo esc_attr(gmdate('Y-m-d')) ?>" required>
                        </td>
                    </tr>
                </table>

                <div id="material-prices-rows-wrapper" style="margin-top:20px;">
                    <p class="description"><?php esc_html_e('Select a region to load materials.', 'sanad-tracker'); ?></p>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary" id="mp-save-btn">
                        <?php esc_html_e('Save Prices', 'sanad-tracker'); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h3><?php esc_html_e('Recent Entries', 'sanad-tracker'); ?></h3>
            <div class="sanad-matrix-scroll">
                <p id="sanad-matrix-placeholder" class="sanad-matrix-placeholder description">
                    <?php esc_html_e('Select a region to view entries.', 'sanad-tracker'); ?>
                </p>
                <div class="sanad-matrix-grid" id="material-prices-list-table" style="display:none;">
                    <div class="matrix-header-row" id="material-prices-matrix-header"></div>
                    <div class="matrix-body" id="sanad-material-prices-recent-tbody"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
