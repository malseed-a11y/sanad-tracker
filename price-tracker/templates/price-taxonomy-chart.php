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
    <input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy_slug); ?>">
    <?php if (!empty($category_slug)): ?>
        <input type="hidden" name="category" value="<?php echo esc_attr($category_slug); ?>">
    <?php endif; ?>
    <button type="submit"><?php esc_html_e('Filter', 'price-tracker'); ?></button>
</form>

<canvas id="price-chart-<?php echo esc_attr($taxonomy_slug); ?>" width="400" height="200"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const allDates = <?php echo wp_json_encode($all_dates); ?>;
    const datasets = [
        <?php foreach ($category_data as $index => $cat): ?> {
                label: <?php echo wp_json_encode($cat['label']); ?>,
                data: allDates.map(d => {
                    const idx = <?php echo wp_json_encode($cat['dates']); ?>.indexOf(d);
                    return idx >= 0 ? <?php echo wp_json_encode($cat['prices']); ?>[idx] : null;
                }),
                borderColor: 'hsl(<?php echo $index * 60; ?>, 70%, 50%)',
                fill: false
            },
        <?php endforeach; ?>
    ];

    const ctx = document.getElementById('price-chart-<?php echo esc_js($taxonomy_slug); ?>').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: allDates,
            datasets: datasets.map(ds => ({
                ...ds,
                spanGaps: true
            }))
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