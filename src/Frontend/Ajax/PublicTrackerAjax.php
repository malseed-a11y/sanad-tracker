<?php

namespace SanadTracker\Frontend\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

use SanadTracker\Database\RegionRepository;
use SanadTracker\Database\MaterialPriceRepository;
use SanadTracker\Database\LandPriceRepository;

class PublicTrackerAjax
{
    private RegionRepository $regionRepo;

    private MaterialPriceRepository $materialPriceRepo;

    private LandPriceRepository $landPriceRepo;

    public function __construct()
    {
        $this->regionRepo        = new RegionRepository();
        $this->materialPriceRepo = new MaterialPriceRepository();
        $this->landPriceRepo     = new LandPriceRepository();

        add_action('wp_ajax_sanad_tracker_get_regions_list', [$this, 'getRegionsList']);
        add_action('wp_ajax_nopriv_sanad_tracker_get_regions_list', [$this, 'getRegionsList']);

        add_action('wp_ajax_sanad_tracker_get_materials_table', [$this, 'getMaterialsTable']);
        add_action('wp_ajax_nopriv_sanad_tracker_get_materials_table', [$this, 'getMaterialsTable']);

        add_action('wp_ajax_sanad_tracker_get_land_table', [$this, 'getLandTable']);
        add_action('wp_ajax_nopriv_sanad_tracker_get_land_table', [$this, 'getLandTable']);
    }

    public function getRegionsList(): void
    {
        $regions = $this->regionRepo->getAllOrderedByName();

        wp_send_json_success(['regions' => $regions]);
    }

    public function getMaterialsTable(): void
    {
        check_ajax_referer('sanad_tracker_frontend_materials_nonce', 'nonce');

        $regionId = intval($_POST['region_id'] ?? 0);

        if (!$regionId) {
            wp_send_json_success(['rows' => []]);
        }

        global $wpdb;
        $materialsTable = $wpdb->prefix . 'sanad_tracker_materials';

        $materials = $wpdb->get_results(
            "SELECT id, name, slug FROM {$materialsTable} ORDER BY name ASC"
        );

        if (empty($materials)) {
            wp_send_json_success(['rows' => []]);
        }

        $rows = [];

        foreach ($materials as $material) {
            $materialId = (int) $material->id;

            $latestPrice = $this->materialPriceRepo->getLatestPrice($regionId, $materialId);

            $chartData = $this->materialPriceRepo->getChartData($regionId, $materialId);

            $indicatorResults = $this->materialPriceRepo->getIndicatorData($regionId, $materialId);

            $indicatorDir = 'neutral';
            $indicatorPct = null;

            if (count($indicatorResults) === 2) {
                $prevAvg = floatval($indicatorResults[0]->avg_price);
                $currAvg = floatval($indicatorResults[1]->avg_price);

                if ($prevAvg > 0) {
                    $indicatorPct = round((($currAvg - $prevAvg) / $prevAvg) * 100, 1);
                }

                if ($currAvg > $prevAvg) {
                    $indicatorDir = 'up';
                } elseif ($currAvg < $prevAvg) {
                    $indicatorDir = 'down';
                } else {
                    $indicatorDir = 'neutral';
                    $indicatorPct = 0.0;
                }
            }

            $chartDataArray = [];
            foreach ($chartData as $cr) {
                $chartDataArray[] = [
                    'month' => $cr->month,
                    'avg'   => floatval($cr->avg_price),
                ];
            }

            $rows[] = [
                'material_id'   => $materialId,
                'material_name' => $material->name,
                'latest_price'  => $latestPrice !== null ? floatval($latestPrice) : null,
                'indicator_dir' => $indicatorDir,
                'indicator_pct' => $indicatorPct,
                'chart_data'    => $chartDataArray,
            ];
        }

        wp_send_json_success(['rows' => $rows]);
    }

    public function getLandTable(): void
    {
        check_ajax_referer('sanad_tracker_frontend_land_nonce', 'nonce');

        $regionId = intval($_POST['region_id'] ?? 0);

        if (!$regionId) {
            wp_send_json_error(['message' => __('Invalid region.', 'sanad-tracker')]);
        }

        $latest = $this->landPriceRepo->getLatestPrices($regionId);

        $chartShellCore   = $this->landPriceRepo->getChartData($regionId, 'shell_core_price');
        $chartFullyFinished = $this->landPriceRepo->getChartData($regionId, 'fully_finished_price');

        $indicatorShellCore   = $this->landPriceRepo->getIndicatorData($regionId, 'shell_core_price');
        $indicatorFullyFinished = $this->landPriceRepo->getIndicatorData($regionId, 'fully_finished_price');

        $computeIndicator = function (array $results): array {
            $dir = 'neutral';
            $pct = null;

            if (count($results) >= 2) {
                $prev = floatval($results[0]->avg_price);
                $curr = floatval($results[1]->avg_price);

                if ($prev > 0) {
                    $pct = round((($curr - $prev) / $prev) * 100, 1);
                }

                if ($curr > $prev) {
                    $dir = 'up';
                } elseif ($curr < $prev) {
                    $dir = 'down';
                } else {
                    $dir = 'neutral';
                    $pct = 0.0;
                }
            }

            return [$dir, $pct];
        };

        [$scDir, $scPct] = $computeIndicator($indicatorShellCore);
        [$ffDir, $ffPct] = $computeIndicator($indicatorFullyFinished);

        $formatChartData = function (array $data): array {
            return array_map(function ($row) {
                return [
                    'month' => $row->month,
                    'avg_price' => floatval($row->avg_price),
                ];
            }, $data);
        };

        wp_send_json_success([
            'shell_core' => [
                'latest_price'  => $latest ? floatval($latest->shell_core_price) : null,
                'indicator_dir' => $scDir,
                'indicator_pct' => $scPct,
                'chart_data'    => $formatChartData($chartShellCore),
            ],
            'fully_finished' => [
                'latest_price'  => $latest ? floatval($latest->fully_finished_price) : null,
                'indicator_dir' => $ffDir,
                'indicator_pct' => $ffPct,
                'chart_data'    => $formatChartData($chartFullyFinished),
            ],
        ]);
    }
}
