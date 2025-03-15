<?php

/**
 * PHPUnit WP integration test bootstrap file
 */

namespace Tests;

use Yoast\WPTestUtils\WPIntegration;

// Disable xdebug backtrace.
if (\function_exists('xdebug_disable')) {
    \xdebug_disable();
}

/*
 * Load the plugin(s).
 */
require_once \dirname(__DIR__, 2) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

/**
 * Get access (GLOBAL!!) to tests_add_filter() function.
 * wp-phpunit is located in `/wordpress-phpunit/` in the wp-env container
 */
require_once WPIntegration\get_path_to_wp_test_dir() . 'includes/functions.php';

\tests_add_filter(
    'muplugins_loaded',
    static function () {
        require_once \dirname(__DIR__, 2) . '/acf-events.php';
    }
);

/*
 * Load WordPress, which will load the Composer autoload file,
 * and load the MockObject autoloader after that.
 */
WPIntegration\bootstrap_it();
