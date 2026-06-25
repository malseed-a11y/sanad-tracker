<?php
namespace SanadTracker\Admin\Tabs;

if (!defined('ABSPATH')) exit;

class MaterialsTab
{
    public function render(): string
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sanad_tracker_materials';

        $materials = $wpdb->get_results(
            "SELECT id, name, slug FROM $table ORDER BY id DESC"
        );

        ob_start();
        ?>
        <div id="materials-tab" class="sanad-tab-content">
            <h3><?php esc_html_e('Manage Materials', 'sanad-tracker'); ?></h3>

            <form method="post" class="sanad-add-form" id="add-material-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="material_name"><?php esc_html_e('Material Name', 'sanad-tracker'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="name" id="material_name" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Add Material', 'sanad-tracker')); ?>
            </form>

            <hr>

            <div id="edit-material-wrapper" style="display:none; margin-top:20px;">
                <h3><?php esc_html_e('Edit Material', 'sanad-tracker'); ?></h3>
                <form method="post" class="sanad-edit-form" id="edit-material-form">
                    <input type="hidden" name="edit_material_id" id="edit_material_id">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="edit_material_name"><?php esc_html_e('Material Name', 'sanad-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="edit_material_name" id="edit_material_name" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Update Material', 'sanad-tracker'), 'primary', 'update_material_btn'); ?>
                    <button type="button" id="cancel-edit-material" class="button-secondary">
                        <?php esc_html_e('Cancel', 'sanad-tracker'); ?>
                    </button>
                </form>
            </div>

            <hr>

            <h3><?php esc_html_e('Existing Materials', 'sanad-tracker'); ?></h3>
            <table class="widefat striped sanad-list-table" id="materials-list-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'sanad-tracker'); ?></th>
                        <th><?php esc_html_e('Name', 'sanad-tracker'); ?></th>
                        <th><?php esc_html_e('Slug', 'sanad-tracker'); ?></th>
                        <th><?php esc_html_e('Actions', 'sanad-tracker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($materials)): ?>
                        <?php foreach ($materials as $material): ?>
                            <tr data-material-id="<?php echo esc_attr($material->id); ?>">
                                <td><?php echo esc_html($material->id); ?></td>
                                <td class="material-name"><?php echo esc_html($material->name); ?></td>
                                <td class="material-slug"><?php echo esc_html($material->slug); ?></td>
                                <td>
                                    <button type="button" class="button-secondary edit-material-btn"
                                        data-id="<?php echo esc_attr($material->id); ?>"
                                        data-name="<?php echo esc_attr($material->name); ?>">
                                        <?php esc_html_e('Edit', 'sanad-tracker'); ?>
                                    </button>
                                    |
                                    <button type="button" class="button-secondary delete-material-btn"
                                        data-id="<?php echo esc_attr($material->id); ?>">
                                        <?php esc_html_e('Delete', 'sanad-tracker'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No materials found.', 'sanad-tracker'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php

        return ob_get_clean();
    }
}
