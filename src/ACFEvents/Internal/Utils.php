<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents\Internal;

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
     * @return list<int>
     */
    public function getYears(string $postType): array
    {
        $wpdb = $this->wpdb();

        $years = $wpdb->get_col(
            $wpdb->prepare(
                <<<SQL
                SELECT DISTINCT YEAR(post_date)
                FROM {$wpdb->posts}
                WHERE post_type = %s
                AND post_status = 'publish'
                ORDER BY post_date DESC
                SQL,
                $postType,
            ),
        );

        return collect($years)->map(absint(...))->all();
    }
}
