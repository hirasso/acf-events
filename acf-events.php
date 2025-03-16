<?php

/**
 * ACF Events
 *
 * @author            Rasso Hilber
 * @copyright         2025 Rasso Hilber
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: ACF Events
 * Description: Mange events, recurrences and locations using Advanced Custom Fields 📆
 * Author: Rasso Hilber
 * Author URI: https://rassohilber.com/
 * Text Domain: acf-events
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Requires PHP: 8.2
 * Version: 0.0.0
 */

use Hirasso\ACFEvents\ACFEvents;

/** Exit if accessed directly */
if (!defined('ABSPATH')) {
    exit;
}

/** Load vendors */
if (is_readable(__DIR__ . '/vendor/scoper-autoload.php')) {
    /**
     * Load scoper-autoload if available
     *
     * @see https://github.com/humbug/php-scoper/discussions/1101
     */
    require_once __DIR__ . '/vendor/scoper-autoload.php';
} elseif (is_readable(__DIR__ . '/vendor/autoload.php')) {
    /**
     * Otherwise, load the normal autoloader if available
     */
    require_once __DIR__ . '/vendor/autoload.php';
}

/** @return \Hirasso\ACFEvents\Core */
function acfEvents()
{
    static $instance;
    if (isset($instance)) {
        return $instance->core();
    }
    $instance = new ACFEvents();
    return $instance->core();
}

add_action('plugins_loaded', function() {
    acfEvents();
});

