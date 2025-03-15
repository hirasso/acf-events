<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\ACFEvents;

use RuntimeException;
use WP_Term;
use WP_Post;

/**
 * Polylang integration for ACFEvents
 */
final class PolylangIntegration
{
    protected static bool $registered = false;

    public function __construct(private Core $core)
    {
    }

    public function register()
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

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

        add_action('pll_save_post', [$this, 'pll_save_post'], 10, 3);
        add_filter('term_link', [$this, 'event_filter_term_link'], 11, 2);
        add_filter('gettext', [$this, 'translate_gettext'], 10, 3);
    }

    /**
     * Check if Polylang is active
     */
    protected function isPolylangActive(): bool
    {
        return function_exists('PLL');
    }

    /**
     * Check if Polylang PRO is active
     */
    protected function isPolylangProActive(): bool
    {
        return $this->isPolylangActive() && !empty(PLL()->translate_slugs);
    }

    /**
     * Handle a just-saved location
     */
    public function pll_save_post(int $locationID, WP_Post $post, array $translations)
    {
        if (!$this->core->isLocation($locationID)) {
            return;
        }
        remove_action('pll_save_post', [$this, 'pll_save_post'], 10);

        $this->createMissingPostTranslations($locationID, $post, $translations);

        add_action('pll_save_post', [$this, 'pll_save_post'], 10, 3);
    }

    /**
     * Ensure translations exist in all languages for a post
     */
    public function createMissingPostTranslations(int $postID, WP_Post $post, array $translations): void
    {
        if (!$this->isPolylangActive()) {
            return;
        }

        if (!$this->core->isVisiblePostStatus($postID)) {
            return;
        }

        $missingTranslations = collect(pll_the_languages(['raw' => true]))
                ->pluck('slug')
                ->diff(array_keys($translations))
                ->all();

        foreach ($missingTranslations as $lang) {
            $translations[$lang] = $this->createPostTranslation($postID, $lang);
        }

        pll_save_post_translations($translations);
    }

    /**
     * Ensure a translation exists for a post
     */
    protected function createPostTranslation(int $postID, string $lang): int
    {
        if (
            $existingTranslation = collect(pll_get_post_translations($postID))
            ->first(fn ($_, $translationLanguage) => $translationLanguage === $lang)
        ) {
            return $existingTranslation;
        }

        /** @var \PLL_Language $language */
        $language = PLL()->model->get_language($lang);

        $originalMeta = $this->core->getFlatPostMeta($postID);
        $originalPostArray = get_post($postID, ARRAY_A);

        $taxInput = collect(get_post_taxonomies($postID))
            ->except(['language', 'post_translations'])
            ->mapWithKeys(fn ($tax) => [
                $tax => collect(wp_get_object_terms($postID, $tax))
                    ->map(fn ($term) => $term->term_id)
                    ->all(),
            ])
            ->all();

        $postarr = collect($originalPostArray)
            ->except(['ID'])
            ->merge([
                'meta_input' => $originalMeta,
                'tax_input' => $taxInput,
            ])
            ->all();

        $translationID = wp_insert_post($postarr, true);

        if (is_wp_error($translationID)) {
            throw new RuntimeException($translationID->get_error_message());
        }

        pll_set_post_language($translationID, $lang);

        /**
         * Re-save the post_name after the post language was set,
         * so that Polylang can share the same slug between languages
         */
        wp_update_post([
            'ID' => $translationID,
            'post_name' => $originalPostArray['post_name']
        ]);

        return $translationID;
    }

    /** @return \wpdb */
    protected function wpdb()
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Translate ACF Event Filter Links
     */
    public function event_filter_term_link(string $link, WP_Term $term): string
    {
        if (!$this->isPolylangProActive()) {
            return $link;
        }

        if ($term->taxonomy !== ACFEvents::FILTER_TAXONOMY) {
            return $link;
        }

        if (!str_starts_with($link, get_post_type_archive_link(PostTypes::EVENT))) {
            return $link;
        }

        $postType = PostTypes::EVENT;
        $termLanguage = pll_get_term_language($term->term_id, \OBJECT);

        /** The term has no language: nothing to do. */
        if (!$termLanguage) {
            return $link;
        }

        $curlang = PLL()->curlang;

        /** No current language, or the term's language is the same as the current language: nothing to do. */
        if ($curlang && $curlang->slug === $termLanguage->slug) {
            return $link;
        }

        /** @phpstan-ignore property.notFound */
        $link = PLL()->translate_slugs->slugs_model->switch_translated_slug($link, $termLanguage, "archive_{$postType}");
        $link = PLL()->links_model->switch_language_in_link($link, $termLanguage);

        return $link;
    }

    /**
     * Translate __('something', 'acf-events') using Polylang
     */
    public function translate_gettext(string $translated, string $original, string $domain): string
    {
        if ($domain === 'acf-events' && $translated === $original) {
            return pll__($original);
        }
        return $translated;
    }
}
