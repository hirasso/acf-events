<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\WP\FPEvents\FieldGroups;

use Exception;
use Hirasso\WP\FPEvents\Core;

/**
 * Global field names
 */
abstract class Fields
{
    private static array $instances = [];

    final protected function __construct(protected Core $core) {}

    final public static function init(Core $core): static
    {
        return self::$instances[static::class] ??= new static($core);
    }

    final public function addHooks(): static
    {
        if (has_action('acf/include_fields', [$this, 'acf_include_fields'])) {
            return $this;
        }
        add_action('acf/include_fields', [$this, 'acf_include_fields']);
        return $this;
    }

    /**
     * Include ACF fields
     */
    final public function acf_include_fields()
    {
        if (! function_exists('acf_add_local_field_group')) {
            throw new Exception("'acf_add_local_field_group()' is not defined");
        }

        $this->addFields();
    }

    /**
     * Must be implemented by children. Should register ACF fields
     */
    abstract protected function addFields();

    /**
     * Returns the field key for a given field name
     */
    public static function key(string $fieldName): string
    {
        return "field_$fieldName";
    }
}
