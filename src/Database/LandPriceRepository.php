<?php

namespace SanadTracker\Database;

if (!defined('ABSPATH')) {
    exit;
}

class LandPriceRepository
{
    private string $table;

    private string $regionsTable;

    public function __construct()
    {
        global $wpdb;

        $this->table        = $wpdb->prefix . 'sanad_tracker_land_prices';
        $this->regionsTable = $wpdb->prefix . 'sanad_tracker_regions';
    }

    public function save(int $regionId, string $date, float $shellCore, float $fullyFinished): int
    {
        global $wpdb;

        $wpdb->insert(
            $this->table,
            [
                'region_id'            => $regionId,
                'shell_core_price'     => $shellCore,
                'fully_finished_price' => $fullyFinished,
                'date'                 => $date,
            ],
            ['%d', '%f', '%f', '%s']
        );

        return $wpdb->insert_id;
    }

    public function delete(int $id): void
    {
        global $wpdb;

        $wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    public function update(int $id, float $shellCore, float $fullyFinished): void
    {
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'shell_core_price'     => $shellCore,
                'fully_finished_price' => $fullyFinished,
            ],
            ['id' => $id],
            ['%f', '%f'],
            ['%d']
        );
    }

    public function getAdminList(int $regionId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, region_id, shell_core_price, fully_finished_price, date
                FROM {$this->table}
                WHERE region_id = %d
                ORDER BY date DESC, id DESC
                LIMIT 20",
                $regionId
            )
        ) ?: [];
    }

    public function getLatestPrices(int $regionId): ?object
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT shell_core_price, fully_finished_price, date
                FROM {$this->table}
                WHERE region_id = %d
                ORDER BY date DESC, id DESC
                LIMIT 1",
                $regionId
            )
        ) ?: null;
    }

    public function getChartData(int $regionId, string $column): array
    {
        global $wpdb;

        $allowed = ['shell_core_price', 'fully_finished_price'];
        $column  = in_array($column, $allowed, true) ? $column : 'shell_core_price';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG({$column}) AS avg_price
                FROM {$this->table}
                WHERE region_id = %d AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month ASC
                LIMIT 6",
                $regionId
            )
        ) ?: [];
    }

    public function getIndicatorData(int $regionId, string $column): array
    {
        global $wpdb;

        $allowed = ['shell_core_price', 'fully_finished_price'];
        $column  = in_array($column, $allowed, true) ? $column : 'shell_core_price';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG({$column}) AS avg_price
                FROM {$this->table}
                WHERE region_id = %d
                  AND date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                  AND date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                GROUP BY month ORDER BY month ASC",
                $regionId
            )
        ) ?: [];
    }
}
