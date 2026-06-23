<?php

namespace PriceTrackerNamespace\classes;

if (!defined('ABSPATH')) {
    die;
}

class ShortcodeManager
{
    public function __construct()
    {
        // Register the shortcode early
        add_action('init', [$this, 'register_shortcodes']);
    }

    /**
     * Register the plugin's shortcodes.
     */
    public function register_shortcodes()
    {
        add_shortcode('price_tracker_table', [$this, 'render_price_table_shortcode']);
        add_shortcode('price_tracker_chart', [$this, 'render_price_chart_shortcode']);
        add_shortcode('price_tracker_insight', [$this, 'render_price_insight_shortcode']);
    }

    public function render_price_table_shortcode($atts)
    {
        global $wpdb;

        $atts = shortcode_atts([
            'taxonomy' => '', // taxonomy slug
            'title'    => ''
        ], $atts, 'price_tracker_table');

        $taxonomy_slug = sanitize_text_field($atts['taxonomy']);
        $table_title = sanitize_text_field($atts['title']);

        // Output date picker form and table container
        ob_start();
?>
        <style>
            <?php
            include PRICE_TRACKER_DIR . '/dist/css/frontend/price-table.min.css';
            include_once PRICE_TRACKER_DIR . '/dist/css/frontend/loader.min.css'; ?>
        </style>

        <div class="price-tracker-table-wrapper"
            data-taxonomy="<?php echo esc_attr($taxonomy_slug); ?>">

            <div class="price-tracker-table-title">
                <p><?php echo esc_html($table_title); ?></p>
                <svg width="15" height="15" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 4.98077C9.87594 4.98719 8.42567 5.09631 6.77219 6.75228C5.18289 8.34192 5.01818 9.74115 5.00321 9.96152C5.00321 9.74543 4.9369 8.45959 3.22995 6.75228C1.44385 4.9658 0.119786 4.9765 0 4.98077C0.124064 4.97436 1.57433 4.86524 3.22995 3.20927C4.88556 1.5533 4.98396 0.222537 4.99893 2.94149e-05C4.99893 0.211839 5.0631 1.49982 6.77219 3.20927C8.48128 4.91873 9.88235 4.98505 10 4.98077Z" fill="#00B3D3" />
                </svg>
            </div>
            <div class="price-tracker-loader">
                <div class="lds-roller">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
            <form class="price-tracker-table-filter">
                <div class="price-tracker-label-wrapper">

                    <label><?php esc_html_e('Date: ', 'price-tracker'); ?>
                        <input type="date" name="the_date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                    </label>
                    <label><?php esc_html_e('Range: ', 'price-tracker'); ?>
                        <select name="range" id="range">
                            <option value="daily" selected><?php esc_html_e('Daily', 'price-tracker'); ?></option>
                            <option value="yearly"><?php esc_html_e('Yearly', 'price-tracker'); ?></option>
                            <option value="monthly"><?php esc_html_e('Monthly', 'price-tracker'); ?></option>
                            <option value="weekly"><?php esc_html_e('Weekly', 'price-tracker'); ?></option>
                        </select>
                    </label>
                </div>
                <button type="submit">
                    <?php esc_html_e('Filter', 'price-tracker'); ?>
                    <svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.91624 1.41418L1.71338 6.61704L6.91624 11.8199" stroke="#F3F6FD" stroke-width="2" stroke-miterlimit="10" stroke-linecap="square" stroke-linejoin="round" />
                        <path d="M16.2851 7.617H17.2851V5.617H16.2851V6.617V7.617ZM1.85938 5.617C1.30709 5.617 0.859375 6.06472 0.859375 6.617C0.859375 7.16929 1.30709 7.617 1.85938 7.617V6.617V5.617ZM16.2851 6.617V5.617H1.85938V6.617V7.617H16.2851V6.617Z" fill="#F3F6FD" />
                    </svg>
                </button>
            </form>
            <div class="price-tracker-table-container">
                <p><?php esc_html_e('Select a date range to view data.', 'price-tracker'); ?></p>
            </div>
        </div>
    <?php

        // Enqueue JS
        $this->enqueue_price_tracker_table_assets();

        return ob_get_clean();
    }

