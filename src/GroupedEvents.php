<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

namespace Hirasso\ACFEvents;

final class GroupedEvents
{
    /** @param \WP_Post[]|null $posts */
    public function __construct(
        public string $title,
        public ?array $posts = []
    ) {
    }
}
