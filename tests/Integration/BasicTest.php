<?php

namespace Hirasso\ACFEvents\Tests\Integration;

use Hirasso\ACFEvents\Internal\FieldGroups\EventFields;
use Hirasso\ACFEvents\Internal\FieldGroups\Fields;
use Hirasso\ACFEvents\Internal\PostTypes;
use Yoast\WPTestUtils\WPIntegration\TestCase;

class BasicTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // $this->setupPolylangLanguages();
        acf_events();
    }

    private function setupPolylangLanguages(): void
    {
        require_once WP_PLUGIN_DIR . '/polylang/src/api.php';

        if (!empty(pll_languages_list())) {
            return;
        }

        PLL()->model->add_language([
            'name'       => 'English',
            'slug'       => 'en',
            'locale'     => 'en_US',
            'rtl'        => false,
            'term_group' => 0,
        ]);

        PLL()->model->add_language([
            'name'       => 'Deutsch',
            'slug'       => 'de',
            'locale'     => 'de_DE',
            'rtl'        => false,
            'term_group' => 1,
        ]);
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

        /**
         * Augment $_POST to test recurrency creation
         * The fact this needs to be hacked here points to a weakness in the
         * implementation. We should maybe look into update_post_meta or so.
         * OR we could add a new API function acf_events()->addRecurrence($eventId, $dateAndTime)...
         * not sure about this.
         */
        $_POST = [
            'acf' => [
                Fields::key(EventFields::FURTHER_DATES) => [
                    "row-0" => [Fields::key(EventFields::FURTHER_DATES_DATE_AND_TIME) => "2025-06-09 19:00:00"],
                    "row-1" => [Fields::key(EventFields::FURTHER_DATES_DATE_AND_TIME) => "2025-06-12 18:00:00"],
                    "row-2" => [Fields::key(EventFields::FURTHER_DATES_DATE_AND_TIME) => "2025-06-12 19:00:00"],
                ],
            ],
        ];

        $event = $this->factory()->post->create_and_get([
            'post_type' => PostTypes::EVENT,
            'tax_input' => ['language' => 'en'], // no effect currently
            'meta_input' => [
                EventFields::DATE_AND_TIME => \date('Y-m-d H:i:s', \strtotime('next saturday 10:00')),
                EventFields::LOCATION_ID => $location->ID,
            ],
        ]);

        $recurrences = get_posts([
            'post_type' => PostTypes::RECURRENCE,
            'post_status' => 'any',
            'posts_per_page' => -1,
        ]);

        $this->assertSame(count($recurrences), 3);

        $furtherDates = collect($_POST['acf'][Fields::key(EventFields::FURTHER_DATES)])
            ->map(fn($row) => $row[Fields::key(EventFields::FURTHER_DATES_DATE_AND_TIME)])
            ->values()
            ->all();

        foreach ($recurrences as $index => $p) {
            $this->assertSame($p->post_parent, $event->ID);
            $dateAndTime = get_post_meta($p->ID, EventFields::DATE_AND_TIME, true);
            $this->assertSame($dateAndTime, $furtherDates[$index]);
        }
    }
}
