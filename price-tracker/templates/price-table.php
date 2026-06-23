<table class="price-tracker-table" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th><?php esc_html_e('Category', 'price-tracker'); ?></th>
            <th><?php esc_html_e('Buy Price', 'price-tracker'); ?></th>
            <th><?php esc_html_e('Sell Price', 'price-tracker'); ?></th>
            <th><?php esc_html_e('Rate of Change', 'price-tracker'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php

        foreach ($categories as $cat) {
            $query_args = [$cat->taxonomy_category_id];
            $query = "
                SELECT buy_price, sell_price, date
                FROM {$wpdb->prefix}pt_items
                WHERE taxonomy_category_id = %d
            ";

            if ($date || $range) {
                // Change the prev_date based on the range if daily or weekly or monthly or yearly
                switch ($range) {
                    case 'daily':
                        $prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
                        break;
                    case 'weekly':
                        $prev_date = date('Y-m-d', strtotime($date . ' -7 day'));
                        break;
                    case 'monthly':
                        $prev_date = date('Y-m-d', strtotime($date . ' -1 month'));
                        break;
                    case 'yearly':
                        $prev_date = date('Y-m-d', strtotime($date . ' -1 year'));
                        break;
                    default:
                        $prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
                }
                $date = empty($date) ? date('Y-m-d') : $date; // Set date to today if not set

                // First query: get record for the requested date
                $query_date = $query . " AND date = %s ORDER BY id DESC LIMIT 1";
                $item_date = $wpdb->get_row($wpdb->prepare($query_date, $cat->taxonomy_category_id, $date));

                // Second query: get record for the previous date
                $query_prev = $query . " AND date = %s ORDER BY id DESC LIMIT 1";
                $item_prev = $wpdb->get_row($wpdb->prepare($query_prev, $cat->taxonomy_category_id, $prev_date));

                // Merge results into one array
                $items = [];
                if ($item_date) $items[] = $item_date;
                if ($item_prev) $items[] = $item_prev;
            } else {
                // Default: just get the last 2 records
                $query .= " ORDER BY date DESC LIMIT 2";
                $items = $wpdb->get_results($wpdb->prepare($query, ...$query_args));
            }

            if (empty($items)) {
                continue;
            }

            $last_item = $items[0];
            $prev_item = $items[1] ?? null;

            $rate_of_change = 0;
            if ($prev_item && $prev_item->sell_price != 0) {
                $rate_of_change = round(
                    (($last_item->sell_price - $prev_item->sell_price) / $prev_item->sell_price) * 100,
                    2
                );
            }

            $rate_class = $rate_of_change > 0 ? 'positive' : ($rate_of_change < 0 ? 'negative' : 'neutral');

            $rate_display = $rate_of_change == 0
                ? '0.00%'
                : sprintf('%s%.2f%%', ($rate_of_change > 0 ? '+' : '-'), abs($rate_of_change));

        ?>

            <tr>
                <td class="cat-title-name"><?php echo esc_html($cat->name); ?></td>
                <td><?php echo esc_html($last_item->buy_price); ?></td>
                <td><?php echo esc_html($last_item->sell_price); ?></td>

                <td class="<?php echo esc_attr($rate_class); ?>">
                    <?php echo esc_html($rate_display); ?>
                </td>


            </tr>
        <?php
            $is_there_something_to_show = true;
        } ?>

    </tbody>
</table>