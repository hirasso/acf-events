<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents\Internal;

final class GroupedEvents
{
    /** @param \WP_Post[]|null $posts */
    public function __construct(
        public string $title,
        public ?array $posts = [],
    ) {}
}
