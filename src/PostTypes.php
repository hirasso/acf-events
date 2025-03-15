<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

/**
 * Make the custom post types available globally
 */
final readonly class PostTypes
{
    public const EVENT = 'acfe-event';
    public const RECURRENCE = 'acfe-recurrence';
    public const LOCATION = 'acfe-location';
}