    public function render_price_chart_shortcode($atts)
    {
        $atts = shortcode_atts([
            'taxonomy' => '',
            'category' => '',
            'title'    => '',
        ], $atts, 'price_chart');

        $taxonomy_slug = sanitize_text_field($atts['taxonomy']);
        $category_slug = sanitize_text_field($atts['category']);
        $chart_title   = sanitize_text_field($atts['title']);

        if (empty($taxonomy_slug)) {
            return '<p>' . esc_html__('Taxonomy is required.', 'price-tracker') . '</p>';
        }


        ob_start();

    ?>
        <style>
            <?php
            include_once PRICE_TRACKER_DIR . '/dist/css/frontend/price-chart.min.css';
            include_once PRICE_TRACKER_DIR . '/dist/css/frontend/loader.min.css'; ?>
        </style>

        <div class="price-tracker-chart-wrapper"
            data-taxonomy="<?php echo esc_attr($taxonomy_slug); ?>"
            data-category="<?php echo esc_attr($category_slug); ?>">
            <div class="price-tracker-chart-title">
                <p><?php echo esc_html($chart_title); ?></p>
                <svg width="15" height="15" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 4.98077C9.87594 4.98719 8.42567 5.09631 6.77219 6.75228C5.18289 8.34192 5.01818 9.74115 5.00321 9.96152C5.00321 9.74543 4.9369 8.45959 3.22995 6.75228C1.44385 4.9658 0.119786 4.9765 0 4.98077C0.124064 4.97436 1.57433 4.86524 3.22995 3.20927C4.88556 1.5533 4.98396 0.222537 4.99893 2.94149e-05C4.99893 0.211839 5.0631 1.49982 6.77219 3.20927C8.48128 4.91873 9.88235 4.98505 10 4.98077Z" fill="#00B3D3" />
                </svg>
            </div>

            <form class="price-tracker-chart-filter" style="margin-bottom:20px;">
                <div class="price-tracker-label-wrapper">
                    <label>
                        <?php esc_html_e('From:', 'price-tracker'); ?>
                        <input type="date" name="from" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                    </label>
                    <label>
                        <?php esc_html_e('To:', 'price-tracker'); ?>
                        <input type="date" name="to" value="<?php echo esc_attr(date('Y-m-d')); ?>">

                    </label>
                </div>
                <button type="submit"><?php esc_html_e('Filter', 'price-tracker'); ?>
                    <svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.91624 1.41418L1.71338 6.61704L6.91624 11.8199" stroke="#F3F6FD" stroke-width="2" stroke-miterlimit="10" stroke-linecap="square" stroke-linejoin="round" />
                        <path d="M16.2851 7.617H17.2851V5.617H16.2851V6.617V7.617ZM1.85938 5.617C1.30709 5.617 0.859375 6.06472 0.859375 6.617C0.859375 7.16929 1.30709 7.617 1.85938 7.617V6.617V5.617ZM16.2851 6.617V5.617H1.85938V6.617V7.617H16.2851V6.617Z" fill="#F3F6FD" />
                    </svg>
                </button>
            </form>

            <canvas width="400" height="200"></canvas>

            <div class="price-tracker-loader">
                <div class="lds-roller">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>

            <div class="price-tracker-chart-information-area"></div>
        </div>
        <?php $this->enqueue_price_tracker_chart_assets(); ?>
    <?php
        return ob_get_clean();
    }


