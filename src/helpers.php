<?php

use Hirasso\ACFEvents\ACFEvents;

/** @return ACFEvents */
function acf_events()
{
    static $instance = null;

    $instance ??= new ACFEvents();

    return $instance;
}

/**
 * This file is loaded too early in bedrock (before WP),
 * so acf_events needs to be initialized from functions.php
 */
// add_action('plugins_loaded', function() {
//     acf_events();
// });
