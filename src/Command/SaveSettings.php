<?php

namespace Osec\Command;

use Osec\App\View\Admin\AdminPageAbstract;
use Osec\Exception\Exception;

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
        if (
            ! isset($_REQUEST[$this->nonceName])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_REQUEST[$this->nonceName])),
                key($this->action)
            )
        ) {
            wp_die('Invalid nonce');
        }

        $options = $this->app->settings->get_options();
        $req_data = $_REQUEST;

        $req_data['default_tags_categories'] = (
            isset($req_data['default_tags_categories_default_categories']) ||
            isset($req_data['default_tags_categories_default_tags'])
        );
        // set some a variable to true to trigger the saving.
        $req_data['enabled_views'] = true;

        /**
         * Alter Settings before validation.
         *
         * Let other plugin modify the POST variables
         * before validations of settings.
         *
         * @since 1.0
         *
         * @param  array  $_REQUEST  Maybe unvalidated variables.
         */
        $req_data = apply_filters('osec_pre_validate_settings', $req_data);
        foreach ($options as $name => $data) {
            $value = null;

            // Sanity check.
            if ( ! isset($data['renderer']['validator']) && ! isset($data['type'])) {
                throw new Exception(
                    esc_html('No validation defined for ' . $name)
                );
            }

            if ( ! isset($req_data[$name]) && isset($data['type']) && 'bool' === $data['type']) {
                // False booleans are not send by browser.
                $value = false;
            }

            if (isset($req_data[$name])) {
                if (isset($data['renderer']['validator'])) {
                    throw new \Exception('Renderer->validattor is not supported anymore..');
                }
                $post_field_value = sanitize_text_field(wp_unslash($req_data[$name]));

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
                            $value = $this->$method($req_data);
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
            'url' => admin_url(OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'settings'),
            'query_args' => ['updated' => 1],
        ];
    }

    /**
     * Handle saving enabled_views.
     *
     * @return array
     */
    protected function handleSaving_enabled_views($req_data)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $enabled_views = $this->app->settings->get('enabled_views');
        foreach ($enabled_views as $view => &$options) {
            $options['enabled'] = isset($req_data['view_' . $view . '_enabled']);
            $options['default'] = isset($req_data['default_calendar_view'])
                ? $req_data['default_calendar_view'] === $view
                : false;
            $options['enabled_mobile'] =
                isset($req_data['view_' . $view . '_enabled_mobile']);
            $options['default_mobile'] =
                isset($req_data['default_calendar_view_mobile']) &&
                $req_data['default_calendar_view_mobile'] === $view;
        }
        // phpcs:enable
        return $enabled_views;
    }

    /**
     * Handle saving default_tag_categories option
     *
     * @return array
     */
    protected function handleSaving_default_tags_categories($req_data)
    {
        $tags = isset($req_data['default_tags_categories_default_tags'])
                    && is_array($req_data['default_tags_categories_default_tags']) ?
                        array_map('absint', $req_data['default_tags_categories_default_tags']) : [];
        $categories = isset($req_data['default_tags_categories_default_categories'])
                    && is_array($req_data['default_tags_categories_default_categories']) ?
                        array_map('absint', $req_data['default_tags_categories_default_categories']) : [];
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
    protected function handleSaving_calendar_page_id($req_data)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $calendar_page = isset($req_data['calendar_page_id']) ?
            sanitize_text_field(wp_unslash($req_data['calendar_page_id'])) : null;
        // phpcs:enable
        if (
            ! is_numeric($calendar_page) &&
            preg_match('#^__auto_page:(.*?)$#', $calendar_page, $matches)
        ) {
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
