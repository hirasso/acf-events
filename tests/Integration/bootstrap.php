<?php

namespace RH\AdminUtils\Tests\Integration;

/** The wordpress plugins directory, deducted from this bootstrap file */
$pluginsDir = \dirname(\dirname(\dirname(__DIR__)));

/** Load wp-env's config file in the container, but still use our own wp-phpunit */
\putenv('WP_PHPUNIT__TESTS_CONFIG=/wordpress-phpunit/wp-tests-config.php');

/** Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available */
require_once "$pluginsDir/acf-events/vendor/autoload.php";

/** Provide access to the function `tests_add_filter()` */
require_once \getenv('WP_PHPUNIT__DIR') . '/includes/functions.php';

/** Manually load plugin files required for tests. */
\tests_add_filter('muplugins_loaded', function () use ($pluginsDir) {
    require_once("$pluginsDir/advanced-custom-fields/acf.php");
    require_once("$pluginsDir/polylang/polylang.php");
});

/** Start up the WP testing environment. */
require_once \getenv('WP_PHPUNIT__DIR') . '/includes/bootstrap.php';
