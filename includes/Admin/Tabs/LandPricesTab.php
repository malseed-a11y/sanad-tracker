<?php
namespace SanadTracker\Admin\Tabs;

if (!defined('ABSPATH')) {
    exit;
}

class LandPricesTab
{
    public function render(): string
    {
        global $wpdb;
        $regions = $wpdb->get_results(
            "SELECT id, name FROM {$wpdb->prefix}sanad_tracker_regions ORDER BY name ASC"
        );

        ob_start();
        ?>
        <div id="land-prices-tab" class="sanad-tab-content">
            <h3><?php esc_html_e('Manage Land Prices', 'sanad-tracker'); ?></h3>

            <form method="post" class="sanad-price-form" id="land-prices-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="lp_region_id"><?php esc_html_e('Region', 'sanad-tracker'); ?></label>
                        </th>
                        <td>
                            <select name="region_id" id="lp_region_id" class="support-select2" style="width:350px;" required>
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
                            <label for="lp_date"><?php esc_html_e('Date', 'sanad-tracker'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="date" id="lp_date" value="<?php echo esc_attr(gmdate('Y-m-d')); ?>" required>
                        </td>
                    </tr>
                </table>

                <div id="land-prices-rows-wrapper" style="margin-top:20px;">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="lp_shell_core"><?php esc_html_e('Shell & Core', 'sanad-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="number" step="0.01" name="shell_core_price" id="lp_shell_core" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="lp_fully_finished"><?php esc_html_e('Fully Finished', 'sanad-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="number" step="0.01" name="fully_finished_price" id="lp_fully_finished" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary" id="lp-save-btn">
                        <?php esc_html_e('Save Prices', 'sanad-tracker'); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h3><?php esc_html_e('Recent Entries', 'sanad-tracker'); ?></h3>
            <table class="widefat striped sanad-list-table" id="land-prices-list-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'sanad-tracker'); ?></th>
                        <th><?php esc_html_e('Shell & Core', 'sanad-tracker'); ?></th>
                        <th><?php esc_html_e('Fully Finished', 'sanad-tracker'); ?></th>
                        <th><?php esc_html_e('Date', 'sanad-tracker'); ?></th>
                        <th><?php esc_html_e('Actions', 'sanad-tracker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5"><?php esc_html_e('Select a region to view entries.', 'sanad-tracker'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
