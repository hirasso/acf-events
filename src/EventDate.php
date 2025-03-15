<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

use DateTimeImmutable;

/**
 * Represents an event date
 */
final readonly class EventDate
{
    public DateTimeImmutable $date;
    public bool $isCurrent;

    /**
     * @param string $dateString The date string
     * @param int $postID The post ID (may point to an acfe-event or acfe-recurrence)
     */
    public function __construct(
        string $dateString,
        public int $postID
    ) {
        $this->date = new DateTimeImmutable($dateString, wp_timezone());
        $this->isCurrent = $this->isCurrent();
    }

    private function isCurrent()
    {
        $urlID = $_GET['recurrence'] ?? null;

        if (is_numeric($urlID)) {
            return intval($urlID) === $this->postID;
        }

        return $this->postID === get_queried_object_id();
    }

    public function toW3C(): string
    {
        return $this->date->format(DATE_W3C);
    }

    public function toFormattedString()
    {
        $format = get_option('date_format') . ', H:i';
        return date_i18n($format, $this->date->getTimestamp());
    }
}
