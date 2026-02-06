<?php

namespace Osec\Command;

use Osec\Exception\Exception;
use Osec\Http\Request\RequestParser;

/**
 * The concrete command that save settings.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Save_Settings
 * @author     Time.ly Network Inc.
 */
class SaveSettings extends SaveAbstract
{
    public function do_execute()
    {
        $nonce = RequestParser::get_param($this->nonceName, null);
        if (! $nonce || wp_verify_nonce($nonce, key($this->action)) !== 1) {
            wp_die('Invalid nonce');
        }

        $options = $this->app->settings->get_options();

        // Add common handler for tags and categories
        $_REQUEST['default_tags_categories'] = (
            isset($_REQUEST['default_tags_categories_default_categories']) ||
            isset($_REQUEST['default_tags_categories_default_tags'])
        );
        // Set some a variable to true to trigger the saving.
        $_REQUEST['enabled_views'] = true;

        foreach ($options as $name => $data) {
            $value = null;

            // Sanity checks.
            if ( ! isset($data['renderer']['validator']) && ! isset($data['type'])) {
                throw new Exception(
                    esc_html('No validation defined for ' . $name)
                );
            }
            if (isset($data['renderer']['validator'])) {
                throw new \Exception('Renderer->validattor is not supported anymore..');
            }

            // False booleans are not send by browser.
            if ( ! isset($_REQUEST[$name]) && isset($data['type']) && 'bool' === $data['type']) {
                $value = false;
            }

            $post_field_value = RequestParser::get_param($name, null);
            if ($post_field_value) {
                switch ($data['type']) {
                    case 'bool':
                        $value = true;
                        break;
                    case 'int':
                        $value = (int) $post_field_value;
                        break;
                    case 'string':
                        $value = $post_field_value;
                        break;
                    case 'array':
                    case 'mixed':
                        $method = 'handleSaving_' . $name;
                        $value  = null;
                        if (method_exists($this, $method)) {
                            $value = $this->$method();
                        }
                        /**
                         * Post process saving of save handler value.
                         *
                         * Settings can have save handler. Like: handleSaving_$option_name()
                         *
                         * @since 1.0
                         *
                         * @param  string  $method  Save handler.
                         * @param  array  $value  Value returned by $method or null.
                         */
                        $value = apply_filters('osec' . $method, $value);
                        break;
                    case 'wp_option':
                        // Save the corresponding WP option
                        $this->app->options->set($name, $post_field_value);
                        $value = $post_field_value;
                        break;
                    default:
                        throw new Exception(
                            esc_html('No validation defined datatype ' . $data['type'])
                        );
                }
            }

            // Save
            if (null !== $value) {
                /**
                 * Alter Settings before save.
                 *
                 * Let other plugin modify the POST variables
                 * before saving settings.
                 *
                 * @since 1.0
                 *
                 * @param  array  $value  Maybe unvalidated variables.
                 */
                $value = apply_filters('osec_pre_save_settings', stripslashes_deep($value));

                $this->app->settings->set($name, $value);
            }
        }

        $new_options = $this->app->settings->get_options();

        /**
         * Action after Settings save.
         *
         * Let other plugin act on changed settings.
         *
         * @since 1.0
         *
         * @param  array  $options  Old options.
         * @param  array  $new_options  New options.
         */
        do_action('osec_settings_updated', $options, $new_options);

        $this->app->settings->persist();

        return [
            'url' => admin_url(OSEC_ADMIN_BASE_URL . '&page=osec-admin-settings'),
            'query_args' => ['updated' => 1],
        ];
    }

    /**
     * Handle saving enabled_views.
     *
     * @return array
     */
    protected function handleSaving_enabled_views()
    {
        $enabled_views = $this->app->settings->get('enabled_views');
        foreach ($enabled_views as $view => &$options) {
            $options['enabled'] = RequestParser::get_param('view_' . $view . '_enabled', 0);
            $default_view = RequestParser::get_param('default_calendar_view', null);
            $options['default'] = ($default_view === $view);
            $options['enabled_mobile'] = RequestParser::get_param('view_' . $view . '_enabled_mobile', 0);
            $default_mobile = RequestParser::get_param('default_calendar_view_mobile', null);
            $options['default_mobile'] = ($default_mobile === $view);
        }
        // phpcs:enable
        return $enabled_views;
    }

    /**
     * Handle saving default_tag_categories option
     *
     * @return array
     */
    protected function handleSaving_default_tags_categories()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $tags = isset($_REQUEST['default_tags_categories_default_tags'])
                    && is_array($_REQUEST['default_tags_categories_default_tags']) ?
                        array_map('absint', $_REQUEST['default_tags_categories_default_tags']) : [];
        $categories = isset($_REQUEST['default_tags_categories_default_categories'])
                    && is_array($_REQUEST['default_tags_categories_default_categories']) ?
                        array_map('absint', $_REQUEST['default_tags_categories_default_categories']) : [];
        // phpcs:enable
        return [
            'tags'       => $tags,
            'categories' => $categories,
        ];
    }

    /**
     * Creates the calendar page if a string is passed.
     *
     * @return int
     */
    protected function handleSaving_calendar_page_id()
    {
        $calendar_page = RequestParser::get_param('calendar_page_id', null);
        if (! is_numeric($calendar_page)
            && preg_match('#^__auto_page:(.*?)$#', $calendar_page, $matches)
        ) {
            // Provide required default.
            return wp_insert_post(
                [
                    'post_title'     => $matches[1],
                    'post_type'      => 'page',
                    'post_status'    => 'publish',
                    'comment_status' => 'closed',
                ]
            );
        }
        return (int)$calendar_page;
    }
}
