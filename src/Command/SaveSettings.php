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
        // Nonce verification happens in SaveAbstract->is_this_to_execute().
        // phpcs:disable WordPress.Security.NonceVerification.Missing

        $options = $this->app->settings->get_options();
        $_POST['default_tags_categories'] = (
            isset($_POST['default_tags_categories_default_categories']) ||
            isset($_POST['default_tags_categories_default_tags'])
        );
        // set some a variable to true to trigger the saving.
        $_POST['enabled_views'] = true;

        /**
         * Alter Settings before validation.
         *
         * Let other plugin modify the POST variables
         * before validations of settings.
         *
         * @since 1.0
         *
         * @param  array  $_POST  Maybe unvalidated variables.
         */
        $_POST = apply_filters('osec_pre_validate_settings', $_POST);
        foreach ($options as $name => $data) {
            $value = null;

            // Sanity check.
            if ( ! isset($data['renderer']['validator']) && ! isset($data['type'])) {
                throw new Exception(
                    esc_html('No validation defined for ' . $name)
                );
            }

            if ( ! isset($_POST[$name]) && isset($data['type']) && 'bool' === $data['type']) {
                // False booleans are not send by browser.
                $value = false;
            }

            if (isset($_POST[$name])) {
                if (isset($data['renderer']['validator'])) {
                    throw new \Exception('Renderer->validattor is not supported anymore..');
                }

                switch ($data['type']) {
                    case 'bool':
                        $value = true;
                        break;
                    case 'int':
                        $value = (int) $_POST[$name];
                        break;
                    case 'string':
                        $value = (string) $_POST[$name];
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
                        $this->app->options->set($name, $_POST[$name], true);
                        $value = sanitize_text_field($_POST[$name]);
                        break;
                    default:
                        throw new Exception(
                            esc_html('No validation defined datatype ' . $data['type'])
                        );
                }
            }
            // phpcs:enable WordPress.Security.NonceVerification.Missing

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
                 * @param  array  $_POST  Maybe unvalidated variables.
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
            'url'        => admin_url(
                OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'settings'
            ),
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
        // Nonce verification happens in SaveAbstract->is_this_to_execute().
        // phpcs:disable WordPress.Security.NonceVerification.Missing

        $enabled_views = $this->app->settings->get('enabled_views');
        foreach ($enabled_views as $view => &$options) {
            $options['enabled'] = isset($_POST['view_' . $view . '_enabled']);
            $options['default'] = isset($_POST['default_calendar_view'])
                ? $_POST['default_calendar_view'] === $view
                : false;
            $options['enabled_mobile'] =
                isset($_POST['view_' . $view . '_enabled_mobile']);
            $options['default_mobile'] =
                isset($_POST['default_calendar_view_mobile']) &&
                $_POST['default_calendar_view_mobile'] === $view;
        }

        return $enabled_views;
    }

    /**
     * Handle saving default_tag_categories option
     *
     * @return array
     */
    protected function handleSaving_default_tags_categories()
    {
        return [
            'tags'       => $_POST['default_tags_categories_default_tags'] ?? [],
            'categories' => $_POST['default_tags_categories_default_categories'] ?? [],
        ];
    }

    /**
     * Creates the calendar page if a string is passed.
     *
     * @return int
     */
    protected function handleSaving_calendar_page_id()
    {
        $calendar_page = isset($_POST['calendar_page_id']) ? $_POST['calendar_page_id'] : null;
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
