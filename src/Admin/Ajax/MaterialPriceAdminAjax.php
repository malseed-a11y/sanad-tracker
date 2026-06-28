<?php

namespace SanadTracker\Admin\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

use SanadTracker\Database\MaterialPriceRepository;

class MaterialPriceAdminAjax
{
    private MaterialPriceRepository $repository;

    public function __construct()
    {
        $this->repository = new MaterialPriceRepository();

        add_action('wp_ajax_sanad_tracker_save_material_prices', [$this, 'save']);
        add_action('wp_ajax_sanad_tracker_delete_material_price', [$this, 'delete']);
        add_action('wp_ajax_sanad_tracker_get_material_prices_admin_list', [$this, 'adminList']);
        add_action('wp_ajax_sanad_tracker_update_material_prices', [$this, 'updateMaterialPrices']);
    }

    public function save(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $regionId = intval($_POST['region_id'] ?? 0);
        $date     = sanitize_text_field($_POST['date'] ?? '');
        $prices   = $_POST['prices'] ?? [];

        if (!$regionId || empty($date)) {
            wp_send_json_error(['message' => __('Region and date are required.', 'sanad-tracker')]);
        }

        if (empty($prices) || !is_array($prices)) {
            wp_send_json_error(['message' => __('No prices provided.', 'sanad-tracker')]);
        }

        $inserted = $this->repository->saveBatch($regionId, $date, $prices);

        if ($inserted === 0) {
            wp_send_json_error(['message' => __('No valid prices to save.', 'sanad-tracker')]);
        }

        wp_send_json_success(['message' => sprintf(__('Saved %d price(s).', 'sanad-tracker'), $inserted)]);
    }

    public function delete(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid ID.', 'sanad-tracker')]);
        }

        $this->repository->delete($id);

        wp_send_json_success(['message' => __('Entry deleted.', 'sanad-tracker')]);
    }

    public function adminList(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $regionId = intval($_GET['region_id'] ?? 0);

        if (!$regionId) {
            wp_send_json_error(['message' => __('Invalid region.', 'sanad-tracker')]);
        }

        $result = $this->repository->getAdminList($regionId);

        wp_send_json_success($result);
    }

    public function updateMaterialPrices(): void
    {
        check_ajax_referer('sanad_tracker_material_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $regionId = intval($_POST['region_id'] ?? 0);
        $date     = sanitize_text_field($_POST['date'] ?? '');
        $prices   = $_POST['prices'] ?? [];

        if (!$regionId || empty($date)) {
            wp_send_json_error(['message' => __('Region and date are required.', 'sanad-tracker')]);
        }

        $result = $this->repository->updateOrDeleteBatch($regionId, $date, $prices);

        if ($result['deleted'] > 0 && $result['updated'] === 0 && $result['inserted'] === 0) {
            wp_send_json_success([
                'message' => __('All entries for this date have been deleted.', 'sanad-tracker'),
            ]);
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Updated: %1$d, Inserted: %2$d, Deleted: %3$d', 'sanad-tracker'),
                $result['updated'],
                $result['inserted'],
                $result['deleted']
            ),
        ]);
    }
}
