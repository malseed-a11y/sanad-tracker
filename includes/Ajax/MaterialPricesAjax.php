<?php
namespace SanadTracker\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

class MaterialPricesAjax
{
    public function __construct()
    {
        add_action('wp_ajax_sanad_tracker_save_material_prices', [$this, 'save']);
        add_action('wp_ajax_sanad_tracker_delete_material_price', [$this, 'delete']);
        add_action('wp_ajax_sanad_tracker_get_material_prices_admin_list', [$this, 'adminList']);
        add_action('wp_ajax_sanad_tracker_update_material_prices', [$this, 'updateMaterialPrices']);
        add_action('wp_ajax_sanad_tracker_get_materials_table', [$this, 'frontendTable']);
        add_action('wp_ajax_nopriv_sanad_tracker_get_materials_table', [$this, 'frontendTable']);
    }

    public function save(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        $region_id = intval($_POST['region_id'] ?? 0);
        $date      = sanitize_text_field($_POST['date'] ?? '');
        $prices    = $_POST['prices'] ?? [];

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

        if (empty($prices) || !is_array($prices)) {
            wp_send_json_error(['message' => __('No prices provided.', 'sanad-tracker')]);
            wp_die();
        }

        $table = $wpdb->prefix . 'sanad_tracker_material_prices';

        $inserted = 0;

        foreach ($prices as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $material_id = intval($entry['material_id'] ?? 0);
            $price       = isset($entry['price']) && $entry['price'] !== '' ? floatval($entry['price']) : null;

            if (!$material_id || $price === null) {
                continue;
            }

            $result = $wpdb->insert(
                $table,
                [
                    'region_id'   => $region_id,
                    'material_id' => $material_id,
                    'price'       => $price,
                    'date'        => $date,
                ],
                ['%d', '%d', '%f', '%s']
            );

            if ($result) {
                $inserted++;
            }
        }

        if ($inserted === 0) {
            wp_send_json_error(['message' => __('No valid prices to save.', 'sanad-tracker')]);
            wp_die();
        }

        wp_send_json_success(['message' => sprintf(__('Saved %d price(s).', 'sanad-tracker'), $inserted)]);
        wp_die();
    }

    public function delete(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

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
        $table = $wpdb->prefix . 'sanad_tracker_material_prices';

        $wpdb->delete($table, ['id' => $id], ['%d']);

        wp_send_json_success(['message' => __('Entry deleted.', 'sanad-tracker')]);
        wp_die();
    }