    public function render_price_insight_shortcode($atts)
    {
        global $wpdb;

        $atts = shortcode_atts([
            'taxonomy' => '', // taxonomy slug
            'title'    => ''
        ], $atts, 'price_insight');

        $taxonomy_slug = sanitize_text_field($atts['taxonomy']);
        $price_insight_title = sanitize_text_field($atts['title']);
        ob_start();

    ?>

        <style>
            <?php
            include PRICE_TRACKER_DIR . '/dist/css/frontend/price-insight.min.css';
            include_once PRICE_TRACKER_DIR . '/dist/css/frontend/loader.min.css'; ?>
        </style>
        <div class="price-tracker-insight-wrapper">
            <div class="price-tracker-loader">
                <div class="lds-roller">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
            <div class="price-tracker-insight-table-wrapper"
                data-taxonomy="<?php echo esc_attr($taxonomy_slug); ?>" style="margin-bottom: 0px;">

                <div class="price-tracker-insight-title">
                    <p><?php echo esc_html($price_insight_title); ?></p>
                    <svg width="15" height="15" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 4.98077C9.87594 4.98719 8.42567 5.09631 6.77219 6.75228C5.18289 8.34192 5.01818 9.74115 5.00321 9.96152C5.00321 9.74543 4.9369 8.45959 3.22995 6.75228C1.44385 4.9658 0.119786 4.9765 0 4.98077C0.124064 4.97436 1.57433 4.86524 3.22995 3.20927C4.88556 1.5533 4.98396 0.222537 4.99893 2.94149e-05C4.99893 0.211839 5.0631 1.49982 6.77219 3.20927C8.48128 4.91873 9.88235 4.98505 10 4.98077Z" fill="#00B3D3" />
                    </svg>
                </div>

                <form class="price-tracker-insight-filter">
                    <div class="price-tracker-label-wrapper">
                        <label><?php esc_html_e('Date: ', 'price-tracker'); ?> <input type="date" name="the_date" value="<?php echo esc_attr(date('Y-m-d')); ?>"></label>
                        <label><?php esc_html_e('Range: ', 'price-tracker'); ?> <select name="range" id="range">
                                <option value="daily" selected><?php esc_html_e('Daily', 'price-tracker'); ?></option>
                                <option value="yearly"><?php esc_html_e('Yearly', 'price-tracker'); ?></option>
                                <option value="monthly"><?php esc_html_e('Monthly', 'price-tracker'); ?></option>
                                <option value="weekly"><?php esc_html_e('Weekly', 'price-tracker'); ?></option>
                            </select></label>
                    </div>
                    <button type="submit">
                        <?php esc_html_e('Filter', 'price-tracker'); ?>

                        <svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.91624 1.41418L1.71338 6.61704L6.91624 11.8199" stroke="#F3F6FD" stroke-width="2" stroke-miterlimit="10" stroke-linecap="square" stroke-linejoin="round" />
                            <path d="M16.2851 7.617H17.2851V5.617H16.2851V6.617V7.617ZM1.85938 5.617C1.30709 5.617 0.859375 6.06472 0.859375 6.617C0.859375 7.16929 1.30709 7.617 1.85938 7.617V6.617V5.617ZM16.2851 6.617V5.617H1.85938V6.617V7.617H16.2851V6.617Z" fill="#F3F6FD" />
                        </svg>
                    </button>
                </form>

                <div class="price-tracker-insight-table">
                    <p><?php esc_html_e('Select a date range to view data.', 'price-tracker'); ?></p>
                </div>
            </div>


            <div class="price-tracker-insight-chart-wrapper" style="margin-top: 0px;">

                <canvas width="400" height="200"></canvas>

                <div class="price-tracker-insight-chart-information-area">

                </div>
            </div>

            <?php $this->enqueue_price_tracker_insight_assets(); ?>
        </div>
<?php
        return ob_get_clean();
    }





    public function enqueue_price_tracker_table_assets()
    {
        wp_enqueue_script('price-tracker-table-ajax', PRICE_TRACKER_URL . 'dist/js/frontend/price-tracker-table.min.js', ['jquery'], PRICE_TRACKER_VERSION, true);

        // Pass ajax URL
        wp_localize_script('price-tracker-table-ajax', 'PriceTrackerTableAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('price_tracker_table_nonce'),
        ]);
    }




    public function enqueue_price_tracker_chart_assets()
    {
        wp_enqueue_script(
            'price-tracker-chart-js-library',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            PRICE_TRACKER_VERSION,
            false
        );

        wp_enqueue_script(
            'price-tracker-chart-ajax',
            PRICE_TRACKER_URL . 'dist/js/frontend/price-tracker-chart.min.js',
            ['jquery'],
            PRICE_TRACKER_VERSION,
            true
        );

        $localization_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('price_tracker_chart_nonce'),
        ];

        $final_data = array_merge($localization_data, $this->price_localize_data());

        wp_localize_script('price-tracker-chart-ajax', 'PriceTrackerChartAjax', $final_data);
    }


    public function enqueue_price_tracker_insight_assets()
    {
        wp_enqueue_script('price-tracker-insight-js-library',  'https://cdn.jsdelivr.net/npm/chart.js', [], PRICE_TRACKER_VERSION, false);

        wp_enqueue_script(
            'price-tracker-insight-ajax',
            PRICE_TRACKER_URL . 'dist/js/frontend/price-tracker-insight.min.js',
            ['jquery', 'price-tracker-insight-js-library'],
            PRICE_TRACKER_VERSION,
            true
        );

        $localization_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('price_tracker_insight_nonce'),
        ];

        $final_data = array_merge($localization_data, $this->price_localize_data());

        wp_localize_script('price-tracker-insight-ajax', 'PriceTrackerInsightAjax', $final_data);
    }

    private function price_localize_data()
    {

        return [
            'locale'   => str_replace('_', '-', get_locale()),
            'i18n'     => [
                'loading'       => esc_attr__('Loading...', 'price-tracker'),
                'network_error' => esc_attr__('Network error. Please try again.', 'price-tracker'),
                'no_data'       => esc_attr__('No data found.', 'price-tracker'),
                'chart_title'   => esc_attr__('Price Chart', 'price-tracker'),
                'axis_date'     => esc_attr__('Date', 'price-tracker'),
                'axis_price'    => esc_attr__('Price', 'price-tracker'),
            ],
        ];
    }
}
