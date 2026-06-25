<?php
namespace SanadTracker\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

class LandPricesAjax
{
    public function __construct()
    {
        add_action('wp_ajax_sanad_tracker_save_land_prices', [$this, 'save']);
        add_action('wp_ajax_sanad_tracker_delete_land_price', [$this, 'delete']);
        add_action('wp_ajax_sanad_tracker_update_land_prices', [$this, 'update']);
        add_action('wp_ajax_sanad_tracker_get_land_prices_admin_list', [$this, 'adminList']);
        add_action('wp_ajax_sanad_tracker_get_land_table', [$this, 'frontendTable']);
        add_action('wp_ajax_nopriv_sanad_tracker_get_land_table', [$this, 'frontendTable']);
    }

    public function save(): void
    {
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        $region_id            = intval($_POST['region_id'] ?? 0);
        $date                 = sanitize_text_field($_POST['date'] ?? '');
        $shell_core_price     = floatval($_POST['shell_core_price'] ?? 0);
        $fully_finished_price = floatval($_POST['fully_finished_price'] ?? 0);

        if (!$region_id || empty($date)) {
            wp_send_json_error(['message' => __('Region and date are required.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $regions_table = $wpdb->prefix . 'sanad_tracker_regions';
        $region_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$regions_table} WHERE id = %d", $region_id
        ));

        if (!$region_exists) {
            wp_send_json_error(['message' => __('Region not found.', 'sanad-tracker')]);
            wp_die();
        }

        $table = $wpdb->prefix . 'sanad_tracker_land_prices';

        $saved = $wpdb->insert(
            $table,
            [
                'region_id'            => $region_id,
                'shell_core_price'     => $shell_core_price,
                'fully_finished_price' => $fully_finished_price,
                'date'                 => $date,
            ],
            ['%d', '%f', '%f', '%s']
        );

        if (!$saved) {
            wp_send_json_error(['message' => __('Could not save land prices.', 'sanad-tracker')]);
            wp_die();
        }

        wp_send_json_success(['message' => __('Land prices saved.', 'sanad-tracker')]);
        wp_die();
    }

    public function delete(): void
    {
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid ID.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_land_prices';

        $wpdb->delete($table, ['id' => $id], ['%d']);

        wp_send_json_success(['message' => __('Entry deleted.', 'sanad-tracker')]);
        wp_die();
    }

    public function adminList(): void
    {
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        $region_id = intval($_GET['region_id'] ?? 0);

        if (!$region_id) {
            wp_send_json_error(['message' => __('Invalid region.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_land_prices';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, region_id, shell_core_price, fully_finished_price, date
            FROM {$table}
            WHERE region_id = %d
            ORDER BY date DESC, id DESC
            LIMIT 20",
            $region_id
        ));

        wp_send_json_success(['entries' => $results]);
        wp_die();
    }

    public function update(): void
    {
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        $id                   = intval($_POST['id'] ?? 0);
        $shell_core_price     = floatval($_POST['shell_core_price'] ?? 0);
        $fully_finished_price = floatval($_POST['fully_finished_price'] ?? 0);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid ID.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_land_prices';

        $updated = $wpdb->update(
            $table,
            [
                'shell_core_price'     => $shell_core_price,
                'fully_finished_price' => $fully_finished_price,
            ],
            ['id' => $id],
            ['%f', '%f'],
            ['%d']
        );

        if ($updated === false) {
            wp_send_json_error(['message' => __('Could not update land prices.', 'sanad-tracker')]);
            wp_die();
        }

        wp_send_json_success(['message' => __('Land prices updated.', 'sanad-tracker')]);
        wp_die();
    }

    public function frontendTable(): void
    {
        check_ajax_referer('sanad_tracker_frontend_land_nonce', 'nonce');

        $region_id = intval($_POST['region_id'] ?? 0);

        if (!$region_id) {
            wp_send_json_error(['message' => __('Invalid region.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_land_prices';

        // Get latest prices
        $latest = $wpdb->get_row($wpdb->prepare(
            "SELECT shell_core_price, fully_finished_price, date
            FROM {$table}
            WHERE region_id = %d
            ORDER BY date DESC, id DESC
            LIMIT 1",
            $region_id
        ));

        // Get chart data (6 months averages) for shell_core
        $chart_shell_core = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(shell_core_price) AS avg_price
            FROM {$table}
            WHERE region_id = %d AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month ASC
            LIMIT 6",
            $region_id
        ));

        // Get chart data (6 months averages) for fully_finished
        $chart_fully_finished = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(fully_finished_price) AS avg_price
            FROM {$table}
            WHERE region_id = %d AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month ASC
            LIMIT 6",
            $region_id
        ));

        // Indicator: current vs previous month avg for shell_core
        $indicator_shell_core = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(shell_core_price) AS avg_price
            FROM {$table}
            WHERE region_id = %d
              AND date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
              AND date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            GROUP BY month ORDER BY month ASC",
            $region_id
        ));

        // Indicator: current vs previous month avg for fully_finished
        $indicator_fully_finished = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(fully_finished_price) AS avg_price
            FROM {$table}
            WHERE region_id = %d
              AND date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
              AND date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            GROUP BY month ORDER BY month ASC",
            $region_id
        ));

        // Compute indicator value for shell_core
        $sc_dir = 'neutral';
        $sc_pct = null;
        if (count($indicator_shell_core) >= 2) {
            $prev = floatval($indicator_shell_core[0]->avg_price);
            $curr = floatval($indicator_shell_core[1]->avg_price);
            if ($prev > 0) {
                $sc_pct = round((($curr - $prev) / $prev) * 100, 1);
            }
            if ($curr > $prev) {
                $sc_dir = 'up';
            } elseif ($curr < $prev) {
                $sc_dir = 'down';
            } else {
                $sc_dir = 'neutral';
                $sc_pct = 0.0;
            }
        }

        // Compute indicator value for fully_finished
        $ff_dir = 'neutral';
        $ff_pct = null;
        if (count($indicator_fully_finished) >= 2) {
            $prev = floatval($indicator_fully_finished[0]->avg_price);
            $curr = floatval($indicator_fully_finished[1]->avg_price);
            if ($prev > 0) {
                $ff_pct = round((($curr - $prev) / $prev) * 100, 1);
            }
            if ($curr > $prev) {
                $ff_dir = 'up';
            } elseif ($curr < $prev) {
                $ff_dir = 'down';
            } else {
                $ff_dir = 'neutral';
                $ff_pct = 0.0;
            }
        }

        $response = [
            'shell_core' => [
                'latest_price'  => $latest ? floatval($latest->shell_core_price) : null,
                'indicator_dir' => $sc_dir,
                'indicator_pct' => $sc_pct,
                'chart_data'    => $chart_shell_core,
            ],
            'fully_finished' => [
                'latest_price'  => $latest ? floatval($latest->fully_finished_price) : null,
                'indicator_dir' => $ff_dir,
                'indicator_pct' => $ff_pct,
                'chart_data'    => $chart_fully_finished,
            ],
        ];

        wp_send_json_success($response);
        wp_die();
    }
}
