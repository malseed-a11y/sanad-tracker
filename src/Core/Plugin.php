<?php

namespace SanadTracker\Core;

if (!defined('ABSPATH')) {
    exit;
}

use SanadTracker\Admin\AdminMenu;
use SanadTracker\Admin\Ajax\RegionAdminAjax;
use SanadTracker\Admin\Ajax\MaterialAdminAjax;
use SanadTracker\Admin\Ajax\MaterialPriceAdminAjax;
use SanadTracker\Admin\Ajax\LandPriceAdminAjax;
use SanadTracker\Frontend\Shortcodes\MaterialsShortcode;
use SanadTracker\Frontend\Shortcodes\LandShortcode;
use SanadTracker\Frontend\Ajax\PublicTrackerAjax;

class Plugin
{
    public function run(): void
    {
        add_action('init', [$this, 'init']);
    }

    public function init(): void
    {
        new AdminMenu();
        new RegionAdminAjax();
        new MaterialAdminAjax();
        new MaterialPriceAdminAjax();
        new LandPriceAdminAjax();
        new MaterialsShortcode();
        new LandShortcode();
        new PublicTrackerAjax();
    }
}
