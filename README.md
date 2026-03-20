# ACF Events

[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/festival-perspectives-events/ci.yml?label=tests)](https://github.com/hirasso/festival-perspectives-events/actions/workflows/ci.yml)

**📆 Open sourced code that powers the program on https://www.festival-perspectives.de/programm/. Based on WordPress + Advanced Custom Fields**

![CleanShot 2026-03-20 at 09 24 12@2x](https://github.com/user-attachments/assets/b208c360-1ca9-404a-8cb7-b60046fe6120)

![CleanShot 2026-03-20 at 09 25 59@2x](https://github.com/user-attachments/assets/6f0b3772-ea5c-4868-a54e-5229ec6704be)

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
