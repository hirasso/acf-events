<?php

/**
 * Plugin Name: e2e tests bootstrap plugin
 * Description: Prepares the @wordpress/env environment for e2e tests
 */

namespace Hirasso\ACFEvents\Tests\E2E;

/** Exit if accessed directly */
if (!\defined('ABSPATH')) {
    exit;
}

/** plugins are not immediately installed. We need to wait for them */
require_once dirname(__DIR__) . '/acf-events/vendor/autoload.php';

/**
 * Check what env we are currently in
 * @return null|"development"|"tests"
 */
function getCurrentEnv(): ?string
{
    $env = (\defined('ACFE_WP_ENV'))
        ? ACFE_WP_ENV
        : null;

    return \in_array($env, ['development', 'test'], true)
        ? $env
        : null;
}

add_action('after_setup_theme', function () {
    new Setup();
});
