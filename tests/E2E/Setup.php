<?php

namespace Hirasso\ACFEvents\Tests\E2E;

use Exception;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\URL;
use Extended\ACF\Location;
use WP_Post;

/** Exit if accessed directly */
if (!\defined('ABSPATH')) {
    exit;
}

/**
 * Setup context to run e2e tests against
 */
final class Setup
{
    protected WP_Post $testPage;

    /**
     * @var array{
     *  key: string
     * } $fieldGroup
     */
    protected array $fieldGroup;

    public function __construct()
    {
        if (!function_exists('register_field_group')) {
            return;
        }

        $this->testPage = $this->getTestPage();
        $this->fieldGroup = $this->setupTestFieldGroup();

        \add_filter('render_block', [$this, 'renderBlock'], 10, 2);
    }

    /**
     * Inject test content after the post title
     *
     * @param array{
     *   blockName: string
     * } $block
     */
    public function renderBlock(string $content, array $block): string
    {
        if ($block['blockName'] !== 'core/post-title') {
            return $content;
        }

        \ob_start(); ?>

        <?= $content ?>

        <?php return \ob_get_clean();
    }

    /**
     * Get a test page to hold the frontend form for e2e tests
     */
    protected function getTestPage(): ?WP_Post
    {
        /**
         * First, try an existing post
         * @var ?int $postID
         */
        $postID = \get_posts([
            'post_type' => 'page',
            'post_status' => 'any',
            'meta_query' => [
                'key' => 'e2e_test_page',
                'value' => '1',
            ],
            'fields' => 'ids',
        ])[0] ?? null;

        /**
         * Create one if none exists
         */
        if (!$postID) {
            $postID = \wp_insert_post([
                'post_type' => 'page',
            ]);
        }

        if (\is_wp_error($postID)) {
            throw new Exception($postID->get_error_message());
        }

        /**
         * Set post properties here
         */
        \wp_update_post([
            'ID' => $postID,
            'post_title' => 'Test Page',
            'post_name' => 'test-page',
            'post_status' => 'publish',
            'meta_input' => [
                'e2e_test_page' => true,
            ],
        ]);

        return \get_post($postID);
    }

    /**
     * @return array<string, mixed>
     */
    protected function setupTestFieldGroup(): array
    {
        return \register_extended_field_group([
            'title' => 'Test Field Group',
            'fields' => [
                Text::make('First Name')
                    ->column(50)
                    ->required(),

                Text::make('Last Name')
                    ->column(50)
                    ->required(),

                Textarea::make('Message'),

                Image::make('An Image'),

                Repeater::make('Some Links')
                    ->fields([
                        URL::make('Link')
                            ->required(),
                    ])
                    ->minRows(1),

            ],
            'location' => [
                Location::where('post_type', 'page'),
            ],
            'position' => 'acf_after_title',
            'style' => 'default',
            'active' => true,
        ]);
    }

}
