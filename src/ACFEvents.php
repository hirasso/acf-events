<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

use DI\Container;

/**
 * Manage events, recurrences and locations using Advanced Custom Fields
 */
final class ACFEvents
{
    public const ISO_DATE_FORMAT = 'Y-m-d H:i:s';
    public const CLAUSES_KEY = 'acfe:clauses';
    public const FILTER_TAXONOMY = 'acfe-event_filter';

    private Container $container;

    public function __construct()
    {
        $container = new Container();

        $container->get(Core::class)->register();
        $container->get(FieldGroups\EventFields::class)->register();
        $container->get(FieldGroups\LocationFields::class)->register();
        $container->get(Locations::class)->register();
        $container->get(Recurrences::class)->register();
        $container->get(PolylangIntegration::class)->register();

        $this->container = $container;
    }

    /** @return Core */
    public function core()
    {
        return $this->container->get(Core::class);
    }
}
