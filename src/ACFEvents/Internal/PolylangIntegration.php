<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents\Internal;

use WP_Term;

/**
 * Polylang integration for ACFEvents
 */
final class PolylangIntegration
{
    private static ?self $instance = null;

    private function __construct()
    {
        $this->registerStrings();
    }

    public static function init()
    {
        self::$instance ??= new self();
        return self::$instance;
    }

    public function addHooks(): self
    {
        if (has_filter('term_link', [$this, 'event_filter_term_link'], 11)) {
            return $this;
        }
        if (!$this->isPolylangActive()) {
            return $this;
        }

        add_filter('term_link', [$this, 'event_filter_term_link'], 11, 2);
        add_filter('gettext', [$this, 'translate_gettext'], 10, 3);

        return $this;
    }

    private function registerStrings(): void
    {
        if (!$this->isPolylangActive()) {
            return;
        }

        pll_register_string('acf-events', 'Minutes');
        pll_register_string('acf-events', 'Program');
        pll_register_string('acf-events', 'A-Z');
        pll_register_string('acf-events', 'Calendar');
        pll_register_string('acf-events', 'Places');
        pll_register_string('acf-events', 'Yesterday');
        pll_register_string('acf-events', 'Today');
        pll_register_string('acf-events', 'Tomorrow');
        pll_register_string('acf-events', 'Open in Maps');
        pll_register_string('acf-events', 'Tickets');
        pll_register_string('acf-events', 'Location');
        pll_register_string('acf-events', 'Cast');
        pll_register_string('acf-events', 'Production');
    }

    /**
     * Check if Polylang is active
     */
    protected function isPolylangActive(): bool
    {
        return \function_exists('PLL');
    }

    /**
     * Check if Polylang PRO is active
     */
    protected function isPolylangProActive(): bool
    {
        return $this->isPolylangActive() && !empty(\PLL()->translate_slugs);
    }

    /**
     * Translate ACF Event Filter Links
     */
    public function event_filter_term_link(string $link, WP_Term $term): string
    {
        if (!$this->isPolylangProActive()) {
            return $link;
        }

        if ($term->taxonomy !== Core::FILTER_TAXONOMY) {
            return $link;
        }

        if (!\str_starts_with($link, \get_post_type_archive_link(PostTypes::EVENT))) {
            return $link;
        }

        $postType = PostTypes::EVENT;
        $termLanguage = \pll_get_term_language($term->term_id, \OBJECT);

        /** The term has no language: nothing to do. */
        if (!$termLanguage) {
            return $link;
        }

        $curlang = \PLL()->curlang;

        /** No current language, or the term's language is the same as the current language: nothing to do. */
        if ($curlang && $curlang->slug === $termLanguage->slug) {
            return $link;
        }

        /** @phpstan-ignore property.notFound */
        $link = \PLL()->translate_slugs->slugs_model->switch_translated_slug($link, $termLanguage, "archive_{$postType}");
        $link = \PLL()->links_model->switch_language_in_link($link, $termLanguage);

        return $link;
    }

    /**
     * Translate __('something', 'acf-events') using Polylang
     */
    public function translate_gettext(string $translated, string $original, string $domain): string
    {
        if ($domain === 'acf-events' && $translated === $original) {
            return \pll__($original);
        }
        return $translated;
    }
}
