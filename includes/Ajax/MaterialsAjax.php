<?php
namespace SanadTracker\Ajax;

if (!defined('ABSPATH')) exit;

class MaterialsAjax
{
    public function __construct()
    {
        add_action('wp_ajax_sanad_tracker_add_material', [$this, 'add']);
        add_action('wp_ajax_sanad_tracker_edit_material', [$this, 'edit']);
        add_action('wp_ajax_sanad_tracker_delete_material', [$this, 'delete']);
        add_action('wp_ajax_sanad_tracker_get_materials', [$this, 'list']);
    }

    public function add(): void
    {
        check_ajax_referer('sanad_tracker_materials_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_materials';

        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = sanitize_title($name);

        if (empty($name)) {
            wp_send_json_error(['message' => __('Material name is required.', 'sanad-tracker')]);
            wp_die();
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug)
        );

        if ($existing) {
            wp_send_json_error(['message' => __('Material with this name already exists.', 'sanad-tracker')]);
            wp_die();
        }

        $inserted = $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
        ]);

        if (!$inserted) {
            wp_send_json_error(['message' => __('Could not save material.', 'sanad-tracker')]);
            wp_die();
        }

        $id = $wpdb->insert_id;

        wp_send_json_success([
            'message' => __('Material added.', 'sanad-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
        wp_die();
    }

    public function edit(): void
    {
        check_ajax_referer('sanad_tracker_materials_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_materials';

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
            wp_send_json_error(['message' => __('Material with this name already exists.', 'sanad-tracker')]);
            wp_die();
        }

        $wpdb->update(
            $table,
            ['name' => $name, 'slug' => $slug],
            ['id' => $id]
        );

        wp_send_json_success([
            'message' => __('Material updated.', 'sanad-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
        wp_die();
    }

    public function delete(): void
    {
        check_ajax_referer('sanad_tracker_materials_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_materials';

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid material ID.', 'sanad-tracker')]);
            wp_die();
        }

        $wpdb->delete($table, ['id' => $id]);

        wp_send_json_success(['message' => __('Material deleted.', 'sanad-tracker')]);
        wp_die();
    }

    public function list(): void
    {
        check_ajax_referer('sanad_tracker_materials_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
            wp_die();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_materials';

        $materials = $wpdb->get_results(
            "SELECT id, name, slug FROM $table ORDER BY id DESC"
        );

        wp_send_json_success(['materials' => $materials]);
        wp_die();
    }
}
