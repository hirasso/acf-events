<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

use Exception;
use WP_Post;
use InvalidArgumentException;
use RuntimeException;
use Hirasso\ACFEvents\FieldGroups\EventFields;
use Hirasso\ACFEvents\FieldGroups\Fields;

/**
 * Automatically create event recurrences, based on an ACF repeater field containing dates
 */
final class Recurrences
{
    protected static bool $registered = false;

    protected string $fieldKey;
    protected string $subFieldKey;
    private string $postType;
    protected string $recurrencePostType;

    public function __construct(private Core $core)
    {
        $this->fieldKey = Fields::key(EventFields::FURTHER_DATES);
        $this->subFieldKey = Fields::key(EventFields::FURTHER_DATES_DATE_AND_TIME);
        $this->postType = PostTypes::EVENT;
        $this->recurrencePostType = PostTypes::RECURRENCE;
    }

    public function register()
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        add_action('init', [$this, 'init_hook']);
        add_action('save_post', [$this, 'save_post'], 20);
        add_action('trashed_post', [$this, 'deleteRecurrences']);
        add_action('before_delete_post', [$this, 'deleteRecurrences']);
        add_filter('display_post_states', [$this, 'display_post_states'], 10, 2);
        add_filter('post_type_link', [$this, 'post_type_link'], 10, 2);
        add_filter("acf/validate_value/key=$this->subFieldKey", [$this, 'acf_validate_value_further_date'], 10, 2);
    }

    public function init_hook()
    {
        if (!post_type_exists($this->postType)) {
            throw new InvalidArgumentException("Post type doesn't exist: $this->postType");
        }

        register_post_type($this->recurrencePostType, [
            'public' => true,
            'show_ui' => true,
            'publicly_queryable' => false,
            'has_archive' => false,
            'show_in_menu' => "edit.php?post_type=$this->postType",
            'hierarchical' => false,
            'labels' => [
                'menu_name' => 'Recurrences',
                'name' => 'Event Recurrences',
                'singular_name' => 'Event Recurrence',
            ],
            'supports' => ['title', 'author']
        ]);
    }

    /**
     * Hook into save_post
     */
    public function save_post(int $postID): void
    {
        if (!$this->core->isOriginalEvent($postID)) {
            return;
        }

        $this->createRecurrences($postID);

        if (function_exists('pll_get_post_translations')) {

            collect(pll_get_post_translations($postID))
                ->reject($postID)
                ->each(fn ($translationID) => $this->createRecurrences($translationID));

        }
    }

    /**
     * Delete all clones from a post
     */
    public function deleteRecurrences(int $postID): void
    {
        if (!$this->core->isOriginalEvent($postID)) {
            return;
        }

        $recurrences = $this->getRecurrences($postID);

        foreach ($recurrences as $recurrenceID) {
            wp_delete_post($recurrenceID, true);
        }
    }

    /**
     * Get all recurrences of an event
     */
    protected function getRecurrences(int $postID)
    {
        if (!$this->core->isOriginalEvent($postID)) {
            return [];
        }

        return get_posts([
            // Fetch from any language
            'lang' => '',
            'post_type' => $this->recurrencePostType,
            'post_parent' => $postID,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
            'suppress_filters' => true,
        ]);
    }

    /**
     * Create clones from an original event
     */
    protected function createRecurrences($postID): void
    {
        /** Double-check if this is an original event */
        if (!$this->core->isOriginalEvent($postID)) {
            return;
        }

        $this->deleteRecurrences($postID);

        /** Only create clones for published events */
        if (!$this->core->isVisiblePostStatus($postID)) {
            return;
        }

        /**
         * FIRST check for an existing acf $_POST data, to make sure
         * the right most recent data lands in the clones.
         *
         * Falls back to the raw field value of the post, if $_POST data
         * is not available (e.g. during any edit actions from the edit.php screen)
         */
        $rawFurtherDates = $_POST['acf'][$this->fieldKey]
            ?? get_field($this->fieldKey, $postID, false);

        /**
         * Create a recurrence for each furtherDates entry
         */
        collect($rawFurtherDates)
            ->pluck($this->subFieldKey)
            ->filter()
            ->map(fn ($date) => $this->core->getIsoDateTime($date))
            ->each(fn (string $dateTime) => $this->createRecurrence($postID, $dateTime));
    }

    /**
     * Create an event recurrence entry
     */
    protected function createRecurrence(int $postID, string $dateTime): void
    {
        if (!$this->core->isOriginalEvent($postID)) {
            return;
        }

        if (!$this->core->isValidDateFormat($dateTime)) {
            throw new Exception("Invalid date format: $dateTime");
        }

        $originalMeta = $this->core->getFlatPostMeta($postID);
        $originalPostArray = get_post($postID, ARRAY_A);

        $taxInput = collect(get_post_taxonomies($postID))
            ->reject('post_translations')
            ->mapWithKeys(fn ($tax) => [
                $tax => collect(wp_get_object_terms($postID, $tax))
                    ->map(fn ($term) => $term->term_id)
                    ->all(),
            ])
            ->all();

        $postName = $originalPostArray['post_name'] . '-' . md5($dateTime);

        $postarr = collect($originalPostArray)
            ->only([
                'post_title',
                'post_name',
                'post_status',
                'post_date',
            ])
            ->merge([
                'post_type' => $this->recurrencePostType,
                'post_name' => $postName,
                'post_parent' => $postID,
                'meta_input' => [
                    ...$originalMeta, // needed for searching
                    EventFields::DATE_AND_TIME => $dateTime,
                ],
                'tax_input' => $taxInput,
            ])
            ->all();

        $result = wp_insert_post($postarr, true);

        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }
    }

    /**
     * Check if a post is an event recurrence
     */
    public function isRecurrence(int $postID): bool
    {
        return !!$postID && get_post_type($postID) === $this->recurrencePostType;
    }

    /**
     * Add custom Post states, to help with understanding
     */
    public function display_post_states(array $states, WP_Post $post): array
    {
        if (get_post_type($post->ID) !== $this->recurrencePostType) {
            return $states;
        }

        $editLink = get_edit_post_link($post->post_parent);
        $link = "<a href='$editLink'>#$post->post_parent</a>";
        $states[] = "Parent: $link";

        return $states;
    }

    /**
     * Redirects recurring events to their parent event
     */
    public function post_type_link(string $link, WP_Post $post): string
    {
        if (!$this->isRecurrence($post->ID)) {
            return $link;
        }

        return add_query_arg(
            'recurrence',
            $post->ID,
            get_permalink($post->post_parent)
        );
    }

    /**
     * Validate further dates
     */
    public function acf_validate_value_further_date(
        string|bool $valid,
        mixed $value
    ): string|bool {
        if (is_string($valid)) {
            return $valid;
        }

        $originalDate = $_POST['acf'][EventFields::key(EventFields::DATE_AND_TIME)] ?? null;

        if ($value === $originalDate) {
            return "Each date must be different from the original event's date and time.";
        }

        $isDuplicate = collect($_POST['acf'][$this->fieldKey] ?? [])
            ->pluck($this->subFieldKey)
            ->duplicates()
            ->contains($value);

        if ($isDuplicate) {
            return "Each date must be unique";
        }

        return $valid;
    }
}
