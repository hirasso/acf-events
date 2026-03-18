<?php

namespace Hirasso\ACFEvents\Tests\Pest;

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BasicTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_has_required_plugins(): void
    {
        $this->assertTrue(function_exists('acf_events'));
        $this->assertTrue(defined('ACF'));
        $this->assertTrue(defined('POLYLANG'));
    }
}
