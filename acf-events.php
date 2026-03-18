<?php

/**
 * Plugin Name: ACF Events
 * Plugin URI: https://github.com/hirasso/acf-events
 * Version: 0.0.0
 * Requires PHP: 8.2
 * Author: Rasso Hilber
 * Description: A Composer library to manage events, recurrences and locations using WordPress + Advanced Custom Fields 📆
 * Author URI: https://rassohilber.com
 * License: GPL-2.0-or-later
 **/

/**
 * Self-initialize in dev
 */
if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';

    add_action('plugins_loaded', 'acf_events');
};
