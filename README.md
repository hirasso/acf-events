# ACF Events

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/festival-perspectives-events.svg)](https://packagist.org/packages/hirasso/festival-perspectives-events)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/festival-perspectives-events/ci.yml?label=tests)](https://github.com/hirasso/festival-perspectives-events/actions/workflows/ci.yml)

**A Composer library to manage events, recurrences and locations using WordPress + Advanced Custom Fields 📆**

## Installation

This is not a WordPress plugin. You need to install it via composer and boot manually.

```shell
composer require hirasso/festival-perspectives-events
```

Then, in your theme's `functions.php` or wherever you boot your site:

```php
require_once dirname(__DIR__) . '/vendor/autoload.php';
acf_events();
```
