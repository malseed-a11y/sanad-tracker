<?php

namespace SanadTracker\Admin\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

use SanadTracker\Database\LandPriceRepository;

class LandPriceAdminAjax
{
    private LandPriceRepository $repository;

    public function __construct()
    {
        $this->repository = new LandPriceRepository();

        add_action('wp_ajax_sanad_tracker_save_land_prices', [$this, 'save']);
        add_action('wp_ajax_sanad_tracker_delete_land_price', [$this, 'delete']);
        add_action('wp_ajax_sanad_tracker_update_land_prices', [$this, 'update']);
        add_action('wp_ajax_sanad_tracker_get_land_prices_admin_list', [$this, 'adminList']);
    }

    public function save(): void
    {
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $regionId      = intval($_POST['region_id'] ?? 0);
        $date          = sanitize_text_field($_POST['date'] ?? '');
        $shellCore     = floatval($_POST['shell_core_price'] ?? 0);
        $fullyFinished = floatval($_POST['fully_finished_price'] ?? 0);

        if (!$regionId || empty($date)) {
            wp_send_json_error(['message' => __('Region and date are required.', 'sanad-tracker')]);
        }

        $this->repository->save($regionId, $date, $shellCore, $fullyFinished);

        wp_send_json_success(['message' => __('Land prices saved.', 'sanad-tracker')]);
    }

    public function delete(): void
    {
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

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
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $regionId = intval($_GET['region_id'] ?? 0);

        if (!$regionId) {
            wp_send_json_error(['message' => __('Invalid region.', 'sanad-tracker')]);
        }

        $entries = $this->repository->getAdminList($regionId);

        wp_send_json_success(['entries' => $entries]);
    }

    public function update(): void
    {
        check_ajax_referer('sanad_tracker_land_prices_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $id            = intval($_POST['id'] ?? 0);
        $shellCore     = floatval($_POST['shell_core_price'] ?? 0);
        $fullyFinished = floatval($_POST['fully_finished_price'] ?? 0);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid ID.', 'sanad-tracker')]);
        }

        $this->repository->update($id, $shellCore, $fullyFinished);

        wp_send_json_success(['message' => __('Land prices updated.', 'sanad-tracker')]);
    }
}
