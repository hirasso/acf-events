<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

use DateTime;
use DateTimeImmutable;
use Hirasso\ACFEvents\FieldGroups\EventFields;
use WP_Query;
use WP_Post;
use WP_Term;

/**
 * Manage events, recurrences and locations using Advanced Custom Fields
 */
final class Core
{
    protected static bool $registered = false;

    public function register()
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        add_action('init', [$this, 'init_hook']);
        add_filter('relevanssi_post_title_before_tokenize', [$this, 'relevanssi_post_title_before_tokenize'], 10, 2);
        add_filter('pll_get_post_types', [$this, 'pll_get_post_types'], 10, 2);
        add_filter('query_vars', [$this, 'query_vars']);
        add_filter('posts_clauses', [$this, 'posts_clauses'], 10, 2);
        add_action('pre_get_posts', [$this, 'prepare_archive_main_query']);
        add_filter('term_link', [$this, 'term_link'], 10, 2);
    }

    public function init_hook()
    {
        $this->addPostType(
            name: PostTypes::EVENT,
            slug: 'event',
            filter: true,
            args: [
                'menu_position' => 0,
                'menu_icon' => 'dashicons-calendar',
                'has_archive' => 'programm',
                'labels' => [
                    'name' => 'Events',
                    'singular_name' => 'Event',
                    'menu_name' => 'Events',
                ],
                'supports' => [
                    'title',
                    'revisions',
                    'author',
                ],
            ],
        );
    }

    /**
     * Add query vars
     */
    public function query_vars(array $vars): array
    {
        return collect($vars)->merge([
            'view',
            'search'
        ])->all();
    }

    /**
     * Get the current date time in the required format and time zone
     */
    public function getIsoDateTime(string $dateString)
    {
        return $this->getDateTime($dateString)->format(ACFEvents::ISO_DATE_FORMAT);
    }

    /**
     * Get a DateTime in the local timezone
     */
    public function getDateTime(string $dateString = 'now'): DateTimeImmutable
    {
        return new DateTimeImmutable(
            datetime: $dateString,
            timezone: wp_timezone(),
        );
    }

    /**
     * Validate that a provided date string conforms to an expected format
     */
    public function isValidDateFormat(
        string $dateString,
        string $expectedFormat = ACFEvents::ISO_DATE_FORMAT,
    ): bool {
        $datetime = \DateTime::createFromFormat($expectedFormat, $dateString);
        return $datetime && $datetime->format($expectedFormat) === $dateString;
    }

    /**
     * Check if a post is an event
     */
    public function isEvent(string|int|WP_Post $post)
    {
        if (!$postID = $this->getPostID($post)) {
            return false;
        }
        return in_array(get_post_type($postID), [PostTypes::EVENT, PostTypes::RECURRENCE]);
    }

    /**
     * Check if a post is an original event
     */
    public function isOriginalEvent(string|int|WP_Post $post)
    {
        if (!$postID = $this->getPostID($post)) {
            return false;
        }
        return get_post_type($postID) === PostTypes::EVENT;
    }

    /**
     * Check if a post is a location
     */
    public function isLocation(int|WP_Post $post)
    {
        if (!$postID = $this->getPostID($post)) {
            return false;
        }

        return get_post_type($postID) === PostTypes::LOCATION;
    }

    /**
     * Get the post ID from an unknown $post argument
     */
    private function getPostID(string|int|null|WP_Post $post): ?int
    {
        if ($post instanceof WP_Post) {
            return $post->ID;
        }

        if (blank($post) || !is_numeric($post)) {
            return null;
        }

        return (int) $post;
    }

    /**
     * Get all dates from an Event
     * @return EventDate[]
     */
    public function getEventDates(int|WP_Post $post): array
    {
        if (!$this->isEvent($post)) {
            return [];
        }

        $post = get_post($post);

        return collect([$post->ID])
            ->merge(get_posts([
                'post_type' => [PostTypes::RECURRENCE],
                'post_parent' => $post->ID,
                'posts_per_page' => -1,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
                'fields' => 'ids'
            ]))
            ->flip()
            ->map(fn ($_, $postID) => get_field(EventFields::DATE_AND_TIME, $postID, false))
            ->reject(fn ($date) => blank($date))
            ->sort()
            ->map(fn ($date, $postID) => new EventDate($date, $postID))
            ->all();
    }

    /**
     * Get all Filters of an event
     * @return WP_Term[]
     */
    public function getEventFilters(int|WP_Post $post): array
    {
        if (!$this->isEvent($post)) {
            return [];
        }

        $post = get_post($post);

        return wp_get_object_terms($post->ID, ACFEvents::FILTER_TAXONOMY);
    }

    /**
     * Get all events attached to a location
     * @return int[]|WP_Post[]
     */
    public function getEventsAtLocation(int|WP_Post $post, int $amount = -1, bool $ids = true): array
    {
        $postID = $post->ID ?? $post;

        if (!$this->isLocation($postID)) {
            return [];
        }

        $args = [
            // 'lang' => pll_get_post_language($postID),
            'suppress_filters' => true,
            'post_type' => [PostTypes::EVENT, PostTypes::RECURRENCE],
            'posts_per_page' => $amount,
            'meta_query' => [
                EventFields::LOCATION_ID => [
                    'key' => EventFields::LOCATION_ID,
                    'value' => $postID,
                    'type' => 'NUMERIC'
                ]
            ],
        ];

        if ($ids) {
            $args['fields'] = 'ids';
        }

        $attachedEvents = new WP_Query($args);

        return $attachedEvents->posts;
    }

    /**
     * Filter the post title before indexing by relevanssi
     */
    public function relevanssi_post_title_before_tokenize(
        string $title,
        WP_Post $post
    ): string {

        if (!$this->isEvent($post)) {
            return $title;
        }

        $locationTokens = collect([
            get_post_meta($post->ID, EventFields::LOCATION_NAME, true),
            get_post_meta($post->ID, EventFields::LOCATION_SORT_NAME, true)
        ])
        ->filter()
        ->join(" ");

        return $this->addWords($title, $locationTokens);
    }

    /**
     * Add words to a string if that string doesn't already contain them
     */
    private function addWords(
        string $str,
        ?string $words = ''
    ): string {
        $words = trim($words);

        if (empty($words)) {
            return $str;
        }

        foreach (explode(' ', $words) as $word) {
            if (!str_contains(" $str ", " $word ")) {
                $str = "$str $word";
            }
        }

        return $str;
    }

    /**
     * Get a date for display purposes
     */
    public function getDateForDisplay(string $dateString, bool $includeTime = false): string
    {
        $dateFormat = get_option('date_format');
        $timeFormat = get_option('time_format');
        $format = $includeTime ? "$dateFormat $timeFormat" : $dateFormat;
        return date_i18n($format, strtotime($dateString));
    }

    /**
     * Forcibly enable translations for all our post types
     */
    public function pll_get_post_types(array $postTypes, bool $is_settings)
    {
        $merge = [
            PostTypes::EVENT,
            PostTypes::RECURRENCE,
            PostTypes::LOCATION
        ];

        return collect($postTypes)
            ->merge(collect($merge)->combine($merge))
            ->all();
    }

    /**
     * Prepare the main archive query for events
     */
    public function prepare_archive_main_query(WP_Query $query): void
    {
        if (
            is_admin()
            || !$query->is_main_query()
            || !$query->is_archive()
        ) {
            return;
        }

        $postType = $this->guessPostType($query);

        if ($postType !== PostTypes::EVENT) {
            return;
        }

        $query->query_vars = collect($query->query_vars)
            ->replaceRecursive($this->getArchiveArgs())
            ->all();
    }

    /**
     * Get the archive args for events
     */
    private function getArchiveArgs()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $view = get_query_var('view', null);

        $args = collect([
            'post_type' => PostTypes::EVENT,
            'posts_per_page' => 6,
            'ignore_sticky_posts' => true,
        ])->replaceRecursive(match($view) { // @phpstan-ignore argument.type
            default => collect([
                'orderby' => [EventFields::DATE_AND_TIME => 'asc'],
            ]),
            'calendar' => collect([
                'post_type' => [PostTypes::EVENT, PostTypes::RECURRENCE],
                'orderby' => [EventFields::DATE_AND_TIME => 'asc'],
                'meta_query' => [
                    EventFields::DATE_AND_TIME => [
                        'key' => EventFields::DATE_AND_TIME,
                        'type'    => 'DATETIME',
                        'compare' => '>=',
                        'value' => $this->getIsoDateTime('now')
                    ],
                ],
                ACFEvents::CLAUSES_KEY => [
                    'fields' => collect([
                        "DATE($wpdb->postmeta.meta_value) as day",
                    ])->join(', '),
                    'groupby' => 'day',
                ],
            ]),
            'locations' => collect([
                'orderby' => [EventFields::LOCATION_SORT_NAME => 'asc'],
                'meta_query' => [
                    EventFields::LOCATION_SORT_NAME => [
                        'key' => EventFields::LOCATION_SORT_NAME,
                        'compare' => 'EXISTS'
                    ],
                    EventFields::LOCATION_NAME => [
                        'key' => EventFields::LOCATION_NAME,
                        'compare' => 'EXISTS'
                    ],
                ],
                ACFEvents::CLAUSES_KEY => [
                    'fields' => collect([
                        "mt1.meta_value as " . EventFields::LOCATION_NAME,
                        "$wpdb->postmeta.meta_value as " . EventFields::LOCATION_SORT_NAME,
                    ])->join(", "),
                    'groupby' => EventFields::LOCATION_SORT_NAME,
                ],
            ]),
        });

        return $args->all();
    }

    /**
     * Get the current batch from a query
     */
    public function getCurrentBatch(WP_Query $query, ?string $view = null)
    {
        if (empty($query->posts)) {
            return [];
        }

        return match($view) {
            'calendar' => $this->groupByDay($query),
            'locations' => $this->groupByLocation($query),
            default => $query->posts
            // default => collect($query->posts)
            //     ->map(fn ($p) => new EventModel($p))
            //     ->all(),
        };

    }

    /**
     * Get the default args for grouped events
     */
    protected function getGroupDefaultArgs(WP_Query $query)
    {
        return collect($query->query_vars)
            ->except(['nopaging'])
            ->replaceRecursive([
                'ignore_sticky_posts' => true,
                'no_found_rows' => false,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'posts_per_page' => -1
            ]);
    }

    /**
     * Group posts by day
     * @return array<int, GroupedEvents>
     */
    protected function groupByDay(WP_Query $query): array
    {
        if (!count($query->posts)) {
            return [];
        }

        $days = collect($query->posts)
            ->map(fn ($p) => $p->day ?? null)
            ->all();

        [$first, $last] = [
            collect($days)->first(),
            collect($days)->last()
        ];

        $args = $this->getGroupDefaultArgs($query)->replaceRecursive([
            'meta_query' => [
                EventFields::DATE_AND_TIME => [
                    'key' => EventFields::DATE_AND_TIME,
                    'compare' => 'BETWEEN',
                    'value' => [$first, $last],
                    'type' => 'DATE',
                ]
            ]
        ])->all();

        return collect(get_posts($args))
            // ->map(fn ($p) => new EventModel($p))
            ->groupBy(fn ($post) => $this->formatDayRelativeToToday(get_field(EventFields::DATE_AND_TIME, $post, false)))
            ->map(fn ($group, $title) => new GroupedEvents(title: $title, posts: $group->all()))
            ->values()
            ->all();
    }

    /**
     * Group posts by location
     * @return array<int, GroupedEvents>
     */
    protected function groupByLocation(WP_Query $query): array
    {
        if (!count($query->posts)) {
            return [];
        }

        $fieldKey = EventFields::LOCATION_SORT_NAME;

        $locationNames = collect($query->posts)
            ->map(fn ($p) => $p->$fieldKey ?? null)
            ->all();

        $args = $this->getGroupDefaultArgs($query)->replaceRecursive([
            'meta_query' => [
                'acfe_min_location_sort_name' => [
                    'key' => $fieldKey,
                    'compare' => '>=',
                    'value' => collect($locationNames)->first(),
                ],
                'acfe_max_location_sort_name' => [
                    'key' => $fieldKey,
                    'compare' => '<=',
                    'value' => collect($locationNames)->last(),
                ]
            ]
        ])->all();

        return collect(get_posts($args))
            // ->map(fn ($p) => new EventModel($p))
            ->groupBy(fn ($post) => get_field(EventFields::LOCATION_NAME, $post))
            ->map(fn ($group, $title) => new GroupedEvents(title: $title, posts: $group->all()))
            ->values()
            ->all();
    }

    /**
     * Inject custom clauses
     */
    public function posts_clauses(
        array $clauses,
        WP_Query $query,
    ): array {
        $customClauses = $query->query_vars[ACFEvents::CLAUSES_KEY] ?? null;
        unset($query->query_vars[ACFEvents::CLAUSES_KEY]);

        if (!is_array($customClauses)) {
            return $clauses;
        }

        return collect($clauses)
            ->replaceRecursive($customClauses)
            ->all();
    }

    /**
     * Filter get_term_link to return the post type url appended with ?filter=term
     */
    public function term_link(string $link, WP_Term $term)
    {
        $postType = get_taxonomy($term->taxonomy)->object_type[0] ?? null;

        if ($postType !== PostTypes::EVENT) {
            return $link;
        }

        $archiveURL = get_post_type_archive_link(PostTypes::EVENT);
        $currentURL = $this->getCurrentURL(true);

        $url = str_starts_with($currentURL, $archiveURL)
            ? $currentURL
            : $archiveURL;

        return add_query_arg(['filter' => $term->slug], $url);
    }

    /**
     * Flatten post meta (making sure single keys are not returned as an array)
     */
    public function getFlatPostMeta(int $postID): array
    {
        return collect(get_post_meta($postID))
            ->map(fn (mixed $_, string $key) => get_post_meta($postID, $key, true))
            ->all();
    }

    /**
     * @TODO extract the date and time function from here
     */
    public function getEventDateAndDuration(int|WP_Post $post): ?string
    {
        if (!$this->isEvent($post)) {
            return null;
        }

        $rawDate = get_field(EventFields::DATE_AND_TIME, $post, false);
        $date = date_i18n(get_option('date_format'), strtotime($rawDate));
        $time = date_i18n(get_option('time_format'), strtotime($rawDate));
        $duration = $this->getEventDuration($post);

        return collect([
            $date,
            $time,
            $duration
        ])
            ->filter()
            ->join(', ');
    }

    /**
     * Get an event's duration in minutes
     */
    public function getEventDuration(int|WP_Post $post): ?string
    {
        if (!$this->isEvent($post)) {
            return null;
        }
        $duration = get_field(EventFields::DURATION, $post);
        $minutes = $this->durationToMinutes($duration);

        $label = __('Minutes', 'acf-events');

        return $minutes
            ? "$minutes $label"
            : null;
    }

    /**
     * Convert a duration in the shape of H:i to minutes
     */
    private function durationToMinutes(?string $duration): int
    {
        if (blank($duration) || !str_contains($duration, ':')) {
            return 0;
        }

        [$hours, $minutes] = collect(explode(':', $duration))
            ->map(fn ($value) => absint($value))
            ->all();

        return ($hours * 60) + $minutes;
    }

    /**
     * Check if the post status of a post can be considered "visible"
     */
    public function isVisiblePostStatus(int $postID)
    {
        return collect(['publish', 'future', 'private'])->contains(get_post_status($postID));
    }

    /**
     * Format a day relative to today
     * Special cases: 'Yesterday', 'Today' and 'Tomorrow'
     */
    public function formatDayRelativeToToday(
        DateTimeImmutable|string $date,
        ?string $relativeDateFormat = ", d. F Y",
        ?string $absoluteDateFormat = null,
    ): string {
        $absoluteDateFormat ??= get_option('date_format');

        if (is_string($date)) {
            $date = new DateTimeImmutable($date);
        }

        $today = new DateTimeImmutable(current_time('mysql'));

        $relativeDay = match (true) {
            $this->isSameDay($date, $today) => __('Today', 'acf-events'),
            $this->isSameDay($date, $today->modify('- 1 day')) => __('Yesterday', 'acf-events'),
            $this->isSameDay($date, $today->modify('+ 1 day')) => __('Tomorrow', 'acf-events'),
            default => null,
        };

        return match (!!$relativeDay) {
            true => $relativeDay . date_i18n($relativeDateFormat, $date->getTimestamp()),
            default => date_i18n($absoluteDateFormat, $date->getTimestamp()),
        };
    }

    /**
     * Check if two dates represent the same day
     */
    public function isSameDay(
        DateTimeImmutable $date1,
        ?DateTimeImmutable $date2 = null,
    ): bool {
        if (!$date2) {
            return false;
        }
        return $date1->format('Y-m-d') === $date2->format('Y-m-d');
    }

    /**
     * Guess the post type based on a WP_Query
     */
    public function guessPostType(WP_Query $query): ?string
    {
        if (!empty($query->query_vars['post_type'])) {
            return collect($query->query_vars['post_type'])->first();
        }

        $queriedObject = $query->get_queried_object();

        if ($queriedObject instanceof \WP_Post) {
            return $queriedObject->post_type;
        }

        if ($queriedObject instanceof \WP_Post_Type) {
            return $queriedObject->name;
        }

        if ($queriedObject instanceof \WP_Term) {
            $tax = get_taxonomy($queriedObject->taxonomy);
            if ($tax->public) {
                return collect($tax->object_type)->first();
            }
        }

        return null;
    }

    /**
     * Get current URL
     */
    public function getCurrentURL(bool $withQuery = false): string
    {
        $url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        if (!$withQuery) {
            $url = explode('?', $url)[0];
        }
        return $url;
    }

    /**
     * Helper function to add a custom post type
     */
    public function addPostType(
        string $name,
        string $slug,
        array $args,
        ?bool $filter = false,
    ): void {
        $args = array_merge([
            'menu_icon' => 'dashicons-star-filled',
            'with_filter' => false,
            'exclude_from_search' => false,
            'has_archive' => false,
        ], $args);

        /** Assume the menu icon is a local SVG file if it doesn't start with dashicons- */
        if (!str_starts_with($args['menu_icon'], 'dashicons-')) {
            $localSvgFile = file_get_contents(get_theme_file_path($args['menu_icon']));
            $args['menu_icon'] = 'data:image/svg+xml;base64,' . base64_encode($localSvgFile);
        }

        $archive_slug = $args['has_archive'];
        if ($archive_slug === true) {
            $archive_slug = $slug;
        }

        $singular_name = $args['labels']['singular_name'];

        if ($archive_slug && $filter) {
            $taxonomy = "{$name}_filter";
            register_taxonomy($taxonomy, $name, [
                'labels' => [
                    'name' => "$singular_name Filters",
                    'singular_name' => "$singular_name Filter",
                    'menu_name' => "Filters",
                ],
                'public' => true,
                'rewrite' => false,
                'query_var' => "filter",
                'show_ui' => true,
                'hierarchical' => true,
                'show_admin_column' => true,
            ]);
        }

        $post_type_args = wp_parse_args($args, [
            'public' => true,
            'rewrite' => [
                'with_front' => false,
                'slug' => $slug,
                'ep_mask' => EP_PAGES, // assign EP_PAGES to the CPT
            ],
            'has_archive' => $archive_slug,
            'menu_position' => 0,
            'hierarchical' => false,
            'taxonomies' => $filter ? ["{$name}_filter"] : [],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);

        register_post_type($name, $post_type_args);
    }
}
