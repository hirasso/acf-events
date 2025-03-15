<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

use InvalidArgumentException;
use Hirasso\ACFEvents\FieldGroups\EventFields;
use Hirasso\ACFEvents\FieldGroups\LocationFields;
use Site\Base\PostHelper;
use WP_Post;

/**
 * Automatically attach locations to events
 */
final class Locations
{
    protected static bool $registered = false;

    public function __construct(private Core $core)
    {
    }

    /**
     * Add WordPress hooks
     */
    public function register()
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        add_action('init', [$this, 'add_post_type']);
        add_filter('update_post_meta', [$this, 'update_post_meta_hook'], 10, 4);
        add_action("wp_after_insert_post", [$this, 'wp_after_insert_post'], 20, 2);
        add_filter('map_meta_cap', [$this, 'prevent_location_deletion'], 10, 4);
        add_filter('acf/pre_update_value', [$this, 'acf_pre_update_value'], 10, 4);
    }


    public function add_post_type()
    {
        $this->core->addPostType(
            name: 'acfe-location',
            slug: 'location',
            args: [
                'menu_position' => 0,
                'menu_icon' => 'dashicons-admin-multisite',
                'public' => true,
                'show_ui' => true,
                'has_archive' => false,
                'labels' => [
                    'name' => 'Locations',
                    'singular_name' => 'Location',
                    'menu_name' => 'Locations',
                ],
                'supports' => ['title', 'revisions', 'author'],
            ],
        );
    }

    /**
     * Runs every time post meta is updated or added
     */
    public function update_post_meta_hook(mixed $x, int $id, string $key, mixed $value): mixed
    {
        if ($this->core->isEvent($id) && $key === EventFields::LOCATION_ID) {
            $this->updateEvent($id, (int) $value);
        }

        return $x;
    }

    /**
     * Update the location infos in an event
     */
    private function updateEvent(int $eventID, int $locationID): void
    {
        if (!$this->core->isEvent($eventID)) {
            throw new InvalidArgumentException("Not an event: $eventID");
        }

        $name = "";
        $sortName = "";

        if ($this->core->isLocation($locationID)) {
            $name = get_the_title($locationID);
            $sortName = get_post_meta($locationID, LocationFields::SORT_NAME, true) ?: $name;
        }

        update_post_meta($eventID, EventFields::LOCATION_NAME, $name);
        update_post_meta($eventID, EventFields::LOCATION_SORT_NAME, $sortName);
    }

    /**
     * Update attached events when saving a location
     */
    public function wp_after_insert_post(int $locationID, WP_Post $post): void
    {
        if (
            !$this->core->isLocation($post)
            || !$this->core->isVisiblePostStatus($locationID)
        ) {
            return;
        }

        remove_action("wp_after_insert_post", [$this, 'wp_after_insert_post'], 20);

        do_action('acfe/save_location', $locationID, $post);

        foreach ($this->core->getEventsAtLocation($locationID) as $eventID) {
            $this->updateEvent($eventID, $locationID);
        }

        add_action("wp_after_insert_post", [$this, 'wp_after_insert_post'], 20, 2);
    }

    /**
     * Do not allow locations with attached events to be deleted
     */
    public function prevent_location_deletion(array $caps, string $cap, int $user_id, array $args): array
    {
        if ($cap !== 'delete_post' || empty($args[0])) {
            return $caps;
        }

        $postID = (int) $args[0];

        return count($this->core->getEventsAtLocation($postID, 1))
            ? ['do_not_allow']
            : $caps;
    }

    /**
     * Prevents selected fields from being modified via ACF:
     *
     * - acfe_event_location_name
     * - acfe_event_location_sort_name
     */
    public function acf_pre_update_value(mixed $check, mixed $value, string|int $postID, array $field): mixed
    {
        if (collect([EventFields::LOCATION_NAME, EventFields::LOCATION_SORT_NAME])->contains($field['name'])) {
            return 'Blocked update via ACF update_value(), as this field is managed by ACFEvents';
        }
        return $check;
    }

}
