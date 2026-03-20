<?php

use Hirasso\FestivalPerspectivesEvents\FestivalPerspectivesEvents;

/** @return FestivalPerspectivesEvents */
function acf_events()
{
    return FestivalPerspectivesEvents::init();
}
