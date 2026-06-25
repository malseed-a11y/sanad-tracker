<?php
namespace SanadTracker\Shortcodes;

if (!defined('ABSPATH')) {
    exit;
}

class LandShortcode
{
    public function __construct()
    {
        add_shortcode('sanad_land', [$this, 'render']);
    }

    public function render($atts): string
    {
        $atts = shortcode_atts([
            'region' => '',
            'title'  => '',
        ], $atts, 'sanad_land');

        $region_slug = sanitize_title($atts['region']);
        $title       = sanitize_text_field($atts['title']);

        $this->enqueueAssets($region_slug);

        ob_start();
        ?>
        <div class="sanad-land-wrapper" data-region="<?php echo esc_attr($region_slug); ?>">
            <h3><?php echo esc_html($title); ?></h3>
            <?php if (empty($region_slug)): ?>
            <div class="sanad-region-selector">
                <select class="sanad-region-select">
                    <option value=""><?php esc_html_e('Select a region...', 'sanad-tracker'); ?></option>
                </select>
            </div>
            <?php endif; ?>
            <div class="sanad-loader"><?php esc_html_e('Loading...', 'sanad-tracker'); ?></div>
            <div class="sanad-land-table-container"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function enqueueAssets(string $region_slug): void
    {
        wp_enqueue_style(
            'sanad-tracker-table',
            SANAD_TRACKER_URL . 'assets/css/frontend/sanad-table.css',
            [],
            SANAD_TRACKER_VERSION
        );

        wp_enqueue_style(
            'sanad-tracker-chart',
            SANAD_TRACKER_URL . 'assets/css/frontend/sanad-chart.css',
            [],
            SANAD_TRACKER_VERSION
        );

        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'sanad-tracker-land',
            SANAD_TRACKER_URL . 'assets/js/frontend/sanad-land.js',
            ['chart-js'],
            SANAD_TRACKER_VERSION,
            true
        );

        wp_localize_script('sanad-tracker-land', 'SanadTrackerLand', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sanad_tracker_frontend_land_nonce'),
            'region'   => $region_slug,
            'i18n'     => [
                'loading'       => __('Loading...', 'sanad-tracker'),
                'error'         => __('Error loading data.', 'sanad-tracker'),
                'no_data'       => __('No data available for this region.', 'sanad-tracker'),
                'shell_core'    => __('Shell & Core', 'sanad-tracker'),
                'fully_finished'=> __('Fully Finished', 'sanad-tracker'),
                'latest_price'  => __('Latest Price', 'sanad-tracker'),
                'indicator'     => __('Trend', 'sanad-tracker'),
                'select_region' => __('Select a region...', 'sanad-tracker'),
                'type'          => __('Type', 'sanad-tracker'),
            ],
        ]);
    }
}
