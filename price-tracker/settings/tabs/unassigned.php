<?php
if (!defined('ABSPATH')) {
    die;
}

global $wpdb;
$table_category = $wpdb->prefix . 'pt_category';
$table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
$table_tax_cat  = $wpdb->prefix . 'pt_taxonomy_category';

// Get all inactive taxonomy–category relations
$unassigned_relations = $wpdb->get_results("
    SELECT tc.id, t.name AS taxonomy_name, c.name AS category_name
    FROM $table_tax_cat tc
    INNER JOIN $table_taxonomy t ON tc.taxonomy_id = t.id
    INNER JOIN $table_category c ON tc.category_id = c.id
    WHERE tc.status = 'inactive'
    ORDER BY tc.id DESC
");
?>

<div id="unassigned" class="tab-content">
    <h3><?php esc_html_e('Not Assigned (Inactive Relations)', 'price-tracker'); ?></h3>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Taxonomy', 'price-tracker'); ?></th>
                <th><?php esc_html_e('Category', 'price-tracker'); ?></th>
                <th><?php esc_html_e('Actions', 'price-tracker'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($unassigned_relations)): ?>
                <?php foreach ($unassigned_relations as $row): ?>
                    <tr id="unassigned-row-<?php echo esc_attr($row->id); ?>">
                        <td><?php echo esc_html($row->taxonomy_name); ?></td>
                        <td><?php echo esc_html($row->category_name); ?></td>
                        <td>
                            <button class="reassign-unassigned button-primary"
                                data-unassigned-id="<?php echo esc_attr($row->id); ?>">
                                <?php esc_html_e('Re-Assign', 'price-tracker'); ?>
                            </button> | 
                            <button class="delete-unassigned button-secondary"
                                data-unassigned-id="<?php echo esc_attr($row->id); ?>">
                                <?php esc_html_e('Permanently Delete', 'price-tracker'); ?>
                            </button>
                        </td>

                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3"><?php esc_html_e('No inactive relations found.', 'price-tracker'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>