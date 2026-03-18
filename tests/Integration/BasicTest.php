<?php

namespace Hirasso\ACFEvents\Tests\Integration;

use Hirasso\ACFEvents\ACFEvents;
use Hirasso\ACFEvents\Internal\FieldGroups\EventFields;
use Hirasso\ACFEvents\Internal\PostTypes;
use Yoast\WPTestUtils\WPIntegration\TestCase;

class BasicTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        new ACFEvents();
    }

    public function test_has_required_plugins(): void
    {
        $this->assertTrue(function_exists('acf_events'));
        $this->assertTrue(defined('ACF'));
        $this->assertTrue(defined('POLYLANG'));
    }

    public function test_creates_recurrence(): void
    {
        $location = $this->factory()->post->create_and_get([
            'post_type' => PostTypes::LOCATION,
            'meta_input' => [
                'acfe_location_address' => "Test Street 1\n12345 Test City",
                'acfe_location_area' => 'Test Area',
            ],
        ]);

        $event = $this->factory()->post->create_and_get([
            'post_type' => PostTypes::EVENT,
            'meta_input' => [
                EventFields::DATE_AND_TIME => \date('Y-m-d H:i:s', \strtotime('next saturday 10:00')),
                EventFields::LOCATION_ID => $location->ID,
                EventFields::FURTHER_DATES => [
                    [EventFields::FURTHER_DATES_DATE_AND_TIME => \date('Y-m-d H:i:s', \strtotime('next saturday 12:00'))],
                    [EventFields::FURTHER_DATES_DATE_AND_TIME => \date('Y-m-d H:i:s', \strtotime('next saturday 14:00'))],
                ],
            ],
        ]);

        $recurrences = get_posts([
            'post_type' => 'any',
            'posts_per_page' => -1,
        ]);
        $this->assertTrue(true);
        dump($recurrences);
    }
}
