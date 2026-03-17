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
     * @param list<string> $postStati
     * @return list<int>
     */
    public function getYears(string|array $postTypes, array $postStati = ['publish']): array
    {
        $wpdb = $this->wpdb();

        $postTypes = (array) $postTypes;

        $metaKey = EventFields::DATE_AND_TIME;

        $placeholders = fn(array $values) => collect($values)
            ->map(fn() => '%s')
            ->implode(', ');

        $query = $wpdb->prepare(
            <<<SQL
            SELECT DISTINCT YEAR(pm.meta_value)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
                ON pm.post_id = p.ID
                AND pm.meta_key = '%s'
            WHERE p.post_type IN ({$placeholders($postTypes)})
            AND p.post_status IN ({$placeholders($postStati)})
            ORDER BY pm.meta_value DESC
            SQL,
            $metaKey,
            ...[
                ...$postTypes,
                ...$postStati,
            ],
        );
        dd($query);

        return collect($wpdb->get_col($query))
            ->map(absint(...))
            ->all();
    }
}
