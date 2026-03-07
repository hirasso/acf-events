# ACF Events

**A Composer library to manage events, recurrences and locations using WordPress+Advanced Custom Fields 📆**

## Installation

This is not a WordPress plugin. You need to install it via composer and boot manually.

```shell
composer require hirasso/acf-events
```

Then, in your theme's `functions.php` or wherever you boot your site:

```php
require_once dirname(__DIR__) . '/vendor/autoload.php';

acf_events();
```