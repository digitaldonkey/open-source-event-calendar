<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().

use Osec\App\View\Event\EventTimeView;

if (empty($parent) && empty($children)) {
    return '';
}
// TODO Seems a bit hacky here.One day all stuff in public might be twig.
global $osec_app;
?>
<div class="ai1ec-panel-heading">
    <a data-toggle="ai1ec-collapse"
       data-parent="#osec-add-new-event-accordion"
       href="#osec-event-children-box">
        <i class="ai1ec-fa ai1ec-fa-retweet"></i> <?php
        if ($parent) {
            esc_html_e('Base recurrence event', 'open-source-event-calendar');
        } else {
            esc_html_e('Modified recurrence events', 'open-source-event-calendar');
        }
        ?>
    </a>
</div>
<div id="osec-event-children-box" class="ai1ec-panel-collapse ai1ec-collapse">
    <div class="ai1ec-panel-body">
        <?php if ($parent) : ?>
            <?php esc_html_e('Edit parent:', 'open-source-event-calendar'); ?>
            <a href="<?php echo get_edit_post_link($parent->get('post_id')); ?>"><?php
                echo apply_filters('the_title', $parent->get('post')->post_title, $parent->get('post_id'));
                ?></a>
        <?php else : /* children */ ?>
            <h4><?php esc_html_e('Modified Events', 'open-source-event-calendar'); ?></h4>
            <ul>
                <?php foreach ($children as $child) : ?>
                    <li>
                        <?php esc_html_e('Edit:', 'open-source-event-calendar'); ?>
                        <a href="<?php echo get_edit_post_link($child->get('post_id')); ?>"><?php
                            echo $child->get('post')->post_title;
                            ?></a>, <?php echo EventTimeView::factory($osec_app)->get_timespan_html($child, 'long'); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
