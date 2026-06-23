<p style="font-size: 24px;"><?php echo esc_html($chart_title); ?></p>
<form method="get" class="price-chart-filter" style="margin-bottom:20px;">
    <label>
        <?php esc_html_e('From:', 'price-tracker'); ?>
        <input type="date" name="from" value="<?php echo esc_attr($from_date); ?>">
    </label>
    <label>
        <?php esc_html_e('To:', 'price-tracker'); ?>
        <input type="date" name="to" value="<?php echo esc_attr($to_date); ?>">
    </label>
    <button type="submit"><?php esc_html_e('Filter', 'price-tracker'); ?></button>
</form>

<canvas id="price-chart-<?php echo esc_attr($id_suffix); ?>" width="400" height="200"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx<?php echo esc_js($id_suffix); ?> = document.getElementById('price-chart-<?php echo esc_js($id_suffix); ?>').getContext('2d');
    new Chart(ctx<?php echo esc_js($id_suffix); ?>, {
        type: 'line',
        data: {
            labels: <?php echo wp_json_encode($dates); ?>,
            datasets: [{
                    label: 'Buy Price',
                    data: <?php echo wp_json_encode($buy_prices); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: false
                },
                {
                    label: 'Sell Price',
                    data: <?php echo wp_json_encode($sell_prices); ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Price'
                    }
                }
            }
        }
    });
</script>