    public function adminList(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

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
        $prices_table    = $wpdb->prefix . 'sanad_tracker_material_prices';
        $materials_table = $wpdb->prefix . 'sanad_tracker_materials';

        $materials = $wpdb->get_results(
            "SELECT id, name FROM {$materials_table} ORDER BY name ASC"
        );

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT material_id, price, date
            FROM {$prices_table}
            WHERE region_id = %d
            ORDER BY date DESC, material_id ASC",
            $region_id
        ));

        $matrix = [];
        foreach ($results as $row) {
            $date = $row->date;
            if (!isset($matrix[$date])) {
                $matrix[$date] = [
                    'date'   => $date,
                    'prices' => [],
                ];
            }
            $matrix[$date]['prices'][(int) $row->material_id] = $row->price;
        }

        wp_send_json_success([
            'matrix'    => array_values($matrix),
            'materials' => $materials,
        ]);
        wp_die();
    }

    public function updateMaterialPrices(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        $region_id = intval($_POST['region_id'] ?? 0);
        $date      = sanitize_text_field($_POST['date'] ?? '');
        $prices    = $_POST['prices'] ?? [];

        if (!$region_id || empty($date)) {
            wp_send_json_error(['message' => __('Region and date are required.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_material_prices';

        $updated = 0;
        $inserted = 0;
        $deleted = 0;

        if (empty($prices) || !is_array($prices)) {
            $wpdb->delete(
                $table,
                [
                    'region_id' => $region_id,
                    'date'      => $date,
                ],
                ['%d', '%s']
            );

            wp_send_json_success([
                'message' => __('All entries for this date have been deleted.', 'sanad-tracker'),
            ]);
            wp_die();
        }

        foreach ($prices as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $material_id = intval($entry['material_id'] ?? 0);
            $price       = isset($entry['price']) && $entry['price'] !== '' ? floatval($entry['price']) : null;

            if (!$material_id) {
                continue;
            }

            if ($price === null) {
                $wpdb->delete(
                    $table,
                    [
                        'region_id'   => $region_id,
                        'material_id' => $material_id,
                        'date'        => $date,
                    ],
                    ['%d', '%d', '%s']
                );
                $deleted++;
            } else {
                $existing_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table}
                    WHERE region_id = %d AND material_id = %d AND date = %s",
                    $region_id,
                    $material_id,
                    $date
                ));

                if ($existing_id) {
                    $wpdb->update(
                        $table,
                        ['price' => $price],
                        ['id' => $existing_id],
                        ['%f'],
                        ['%d']
                    );
                    $updated++;
                } else {
                    $wpdb->insert(
                        $table,
                        [
                            'region_id'   => $region_id,
                            'material_id' => $material_id,
                            'price'       => $price,
                            'date'        => $date,
                        ],
                        ['%d', '%d', '%f', '%s']
                    );
                    $inserted++;
                }
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                /* translators: 1: updated count, 2: inserted count, 3: deleted count */
                __('Updated: %1$d, Inserted: %2$d, Deleted: %3$d', 'sanad-tracker'),
                $updated,
                $inserted,
                $deleted
            ),
        ]);
        wp_die();
    }

    public function frontendTable(): void
    {
        check_ajax_referer('sanad_tracker_frontend_materials_nonce', 'nonce');

        $region_id = intval($_POST['region_id'] ?? 0);

        if (!$region_id) {
            wp_send_json_success(['rows' => []]);
            wp_die();
        }

        global $wpdb;
        $materials_table   = $wpdb->prefix . 'sanad_tracker_materials';
        $prices_table      = $wpdb->prefix . 'sanad_tracker_material_prices';

        $materials = $wpdb->get_results(
            "SELECT id, name, slug FROM {$materials_table} ORDER BY name ASC"
        );

        if (empty($materials)) {
            wp_send_json_success(['rows' => []]);
            wp_die();
        }

        $rows = [];

        foreach ($materials as $material) {
            $material_id = (int) $material->id;

            // Latest price
            $latest = $wpdb->get_var($wpdb->prepare(
                "SELECT price FROM {$prices_table}
                WHERE region_id = %d AND material_id = %d
                ORDER BY date DESC, id DESC LIMIT 1",
                $region_id,
                $material_id
            ));

            // Chart data (6 months)
            $chart_results = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(price) AS avg_price
                FROM {$prices_table}
                WHERE region_id = %d AND material_id = %d
                  AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month ASC
                LIMIT 6",
                $region_id,
                $material_id
            ));

            $chart_data = [];
            foreach ($chart_results as $cr) {
                $chart_data[] = [
                    'month' => $cr->month,
                    'avg'   => floatval($cr->avg_price),
                ];
            }

            // Indicator data (current vs previous month)
            $indicator_results = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(price) AS avg_price
                FROM {$prices_table}
                WHERE region_id = %d AND material_id = %d
                  AND date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                  AND date <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                GROUP BY month
                ORDER BY month ASC",
                $region_id,
                $material_id
            ));

            $indicator_dir = 'neutral';
            $indicator_pct = null;

            if (count($indicator_results) === 2) {
                $prev_avg = floatval($indicator_results[0]->avg_price);
                $curr_avg = floatval($indicator_results[1]->avg_price);

                if ($prev_avg > 0) {
                    $indicator_pct = round((($curr_avg - $prev_avg) / $prev_avg) * 100, 1);
                }

                if ($curr_avg > $prev_avg) {
                    $indicator_dir = 'up';
                } elseif ($curr_avg < $prev_avg) {
                    $indicator_dir = 'down';
                } else {
                    $indicator_dir = 'neutral';
                    $indicator_pct = 0.0;
                }
            }

            $rows[] = [
                'material_id'   => $material_id,
                'material_name' => $material->name,
                'latest_price'  => $latest !== null ? floatval($latest) : null,
                'indicator_dir' => $indicator_dir,
                'indicator_pct' => $indicator_pct,
                'chart_data'    => $chart_data,
            ];
        }

        wp_send_json_success(['rows' => $rows]);
        wp_die();
    }
}
