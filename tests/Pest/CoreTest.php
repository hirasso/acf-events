<?php

use Hirasso\ACFEvents\Core;

uses(\Yoast\WPTestUtils\WPIntegration\TestCase::class);

test('Provides access to the Core via acfEvents()', function () {
    expect(acfEvents())->toBeInstanceOf(Core::class);
});
