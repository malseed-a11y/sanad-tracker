<?php

namespace SanadTracker\Core;

if (!defined('ABSPATH')) {
    exit;
}

use SanadTracker\Database\Migration;

class Activator
{
    public static function activate(): void
    {
        Migration::run();

        foreach (['administrator', 'editor'] as $roleName) {
            $role = get_role($roleName);
            if ($role && !$role->has_cap('sanad_tracker_access')) {
                $role->add_cap('sanad_tracker_access');
            }
        }
    }
}
