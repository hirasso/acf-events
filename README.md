# ACF Events

[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/festival-perspectives-events/ci.yml?label=tests)](https://github.com/hirasso/festival-perspectives-events/actions/workflows/ci.yml)

**📆 Open sourced code that powers the program events and locations on the FESTIVAL PERSPECTIVES website. Based on WordPress + Advanced Custom Fields.**

## Demo

**https://www.festival-perspectives.de/programm/**

## Main Features

- Basic fields for Events and Locations
- Different views: A-Z, By Day, By Location
- Auatomatic Recurrence management based on an ACF repeater "Further Dates"

## Screenshots

![Frontend View](https://github.com/user-attachments/assets/b208c360-1ca9-404a-8cb7-b60046fe6120)

![Admin View: Event](https://github.com/user-attachments/assets/6f0b3772-ea5c-4868-a54e-5229ec6704be)

![Admin View: Location](https://github.com/user-attachments/assets/d1b9cfa3-8fc4-4964-9286-58985555f618)

## Installation

This is not a WordPress plugin. Install it via composer:

```shell
# add the custom repository to the config:
composer config repositories.festival-perspectives-events vcs https://github.com/hirasso/festival-perspectives-events
# install it
composer require hirasso/festival-perspectives-events
```

Then, in your theme's `functions.php` or wherever you boot your site:

```php
/** require the composer autoloader */
require_once dirname(__DIR__) . '/vendor/autoload.php';
/** initialize the module */
fp_events();
/** the same function is also the access point for the API, e.g.: */
fp_events()->getEventDateAndTime($post_id);
```
