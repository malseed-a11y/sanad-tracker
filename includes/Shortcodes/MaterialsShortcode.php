<?php
namespace SanadTracker\Shortcodes;

if (!defined('ABSPATH')) {
    exit;
}

class MaterialsShortcode
{
    public function __construct()
    {
        add_shortcode('sanad_materials', [$this, 'render']);
    }

    public function render($atts): string
    {
        $atts = shortcode_atts([
            'region' => '',
            'title'  => __('Material Prices', 'sanad-tracker'),
        ], $atts, 'sanad_materials');

        $has_region = !empty($atts['region']);

        wp_enqueue_style('sanad-tracker-table', SANAD_TRACKER_URL . 'assets/css/frontend/sanad-table.css', [], SANAD_TRACKER_VERSION);
        wp_enqueue_style('sanad-tracker-chart', SANAD_TRACKER_URL . 'assets/css/frontend/sanad-chart.css', [], SANAD_TRACKER_VERSION);

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);

        wp_enqueue_script(
            'sanad-tracker-materials',
            SANAD_TRACKER_URL . 'assets/js/frontend/sanad-materials.js',
            ['chart-js'],
            SANAD_TRACKER_VERSION,
            true
        );

        wp_localize_script('sanad-tracker-materials', 'SanadTrackerMaterials', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sanad_tracker_frontend_materials_nonce'),
            'i18n'     => [
                'loading'       => __('Loading...', 'sanad-tracker'),
                'error'         => __('An error occurred.', 'sanad-tracker'),
                'no_data'       => __('No data available.', 'sanad-tracker'),
                'select_region' => __('Select a region', 'sanad-tracker'),
                'material'      => __('Material', 'sanad-tracker'),
                'latest_price'  => __('Latest Price', 'sanad-tracker'),
                'indicator'     => __('Indicator', 'sanad-tracker'),
                'price_chart'   => __('Price Chart', 'sanad-tracker'),
            ],
        ]);

        $region_style = $has_region ? 'style="display:none"' : '';

        ob_start();
        ?>
        <div class="sanad-materials-wrapper" data-region="<?php echo esc_attr($atts['region']); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sanad-region-selector" <?php echo $region_style; ?>>
                <select class="sanad-region-select">
                    <option value=""><?php esc_html_e('Select a region', 'sanad-tracker'); ?></option>
                </select>
            </div>
            <div class="sanad-loader"></div>
            <div class="sanad-materials-table-container"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
