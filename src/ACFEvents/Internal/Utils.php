<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents\Internal;

use Hirasso\ACFEvents\Internal\FieldGroups\EventFields;
use wpdb;

final class Utils
{
    /**
     * Access the global wpdb instance
     */
    public function wpdb(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Get all years for which events exist
     *
     * @param string|list<string> $postTypes
     * @param null|list<string> $postStati pass `null` explicitly to ignore the post status
     * @return list<int>
     */
    public function getYears(string|array $postTypes, null|array $postStati = ['publish']): array
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
}
