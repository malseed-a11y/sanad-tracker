<?php

namespace SanadTracker\Database;

if (!defined('ABSPATH')) {
    exit;
}

class MaterialPriceRepository
{
    private string $table;

    private string $materialsTable;

    private string $regionsTable;

    public function __construct()
    {
        global $wpdb;

        $this->table          = $wpdb->prefix . 'sanad_tracker_material_prices';
        $this->materialsTable = $wpdb->prefix . 'sanad_tracker_materials';
        $this->regionsTable   = $wpdb->prefix . 'sanad_tracker_regions';
    }

    public function saveBatch(int $regionId, string $date, array $prices): int
    {
        global $wpdb;

        $inserted = 0;

        foreach ($prices as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $materialId = intval($entry['material_id'] ?? 0);
            $price      = isset($entry['price']) && $entry['price'] !== '' ? floatval($entry['price']) : null;

            if (!$materialId || $price === null) {
                continue;
            }

            $result = $wpdb->insert(
                $this->table,
                [
                    'region_id'   => $regionId,
                    'material_id' => $materialId,
                    'price'       => $price,
                    'date'        => $date,
                ],
                ['%d', '%d', '%f', '%s']
            );

            if ($result) {
                $inserted++;
            }
        }

        return $inserted;
    }

    public function delete(int $id): void
    {
        global $wpdb;

        $wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    public function getAdminList(int $regionId): array
    {
        global $wpdb;

        $materials = $wpdb->get_results(
            "SELECT id, name FROM {$this->materialsTable} ORDER BY name ASC"
        ) ?: [];

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT material_id, price, date
                FROM {$this->table}
                WHERE region_id = %d
                ORDER BY date DESC, material_id ASC",
                $regionId
            )
        ) ?: [];

        $matrix = [];
        foreach ($results as $row) {
            $date = $row->date;
            if (!isset($matrix[$date])) {
                $matrix[$date] = [
                    'date'   => $date,
                    'prices' => [],
                ];
            }
            $matrix[$date]['prices'][(int) $row->material_id] = $row->price;
        }

        return [
            'matrix'    => array_values($matrix),
            'materials' => $materials,
        ];
    }

    public function getLatestPrice(int $regionId, int $materialId): ?float
    {
        global $wpdb;

        $price = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT price FROM {$this->table}
                WHERE region_id = %d AND material_id = %d
                ORDER BY date DESC, id DESC LIMIT 1",
                $regionId,
                $materialId
            )
        );

        return $price !== null ? floatval($price) : null;
    }

    public function getChartData(int $regionId, int $materialId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(price) AS avg_price
                FROM {$this->table}
                WHERE region_id = %d AND material_id = %d
                  AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month ASC
                LIMIT 6",
                $regionId,
                $materialId
            )
        ) ?: [];
    }

    public function getIndicatorData(int $regionId, int $materialId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month, AVG(price) AS avg_price
                FROM {$this->table}
                WHERE region_id = %d AND material_id = %d
                  AND date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                  AND date <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                GROUP BY month
                ORDER BY month ASC",
                $regionId,
                $materialId
            )
        ) ?: [];
    }

    public function updateOrDeleteBatch(int $regionId, string $date, array $prices): array
    {
        global $wpdb;

        $updated = 0;
        $inserted = 0;
        $deleted = 0;

        if (empty($prices) || !is_array($prices)) {
            $wpdb->delete(
                $this->table,
                ['region_id' => $regionId, 'date' => $date],
                ['%d', '%s']
            );

            return ['updated' => 0, 'inserted' => 0, 'deleted' => 1];
        }

        foreach ($prices as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $materialId = intval($entry['material_id'] ?? 0);
            $price      = isset($entry['price']) && $entry['price'] !== '' ? floatval($entry['price']) : null;

            if (!$materialId) {
                continue;
            }

            if ($price === null) {
                $wpdb->delete(
                    $this->table,
                    [
                        'region_id'   => $regionId,
                        'material_id' => $materialId,
                        'date'        => $date,
                    ],
                    ['%d', '%d', '%s']
                );
                $deleted++;
            } else {
                $existingId = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$this->table}
                        WHERE region_id = %d AND material_id = %d AND date = %s",
                        $regionId,
                        $materialId,
                        $date
                    )
                );

                if ($existingId) {
                    $wpdb->update(
                        $this->table,
                        ['price' => $price],
                        ['id' => $existingId],
                        ['%f'],
                        ['%d']
                    );
                    $updated++;
                } else {
                    $wpdb->insert(
                        $this->table,
                        [
                            'region_id'   => $regionId,
                            'material_id' => $materialId,
                            'price'       => $price,
                            'date'        => $date,
                        ],
                        ['%d', '%d', '%f', '%s']
                    );
                    $inserted++;
                }
            }
        }

        return ['updated' => $updated, 'inserted' => $inserted, 'deleted' => $deleted];
    }
}
