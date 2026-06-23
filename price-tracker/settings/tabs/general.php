<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    echo  "<p>" . esc_html__('Just administrators can edit general settings.', 'price-tracker') . "</p>";
    return;
}

global $wp_roles;

$delete_data_on_uninstall = get_option('price-tracker-delete-data-on-uninstall', false);
$allowed_roles = get_option('price-tracker-allowed-roles', ['administrator']);
?>

<div id="general" class="tab-content">
    <h3><?php esc_html_e('General Settings', 'price-tracker'); ?></h3>

    <!-- Delete data -->
    <div class="setting">
        <label>
            <input type="checkbox"
                name="price-tracker-delete-data-on-uninstall"
                <?php checked($delete_data_on_uninstall, 'on'); ?>>
            <?php esc_html_e('Remove all plugin data when uninstalling.', 'price-tracker'); ?>
        </label>
    </div>

    <hr>

    <!-- Role access -->
    <h4><?php esc_html_e('Who can access Price Tracker?', 'price-tracker'); ?></h4>

    <?php foreach ($wp_roles->roles as $role_key => $role) : ?>
        <label style="display:block; margin-bottom:6px;">
            <input type="checkbox"
                name="price-tracker-allowed-roles[]"
                value="<?php echo esc_attr($role_key); ?>"
                <?php checked(in_array($role_key, $allowed_roles, true)); ?>>
            <?php echo esc_html($role['name']); ?>
        </label>
    <?php endforeach; ?>
</div>