<?php

/**
 * Plugin Name: e2e test plugin
 * Description: Runs as a plugin to support e2e tests using playwright via wp-env
 */

use Exception;
use WP_Query;

add_action('plugins_loaded', fn () => new E2EHelperPlugin);

class E2EHelperPlugin
{
    public function __construct()
    {

    }
}
