<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="regions-tab" class="sanad-tab-content">
    <h3><?php esc_html_e('Manage Regions', 'sanad-tracker'); ?></h3>

    <form method="post" class="sanad-add-form" id="add-region-form">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="region_name"><?php esc_html_e('Region Name', 'sanad-tracker'); ?></label>
                </th>
                <td>
                    <input type="text" name="name" id="region_name" class="regular-text" required>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Add Region', 'sanad-tracker')); ?>
    </form>

    <hr>

    <div id="edit-region-wrapper" style="display:none; margin-top:20px;">
        <h3><?php esc_html_e('Edit Region', 'sanad-tracker'); ?></h3>
        <form method="post" class="sanad-edit-form" id="edit-region-form">
            <input type="hidden" name="edit_region_id" id="edit_region_id">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="edit_region_name"><?php esc_html_e('Region Name', 'sanad-tracker'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="edit_region_name" id="edit_region_name" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Update Region', 'sanad-tracker'), 'primary', 'update_region_btn'); ?>
            <button type="button" id="cancel-edit-region" class="button-secondary">
                <?php esc_html_e('Cancel', 'sanad-tracker'); ?>
            </button>
        </form>
    </div>

    <hr>

    <h3><?php esc_html_e('Existing Regions', 'sanad-tracker'); ?></h3>
    <table class="widefat striped sanad-list-table" id="regions-list-table">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'sanad-tracker'); ?></th>
                <th><?php esc_html_e('Name', 'sanad-tracker'); ?></th>
                <th><?php esc_html_e('Slug', 'sanad-tracker'); ?></th>
                <th><?php esc_html_e('Actions', 'sanad-tracker'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($regions)): ?>
                <?php foreach ($regions as $region): ?>
                    <tr data-region-id="<?php echo esc_attr($region->id); ?>">
                        <td><?php echo esc_html($region->id); ?></td>
                        <td class="region-name"><?php echo esc_html($region->name); ?></td>
                        <td class="region-slug"><?php echo esc_html($region->slug); ?></td>
                        <td>
                            <button type="button" class="button-secondary edit-region-btn"
                                data-id="<?php echo esc_attr($region->id); ?>"
                                data-name="<?php echo esc_attr($region->name); ?>">
                                <?php esc_html_e('Edit', 'sanad-tracker'); ?>
                            </button>
                            |
                            <button type="button" class="button-secondary delete-region-btn"
                                data-id="<?php echo esc_attr($region->id); ?>">
                                <?php esc_html_e('Delete', 'sanad-tracker'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4"><?php esc_html_e('No regions found.', 'sanad-tracker'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
