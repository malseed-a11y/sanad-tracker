<?php
namespace SanadTracker\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

class RegionsAjax
{
    public function __construct()
    {
        add_action('wp_ajax_sanad_tracker_add_region', [$this, 'add']);
        add_action('wp_ajax_sanad_tracker_edit_region', [$this, 'edit']);
        add_action('wp_ajax_sanad_tracker_delete_region', [$this, 'delete']);
        add_action('wp_ajax_sanad_tracker_get_regions', [$this, 'list']);
        add_action('wp_ajax_nopriv_sanad_tracker_get_regions', [$this, 'publicList']);
        add_action('wp_ajax_sanad_tracker_get_regions_list', [$this, 'publicList']);
        add_action('wp_ajax_nopriv_sanad_tracker_get_regions_list', [$this, 'publicList']);
    }

    public function add(): void
    {
        check_ajax_referer('sanad_tracker_regions_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_regions';

        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = sanitize_title($name);

        if (empty($name)) {
            wp_send_json_error(['message' => __('Region name is required.', 'sanad-tracker')]);
            wp_die();
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug)
        );

        if ($existing) {
            wp_send_json_error(['message' => __('Region with this name already exists.', 'sanad-tracker')]);
            wp_die();
        }

        $inserted = $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
        ]);

        if (!$inserted) {
            wp_send_json_error(['message' => __('Could not save region.', 'sanad-tracker')]);
            wp_die();
        }

        $id = $wpdb->insert_id;

        wp_send_json_success([
            'message' => __('Region added.', 'sanad-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
        wp_die();
    }

    public function edit(): void
    {
        check_ajax_referer('sanad_tracker_regions_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_regions';

        $id   = intval($_POST['id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = sanitize_title($name);

        if (!$id || empty($name)) {
            wp_send_json_error(['message' => __('Invalid data.', 'sanad-tracker')]);
            wp_die();
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE slug = %s AND id != %d", $slug, $id)
        );

        if ($existing) {
            wp_send_json_error(['message' => __('Region with this name already exists.', 'sanad-tracker')]);
            wp_die();
        }

        $wpdb->update(
            $table,
            ['name' => $name, 'slug' => $slug],
            ['id' => $id]
        );

        wp_send_json_success([
            'message' => __('Region updated.', 'sanad-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
        wp_die();
    }

    public function delete(): void
    {
        check_ajax_referer('sanad_tracker_regions_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_regions';

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid region ID.', 'sanad-tracker')]);
            wp_die();
        }

        $wpdb->delete($table, ['id' => $id]);

        wp_send_json_success(['message' => __('Region deleted.', 'sanad-tracker')]);
        wp_die();
    }

    public function list(): void
    {
        check_ajax_referer('sanad_tracker_regions_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_regions';

        $regions = $wpdb->get_results(
            "SELECT id, name, slug FROM $table ORDER BY id DESC"
        );

        wp_send_json_success(['regions' => $regions]);
        wp_die();
    }

    public function publicList(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_regions';

        $regions = $wpdb->get_results(
            "SELECT id, name, slug FROM $table ORDER BY name ASC"
        );

        wp_send_json_success(['regions' => $regions]);
        wp_die();
    }
}
