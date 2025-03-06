<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * A class that renders bootstrap modals.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Bootstrap_Modal
 */
class ModalQuestion extends SettingsAbstract
{
    /**
     * @param  string  $html
     * @param  true  $wrap  *
     *
     * @return string
     */
    public function render($html = '', $wrap = true): string
    {
        $args = [
            'id' =>  $this->render_id_if_present(),
            'header' => $this->render_header_if_present(),
            'body' => $this->args['body_text'],
            'remove_event_button' => $this->render_remove_button_if_present(),
            'keep_event_button' => $this->render_keep_button_if_present(),
        ];
        return ThemeLoader::factory($this->app)
                          ->get_file('modal_question.twig', $args, true)
                          ->get_content();
    }

    private function render_header_if_present(): string
    {
        return isset($this->args['header_text']) ?
            '<h2>' . $this->args['header_text'] . '</h2>'
            : '';
    }

    private function render_id_if_present(): string
    {
        return isset($this->args['id']) ? "id='{$this->args['id']}'" : '';
    }

    private function render_remove_button_if_present(): string
    {
        return isset($this->args['delete_button_text']) ?
            "<a href='#' class='ai1ec-btn remove ai1ec-btn-danger ai1ec-btn-lg'>{$this->args['delete_button_text']}</a>"
            : '';
    }

    private function render_keep_button_if_present(): string
    {
        return isset($this->args['keep_button_text'])
            ? "<a href='#' class='ai1ec-btn keep ai1ec-btn-primary ai1ec-btn-lg'>{$this->args['keep_button_text']}</a>"
            : '';
    }
}
