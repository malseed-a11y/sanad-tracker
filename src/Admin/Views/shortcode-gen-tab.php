<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="shortcode-gen-tab" class="sanad-tab-content">
    <h3><?php esc_html_e('Shortcodes', 'sanad-tracker'); ?></h3>
    <p><?php esc_html_e('Copy and paste these shortcodes into any post or page.', 'sanad-tracker'); ?></p>

    <div class="sanad-general-shortcode-box">
        <label><?php esc_html_e('Material Prices Table', 'sanad-tracker'); ?></label>
        <input type="text" class="sanad-general-shortcode-input" value="[sanad_materials]" readonly onfocus="this.select()">
    </div>

    <div class="sanad-general-shortcode-box">
        <label><?php esc_html_e('Land Prices Table', 'sanad-tracker'); ?></label>
        <input type="text" class="sanad-general-shortcode-input" value="[sanad_land]" readonly onfocus="this.select()">
    </div>
</div>
