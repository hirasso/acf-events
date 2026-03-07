<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

use Hirasso\ACFEvents\Internal\Core;
use Hirasso\ACFEvents\Internal\FieldGroups\EventFields;
use Hirasso\ACFEvents\Internal\FieldGroups\LocationFields;
use Hirasso\ACFEvents\Internal\Locations;
use Hirasso\ACFEvents\Internal\PolylangIntegration;
use Hirasso\ACFEvents\Internal\Recurrences;
use WP_Post;

/**
 * Manage events, recurrences and locations using Advanced Custom Fields
 */
final readonly class ACFEvents
{
    public Core $core;

    public function __construct()
    {
        $core = new Core();
        $core->register();
        (new EventFields($core))->register();
        (new LocationFields($core))->register();
        (new Locations($core))->register();
        (new Recurrences($core))->register();
        (new PolylangIntegration())->register();

        $this->core = $core;
    }

    /**
     * Get an event's date and time, separated by $separator
     */
    public function getEventDateAndTime(int|WP_Post $post, string $separator = ', '): ?string
    {
        if (!$this->core->isEvent($post)) {
            return null;
        }

        $rawDate = \get_field(EventFields::DATE_AND_TIME, $post, false);

        $date = \date_i18n(\get_option('date_format'), \strtotime($rawDate));
        $time = \date_i18n(\get_option('time_format'), \strtotime($rawDate));

        return \collect([$date, $time])->filter()->join($separator);
    }

    /**
     * Return "Location Name, Location Area"
     */
    public function getLocationNameAndArea(int $eventID): string
    {
        $locationID = \get_field(EventFields::LOCATION_ID, $eventID);

        return \collect([
            \get_the_title($locationID),
            \get_field(LocationFields::AREA, $locationID) ?: '',
        ])
        ->filter($this->core->isFilledString(...))
        ->join(', ');
    }
}
