<?php

namespace SanadTracker\Admin\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

use SanadTracker\Database\MaterialRepository;

class MaterialAdminAjax
{
    private MaterialRepository $repository;

    public function __construct()
    {
        $this->repository = new MaterialRepository();

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
        }

        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = sanitize_title($name);

        if (empty($name)) {
            wp_send_json_error(['message' => __('Material name is required.', 'sanad-tracker')]);
        }

        if ($this->repository->existsBySlug($slug)) {
            wp_send_json_error(['message' => __('Material with this name already exists.', 'sanad-tracker')]);
        }

        $id = $this->repository->create($name, $slug);

        wp_send_json_success([
            'message' => __('Material added.', 'sanad-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
    }

    public function edit(): void
    {
        check_ajax_referer('sanad_tracker_materials_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $id   = intval($_POST['id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = sanitize_title($name);

        if (!$id || empty($name)) {
            wp_send_json_error(['message' => __('Invalid data.', 'sanad-tracker')]);
        }

        if ($this->repository->existsBySlug($slug, $id)) {
            wp_send_json_error(['message' => __('Material with this name already exists.', 'sanad-tracker')]);
        }

        $this->repository->update($id, $name, $slug);

        wp_send_json_success([
            'message' => __('Material updated.', 'sanad-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
    }

    public function delete(): void
    {
        check_ajax_referer('sanad_tracker_materials_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid material ID.', 'sanad-tracker')]);
        }

        $this->repository->delete($id);

        wp_send_json_success(['message' => __('Material deleted.', 'sanad-tracker')]);
    }

    public function list(): void
    {
        check_ajax_referer('sanad_tracker_materials_nonce', 'nonce');

        if (!current_user_can('sanad_tracker_access')) {
            wp_send_json_error(['message' => __('No permission.', 'sanad-tracker')]);
        }

        $materials = $this->repository->getAll();

        wp_send_json_success(['materials' => $materials]);
    }
}
