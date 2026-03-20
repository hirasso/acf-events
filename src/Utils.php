<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\WP\FPEvents;

use Hirasso\WP\FPEvents\FieldGroups\EventFields;
use WP_Query;
use wpdb;

final class Utils
{
    private static ?self $instance = null;

    private function __construct() {}

    public static function init()
    {
        self::$instance ??= new self();
        return self::$instance;
    }

    /**
     * Access the global wpdb instance
     */
    public function wpdb(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Access the main WP_Query instance
     */
    public function mainQuery(): WP_Query
    {
        global $wp_query;
        return $wp_query;
    }

    /**
     * Get all years for which events exist
     *
     * @param string|list<string> $postTypes
     * @param null|list<string> $postStati pass `null` explicitly to ignore the post status
     * @return list<int>
     */
    public function getYears(string|array $postTypes, ?array $postStati = ['publish']): array
    {
        $wpdb = $this->wpdb();

        $postTypes = (array) $postTypes;
        $postStati ??= [];

        $metaKey = EventFields::DATE_AND_TIME;

        $placeholders = fn(array $values) => collect($values)
            ->map(fn() => '%s')
            ->implode(', ');

        $statusClause = !empty($postStati)
            ? "AND p.post_status IN ({$placeholders($postStati)})"
            : '';

        $query = $wpdb->prepare(
            <<<SQL
            SELECT DISTINCT YEAR(pm.meta_value)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
                ON pm.post_id = p.ID
                AND pm.meta_key = '%s'
            WHERE p.post_type IN ({$placeholders($postTypes)})
            {$statusClause}
            ORDER BY pm.meta_value DESC
            SQL,
            $metaKey,
            ...[
                ...$postTypes,
                ...$postStati,
            ],
        );

        return collect($wpdb->get_col($query))
            ->map(absint(...))
            ->all();
    }

    /**
     * Does an unknown variable look like a year?
     */
    public function isYear(mixed $var): bool
    {
        return
            is_numeric($var)
            && preg_match('/^\d{4}$/', trim((string) $var)) === 1
            && (int) $var >= 1000
            && (int) $var <= 9999;
    }

    /**
     * Check if a value is the current year
     */
    public function isCurrentYear(int $value): bool
    {
        return $value === (int) current_time('Y');
    }
}
